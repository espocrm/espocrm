<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2021 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
 * Website: https://www.espocrm.com
 *
 * EspoCRM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * EspoCRM is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with EspoCRM. If not, see http://www.gnu.org/licenses/.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

namespace Espo\Tools\EmailTemplate;

use Espo\ORM\EntityManager;
use Espo\ORM\Entity;

use Espo\Core\AclManager;
use Espo\Core\Record\ServiceContainer;
use Espo\Core\Utils\Config;
use Espo\Core\FileStorage\Manager as FileStorageManager;
use Espo\Core\Entities\Person;
use Espo\Core\Htmlizer\HtmlizerFactory as HtmlizerFactory;
use Espo\Core\Htmlizer\Htmlizer;
use Espo\Core\Acl\GlobalRestricton;
use Espo\Core\Utils\DateTime as DateTimeUtil;

use Espo\Entities\EmailTemplate;
use Espo\Entities\User;
use Espo\Entities\Attachment;
use Espo\Entities\EmailAddress;

use Espo\Repositories\EmailAddress as EmailAddressRepository;

use Exception;
use DateTime;
use DateTimezone;

class Processor
{
    private $formatter;

    private $entityManager;

    private $aclManager;

    private $recordServiceContainer;

    private $config;

    private $fileStorageManager;

    private $user;

    private $htmlizerFactory;

    private $dateTime;

    public function __construct(
        Formatter $formatter,
        EntityManager $entityManager,
        AclManager $aclManager,
        ServiceContainer $recordServiceContainer,
        Config $config,
        FileStorageManager $fileStorageManager,
        User $user,
        HtmlizerFactory $htmlizerFactory,
        DateTimeUtil $dateTime
    ) {
        $this->formatter = $formatter;
        $this->entityManager = $entityManager;
        $this->aclManager = $aclManager;
        $this->recordServiceContainer = $recordServiceContainer;
        $this->config = $config;
        $this->fileStorageManager = $fileStorageManager;
        $this->user = $user;
        $this->htmlizerFactory = $htmlizerFactory;
        $this->dateTime = $dateTime;
    }

    public function process(EmailTemplate $template, Params $params, Data $data): Result
    {
        $entityHash = $data->getEntityHash();

        $user = $data->getUser() ?? $this->user;

        if (!isset($entityHash['User'])) {
            $entityHash['User'] = $user;
        }

        $foundByAddressEntity = null;

        if ($data->getEmailAddress()) {
            $foundByAddressEntity = $this->getEmailAddressRepository()
                ->getEntityByAddress(
                    $data->getEmailAddress(),
                    null,
                    ['Contact', 'Lead', 'Account', 'User']
                );
        }

        if ($foundByAddressEntity) {
            if ($foundByAddressEntity instanceof Person) {
                $entityHash['Person'] = $foundByAddressEntity;
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
            $parent = $this->entityManager->getEntity($data->getParentType(), $data->getParentId());

            if ($parent) {
                $service = $this->recordServiceContainer->get($data->getParentType());

                $service->loadAdditionalFields($parent);

                $data = $data->withParent($parent);
            }
        }

        if ($data->getParent()) {
            $parent = $data->getParent();

            $entityHash[$parent->getEntityType()] = $parent;
            $entityHash['Parent'] = $parent;

            if (!isset($entityHash['Person']) && $parent instanceof Person) {
                $entityHash['Person'] = $parent;
            }
        }

        if ($data->getRelatedId() && $data->getRelatedType()) {
            $related = $this->entityManager->getEntity($data->getRelatedType(), $data->getRelatedId());

            if ($related) {
                $entityHash[$related->getEntityType()] = $related;
            }
        }

        $subject = $template->get('subject') ?? '';
        $body = $template->get('body') ?? '';

        $parent = $entityHash['Parent'] ?? null;

        $htmlizer = null;

        if ($parent && !$this->config->get('emailTemplateHtmlizerDisabled')) {
            $handlebarsInSubject = strpos($subject, '{{') !== false && strpos($subject, '}}') !== false;
            $handlebarsInBody = strpos($body, '{{') !== false && strpos($body, '}}') !== false;

            if ($handlebarsInSubject || $handlebarsInBody) {
                $htmlizer = $this->createHtmlizer($params, $user);

                if ($handlebarsInSubject) {
                    $subject = $htmlizer->render($parent, $subject);
                }

                if ($handlebarsInBody) {
                    $body = $htmlizer->render($parent, $body);
                }
            }
        }

        foreach ($entityHash as $type => $entity) {
            $subject = $this->processText(
                $type,
                $entity,
                $subject,
                $user,
                false,
                null,
                !$params->applyAcl()
            );
        }

        foreach ($entityHash as $type => $entity) {
            $body = $this->processText(
                $type,
                $entity,
                $body,
                $user,
                false,
                null,
                !$params->applyAcl()
            );
        }

        $attachmentList = $params->copyAttachments() ?
            $this->copyAttachments($template) :
            [];

        return new Result(
            $subject,
            $body,
            $template->get('isHtml'),
            $attachmentList
        );
    }

    private function processText(
        string $type,
        Entity $entity,
        string $text,
        User $user,
        bool $skipLinks = false,
        ?string $prefixLink = null,
        bool $skipAcl = false
    ): string {

        $attributeList = $entity->getAttributeList();

        $forbiddenAttributeList = [];

        if (!$skipAcl) {
            $forbiddenAttributeList = array_merge(
                $this->aclManager->getScopeForbiddenAttributeList($user, $entity->getEntityType()),
                $this->aclManager->getScopeRestrictedAttributeList(
                    $entity->getEntityType(),
                    [
                        GlobalRestricton::TYPE_FORBIDDEN,
                        GlobalRestricton::TYPE_INTERNAL,
                        GlobalRestricton::TYPE_ONLY_ADMIN,
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

            $value = $this->formatter->formatAttributeValue($entity, $attribute);

            if (is_null($value)) {
                continue;
            }

            $variableName = $attribute;

            if (!is_null($prefixLink)) {
                $variableName = $prefixLink . '.' . $attribute;
            }

            $text = str_replace('{' . $type . '.' . $variableName . '}', $value, $text);
        }

        if (!$skipLinks && $entity->getId()) {
            $text = $this->processLinks(
                $type,
                $entity,
                $text,
                $user,
                $skipAcl
            );
        }

        $now = new DateTime('now', new DateTimezone($this->config->get('timeZone')));

        $replaceData = [
            'today' => $this->dateTime->getTodayString(),
            'now' => $this->dateTime->getNowString(),
            'currentYear' => $now->format('Y'),
        ];

        foreach ($replaceData as $key => $value) {
            $text = str_replace('{' . $key . '}', $value, $text);
        }

        return $text;
    }

    private function processLinks(
        string $type,
        Entity $entity,
        string $text,
        User $user,
        bool $skipAcl
    ): string {

        $forbiddenLinkList = $skipAcl ?
            $this->aclManager->getScopeRestrictedLinkList(
                $entity->getEntityType(),
                [
                    GlobalRestricton::TYPE_FORBIDDEN,
                    GlobalRestricton::TYPE_INTERNAL,
                    GlobalRestricton::TYPE_ONLY_ADMIN,
                ]
            ) :
            [];

        foreach ($entity->getRelationList() as $relation) {
            if (in_array($relation, $forbiddenLinkList)) {
                continue;
            }

            $relationType = $entity->getRelationType($relation);

            $relationTypeIsOk =
                $relationType === Entity::BELONGS_TO ||
                $relationType === Entity::BELONGS_TO_PARENT;

            if (!$relationTypeIsOk) {
                continue;
            }

            $relatedEntity = $this->entityManager
                ->getRDBRepository($entity->getEntityType())
                ->getRelation($entity, $relation)
                ->findOne();

            if (!$relatedEntity) {
                continue;
            }

            try {
                $hasAccess = $this->aclManager->checkEntityRead($user, $relatedEntity);
            }
            catch (Exception $e) {
                continue;
            }

            if (!$hasAccess) {
                continue;
            }

            $text = $this->processText(
                $type,
                $relatedEntity,
                $text,
                $user,
                true,
                $relation,
                $skipAcl
            );
        }

        return $text;
    }

    /**
     * @return Attachment[]
     */
    private function copyAttachments(EmailTemplate $template): array
    {
        $copiedAttachmentList = [];

        /** @var iterable<Attachment> $attachmentList */
        $attachmentList = $this->entityManager
            ->getRDBRepository('EmailTemplate')
            ->getRelation($template, 'attachments')
            ->find();

        foreach ($attachmentList as $attachment) {
            /** @var Attachment $clone */
            $clone = $this->entityManager->getEntity('Attachment');

            $data = $attachment->getValueMap();

            unset($data->parentType);
            unset($data->parentId);
            unset($data->id);

            $clone->set($data);
            $clone->set('sourceId', $attachment->getSourceId());
            $clone->set('storage', $attachment->get('storage'));

            if (!$this->fileStorageManager->exists($attachment)) {
                continue;
            }

            $this->entityManager->saveEntity($clone);

            $copiedAttachmentList[] = $clone;
        }

        return $copiedAttachmentList;
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
}
