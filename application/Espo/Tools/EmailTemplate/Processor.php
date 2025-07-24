<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
 * Website: https://www.espocrm.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU Affero General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU Affero General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

namespace Espo\Tools\EmailTemplate;

use Espo\Core\Templates\Entities\Person as PersonTemplate;
use Espo\Modules\Crm\Entities\Account;
use Espo\Modules\Crm\Entities\Contact;
use Espo\Modules\Crm\Entities\Lead;
use Espo\ORM\EntityManager;
use Espo\ORM\Entity;
use Espo\Core\AclManager;
use Espo\Core\Record\ServiceContainer;
use Espo\Core\Utils\Config;
use Espo\Core\FileStorage\Manager as FileStorageManager;
use Espo\Core\Entities\Person;
use Espo\Core\Htmlizer\HtmlizerFactory as HtmlizerFactory;
use Espo\Core\Htmlizer\Htmlizer;
use Espo\Core\Acl\GlobalRestriction;
use Espo\Entities\EmailTemplate;
use Espo\Entities\User;
use Espo\Entities\Attachment;
use Espo\Entities\EmailAddress;
use Espo\Repositories\EmailAddress as EmailAddressRepository;

use Exception;

class Processor
{
    private const KEY_PARENT = 'Parent';

    public function __construct(
        private Formatter $formatter,
        private EntityManager $entityManager,
        private AclManager $aclManager,
        private ServiceContainer $recordServiceContainer,
        private Config $config,
        private FileStorageManager $fileStorageManager,
        private User $user,
        private HtmlizerFactory $htmlizerFactory,
        private PlaceholdersProvider $placeholdersProvider,
        private EntityMapProvider $entityMapProvider,
    ) {}

    public function process(EmailTemplate $template, Params $params, Data $data): Result
    {
        $user = $data->getUser() ?? $this->user;

        [$entityHash, $data] = $this->prepare($data, $user, $params);

        $subject = $template->getSubject() ?? '';
        $body = $template->getBody() ?? '';

        $parent = $entityHash[self::KEY_PARENT] ?? null;

        if ($parent && !$this->config->get('emailTemplateHtmlizerDisabled')) {
            $handlebarsInSubject = str_contains($subject, '{{') && str_contains($subject, '}}');
            $handlebarsInBody = str_contains($body, '{{') && str_contains($body, '}}');

            if ($handlebarsInSubject || $handlebarsInBody) {
                $htmlizer = $this->createHtmlizer($params, $user);

                if ($handlebarsInSubject) {
                    $subject = $htmlizer->render($parent, $subject);
                }

                if ($handlebarsInBody) {
                    $body = $htmlizer->render($parent, $body, null, null, false, true);
                }
            }
        }

        foreach ($entityHash as $type => $entity) {
            $subject = $this->processText(
                type: $type,
                entity: $entity,
                text: $subject,
                user: $user,
                skipAcl: !$params->applyAcl(),
                isHtml: $template->isHtml(),
            );
        }

        foreach ($entityHash as $type => $entity) {
            $body = $this->processText(
                type: $type,
                entity: $entity,
                text: $body,
                user: $user,
                skipAcl: !$params->applyAcl(),
                isHtml: $template->isHtml(),
            );
        }

        $subject = $this->processPlaceholders($subject, $data);
        $body = $this->processPlaceholders($body, $data);

        $attachmentList = $params->copyAttachments() ?
            $this->copyAttachments($template) : [];

        return new Result(
            subject: $subject,
            body: $body,
            isHtml: $template->isHtml(),
            attachmentList: $attachmentList,
        );
    }

    private function processPlaceholders(string $text, Data $data): string
    {
        foreach ($this->placeholdersProvider->get() as [$key, $placeholder]) {
            $value = $placeholder->get($data);

            $text = str_replace('{' . $key . '}', $value, $text);
        }

        return $text;
    }

    private function processText(
        string $type,
        Entity $entity,
        string $text,
        User $user,
        bool $skipLinks = false,
        ?string $prefixLink = null,
        bool $skipAcl = false,
        bool $isHtml = true
    ): string {

        $attributeList = $entity->getAttributeList();

        $forbiddenAttributeList = [];

        if (!$skipAcl) {
            $forbiddenAttributeList = array_merge(
                $this->aclManager->getScopeForbiddenAttributeList($user, $entity->getEntityType()),
                $this->aclManager->getScopeRestrictedAttributeList(
                    $entity->getEntityType(),
                    [
                        GlobalRestriction::TYPE_FORBIDDEN,
                        GlobalRestriction::TYPE_INTERNAL,
                        GlobalRestriction::TYPE_ONLY_ADMIN,
                    ]
                )
            );
        }

        foreach ($attributeList as $attribute) {
            if (in_array($attribute, $forbiddenAttributeList)) {
                continue;
            }

            if (is_object($entity->get($attribute))) {
                continue;
            }

            if (!$entity->getAttributeType($attribute)) {
                continue;
            }

            $value = $this->formatter->formatAttributeValue($entity, $attribute, !$isHtml);

            if (is_null($value)) {
                continue;
            }

            $variableName = $attribute;

            if (!is_null($prefixLink)) {
                $variableName = "$prefixLink.$attribute";
            }

            $text = str_replace("{{$type}.$variableName}", $value, $text);
        }

        if (!$skipLinks && $entity->hasId()) {
            $text = $this->processLinks(
                type: $type,
                entity: $entity,
                text: $text,
                user: $user,
                skipAcl: $skipAcl,
                isHtml: $isHtml,
            );
        }

        return $text;
    }

    private function processLinks(
        string $type,
        Entity $entity,
        string $text,
        User $user,
        bool $skipAcl,
        bool $isHtml,
    ): string {

        $entityDefs = $this->entityManager->getDefs()->getEntity($entity->getEntityType());

        $forbiddenLinkList = $skipAcl ?
            $this->aclManager->getScopeRestrictedLinkList(
                $entity->getEntityType(),
                [
                    GlobalRestriction::TYPE_FORBIDDEN,
                    GlobalRestriction::TYPE_INTERNAL,
                    GlobalRestriction::TYPE_ONLY_ADMIN,
                ]
            ) :
            [];

        foreach ($entity->getRelationList() as $relation) {
            if (in_array($relation, $forbiddenLinkList)) {
                continue;
            }

            if (
                !in_array($entity->getRelationType($relation), [
                    Entity::BELONGS_TO,
                    Entity::BELONGS_TO_PARENT,
                ])
            ) {
                continue;
            }

            if (
                !$skipAcl &&
                $entityDefs->hasField($relation) &&
                !$this->aclManager->checkField($user, $entity->getEntityType(), $relation)
            ) {
                continue;
            }

            $relatedEntity = $this->entityManager
                ->getRelation($entity, $relation)
                ->findOne();

            if (!$relatedEntity) {
                continue;
            }

            if (!$skipAcl) {
                try {
                    $hasAccess = $this->aclManager->checkEntityRead($user, $relatedEntity);
                } catch (Exception) {
                    continue;
                }

                if (!$hasAccess) {
                    continue;
                }
            }

            $text = $this->processText(
                type: $type,
                entity: $relatedEntity,
                text: $text,
                user: $user,
                skipLinks: true,
                prefixLink: $relation,
                skipAcl: $skipAcl,
                isHtml: $isHtml,
            );
        }

        return $text;
    }

    /**
     * @return Attachment[]
     */
    private function copyAttachments(EmailTemplate $template): array
    {
        $copiedAttachments = [];

        /** @var iterable<Attachment> $attachments */
        $attachments = $this->entityManager
            ->getRelation($template, 'attachments')
            ->find();

        foreach ($attachments as $attachment) {
            $clone = $this->entityManager->getRDBRepositoryByClass(Attachment::class)->getNew();

            $data = $attachment->getValueMap();

            unset($data->parentType);
            unset($data->parentId);
            unset($data->id);

            $clone->set($data);
            $clone->setSourceId($attachment->getSourceId());
            $clone->setStorage($attachment->getStorage());

            if (!$this->fileStorageManager->exists($attachment)) {
                continue;
            }

            $this->entityManager->saveEntity($clone);

            $copiedAttachments[] = $clone;
        }

        return $copiedAttachments;
    }

    private function createHtmlizer(Params $params, User $user): Htmlizer
    {
        if (!$params->applyAcl()) {
            return $this->htmlizerFactory->createNoAcl();
        }

        return $this->htmlizerFactory->createForUser($user);
    }

    private function getEmailAddressRepository(): EmailAddressRepository
    {
        /** @var EmailAddressRepository */
        return $this->entityManager->getRepository(EmailAddress::ENTITY_TYPE);
    }

    /**
     * @return array{array<string, Entity>, Data}
     */
    private function prepare(Data $data, User $user, Params $params): array
    {
        $entityHash = $data->getEntityHash();

        if (!isset($entityHash[User::ENTITY_TYPE])) {
            $entityHash[User::ENTITY_TYPE] = $user;
        }

        $foundByAddressEntity = null;

        if ($data->getEmailAddress()) {
            $foundByAddressEntity = $this->getEmailAddressRepository()->getEntityByAddress(
                $data->getEmailAddress(),
                null,
                [
                    Contact::ENTITY_TYPE,
                    Lead::ENTITY_TYPE,
                    Account::ENTITY_TYPE,
                    User::ENTITY_TYPE,
                ]
            );
        }

        if ($foundByAddressEntity) {
            if ($foundByAddressEntity instanceof Person) {
                $entityHash[PersonTemplate::TEMPLATE_TYPE] = $foundByAddressEntity;
            }

            if (!isset($entityHash[$foundByAddressEntity->getEntityType()])) {
                $entityHash[$foundByAddressEntity->getEntityType()] = $foundByAddressEntity;
            }
        }

        if (
            !$data->getParent() &&
            $data->getParentId() &&
            $data->getParentType()
        ) {
            $parent = $this->entityManager->getEntityById($data->getParentType(), $data->getParentId());

            if ($parent) {
                $service = $this->recordServiceContainer->get($data->getParentType());

                $service->loadAdditionalFields($parent);

                if (
                    $params->applyAcl() &&
                    !$this->aclManager->checkEntityRead($this->user, $parent)
                ) {
                    $parent = null;
                }

                $data = $data->withParent($parent);
            }
        }

        if ($data->getParent()) {
            $parent = $data->getParent();

            $entityHash[$parent->getEntityType()] = $parent;
            $entityHash[self::KEY_PARENT] = $parent;

            if (
                !isset($entityHash[PersonTemplate::TEMPLATE_TYPE]) &&
                $parent instanceof Person
            ) {
                $entityHash[PersonTemplate::TEMPLATE_TYPE] = $parent;
            }
        }

        if ($data->getParent()) {
            $entityHash = array_merge(
                $entityHash,
                $this->entityMapProvider->get($data->getParent(), $user, $params->applyAcl())
            );

            $entityHash[$data->getParent()->getEntityType()] = $data->getParent();
        }

        if ($data->getRelatedId() && $data->getRelatedType()) {
            $related = $this->entityManager->getEntityById($data->getRelatedType(), $data->getRelatedId());

            if (
                $related &&
                $params->applyAcl() &&
                !$this->aclManager->checkEntityRead($this->user, $related)
            ) {
                $related = null;
            }

            if ($related) {
                $entityHash[$related->getEntityType()] = $related;
            }
        }

        return [$entityHash, $data];
    }
}
