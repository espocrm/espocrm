<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2023 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

namespace Espo\Core\Record\Access;

use Espo\Core\Acl;
use Espo\Core\Acl\LinkChecker;
use Espo\Core\Acl\LinkChecker\LinkCheckerFactory;
use Espo\Core\Acl\Table as AclTable;
use Espo\Core\Exceptions\Error\Body as ErrorBody;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\ForbiddenSilent;
use Espo\Core\InjectableFactory;
use Espo\Core\Utils\Metadata;
use Espo\Entities\User;
use Espo\Modules\Crm\Entities\Account;
use Espo\Modules\Crm\Entities\Contact;
use Espo\ORM\Defs\RelationDefs;
use Espo\ORM\Entity;
use Espo\ORM\EntityManager;

/**
 * Check access for record linking.
 */
class LinkCheck
{
    /** @var array<string, LinkChecker<Entity, Entity>>> */
    private $linkCheckerCache = [];

    /**
     * @param string[] $noEditAccessRequiredLinkList
     */
    public function __construct(
        private EntityManager $entityManager,
        private Acl $acl,
        private Metadata $metadata,
        private InjectableFactory $injectableFactory,
        private User $user,
        private array $noEditAccessRequiredLinkList = [],
        private bool $noEditAccessRequiredForLink = false
    ) {}

    /**
     * Checks relation fields set in an entity.
     *
     * @throws Forbidden
     */
    public function process(Entity $entity): void
    {
        $this->processLinkMultiple($entity);
    }

    /**
     * @throws Forbidden
     */
    private function processLinkMultiple(Entity $entity): void
    {
        $entityType = $entity->getEntityType();

        $entityDefs = $this->entityManager
            ->getDefs()
            ->getEntity($entityType);

        $typeList = [
            Entity::HAS_MANY,
            Entity::MANY_MANY,
            Entity::HAS_CHILDREN,
        ];

        foreach ($entityDefs->getRelationList() as $relationDefs) {
            $name = $relationDefs->getName();

            if (!in_array($relationDefs->getType(), $typeList)) {
                continue;
            }

            $attribute = $name . 'Ids';

            if (
                !$entityDefs->hasAttribute($attribute) ||
                !$entity->isAttributeChanged($attribute)
            ) {
                continue;
            }

            /** @var string[] $ids */
            $ids = $entity->get($attribute) ?? [];
            /** @var string[] $oldIds */
            $oldIds = $entity->getFetched($attribute) ?? [];

            $ids = array_values(array_diff($ids, $oldIds));
            $removedIds = array_values(array_diff($oldIds, $ids));

            if ($ids === [] && $removedIds === []) {
                continue;
            }

            $hasLinkMultiple =
                $entityDefs->hasField($name) &&
                (
                    $entityDefs->getField($name)->getType() === 'linkMultiple' ||
                    $entityDefs->getField($name)->getType() === 'attachmentMultiple'
                );

            if (
                !$hasLinkMultiple &&
                in_array($name, $this->acl->getScopeForbiddenLinkList($entityType, AclTable::ACTION_EDIT))
            ) {
                throw ForbiddenSilent::createWithBody(
                    "No access to link {$name}.",
                    ErrorBody::create()
                        ->withMessageTranslation('cannotRelateForbiddenLink', null, ['link' => $name])
                        ->encode()
                );
            }

            if ($ids === []) {
                continue;
            }

            foreach ($ids as $id) {
                $this->processLinkedRecordsCheckItem($entity, $relationDefs, $id);
            }
        }
    }

    /**
     * @throws Forbidden
     */
    private function processLinkedRecordsCheckItem(Entity $entity, RelationDefs $defs, string $id): void
    {
        $entityType = $entity->getEntityType();
        $link = $defs->getName();

        if (!$defs->hasForeignEntityType()) {
            return;
        }

        if ($this->metadata->get(['recordDefs', $entityType, 'relationships', $link, 'linkCheckDisabled'])) {
            return;
        }

        $foreignEntityType = $defs->getForeignEntityType();

        $foreignEntity = $this->entityManager->getEntityById($foreignEntityType, $id);

        if (!$foreignEntity) {
            throw ForbiddenSilent::createWithBody(
                "Can't relate with non-existing record. entity type: $entityType, link: $link.",
                ErrorBody::create()
                    ->withMessageTranslation(
                        'cannotRelateNonExisting', null, ['foreignEntityType' => $foreignEntityType])
                    ->encode()
            );
        }

        $this->linkForeignAccessCheck($entityType, $link, $foreignEntity, true);
        $this->linkEntityAccessCheck($entity, $foreignEntity, $link);
    }

    /**
     * Check access to a specific link.
     *
     * @throws Forbidden
     */
    public function processLink(Entity $entity, string $link): void
    {
        $entityType = $entity->getEntityType();

        /** @var AclTable::ACTION_*|null $action */
        $action = $this->metadata
            ->get(['recordDefs', $entityType, 'relationships', $link, 'linkRequiredAccess']) ??
            null;

        if (!$action) {
            $action = $this->noEditAccessRequiredForLink ?
                AclTable::ACTION_READ :
                AclTable::ACTION_EDIT;
        }

        if (!$this->acl->check($entity, $action)) {
            throw ForbiddenSilent::createWithBody(
                "No record access for link operation ({$entityType}:{$link}).",
                ErrorBody::create()
                    ->withMessageTranslation('noAccessToRecord', null, ['action' => $action])
                    ->encode()
            );
        }
    }

    /**
     * Check link access for a specific foreign entity.
     * @throws Forbidden
     */
    public function processLinkForeign(Entity $entity, string $link, Entity $foreignEntity): void
    {
        $this->linkForeignAccessCheck($entity->getEntityType(), $link, $foreignEntity);
        $this->linkEntityAccessCheck($entity, $foreignEntity, $link);
    }

    /**
     * @throws Forbidden
     */
    private function linkForeignAccessCheck(
        string $entityType,
        string $link,
        Entity $foreignEntity,
        bool $fromUpdate = false
    ): void {

        $action = in_array($link, $this->noEditAccessRequiredLinkList) ?
            AclTable::ACTION_READ : null;

        if (!$action) {
            /** @var AclTable::ACTION_* $action */
            $action = $this->metadata
                ->get(['recordDefs', $entityType, 'relationships', $link, 'linkRequiredForeignAccess']) ??
                AclTable::ACTION_EDIT;
        }

        if (
            $this->metadata
                ->get(['recordDefs', $entityType, 'relationships', $link, 'linkForeignAccessCheckDisabled'])
        ) {
            return;
        }

        $fieldDefs = $fromUpdate ?
            $this->entityManager
                ->getDefs()
                ->getEntity($entityType)
                ->tryGetField($link) :
            null;

        if (
            $fromUpdate &&
            $fieldDefs &&
            in_array($fieldDefs->getType(), ['linkMultiple', 'attachmentMultiple'])
        ) {
            $action = AclTable::ACTION_READ;

            // Allow defaults.
            $defaultAttributes = (object) ($fieldDefs->getParam('defaultAttributes') ?? []);
            $attribute = $link . 'Ids';
            /** @var string[] $defaultIds */
            $defaultIds = $defaultAttributes->$attribute ?? [];

            if (in_array($foreignEntity->getId(), $defaultIds)) {
                return;
            }
        }

        if ($this->acl->check($foreignEntity, $action)) {
            return;
        }

        if ($this->user->isPortal() && $action === AclTable::ACTION_READ) {
            if (
                $foreignEntity->getEntityType() === Account::ENTITY_TYPE &&
                $this->user->getAccounts()->hasId($foreignEntity->getId())
            ) {
                return;
            }

            if (
                $foreignEntity->getEntityType() === Contact::ENTITY_TYPE &&
                $this->user->getContactId() === $foreignEntity->getId()
            ) {
                return;
            }
        }

        $body = ErrorBody::create();

        $body = $fromUpdate ?
            $body->withMessageTranslation('cannotRelateForbidden', null, [
                'foreignEntityType' => $foreignEntity->getEntityType(),
                'action' => $action,
            ]) :
            $body->withMessageTranslation('noAccessToForeignRecord', null, ['action' => $action]);

        throw ForbiddenSilent::createWithBody(
            "No foreign record access for link operation ({$entityType}:{$link}).",
            $body->encode()
        );
    }

    /**
     * @throws Forbidden
     */
    private function linkEntityAccessCheck(Entity $entity, Entity $foreignEntity, string $link): void
    {
        $entityType = $entity->getEntityType();

        $checker = $this->getLinkChecker($entityType, $link);

        if (!$checker) {
            return;
        }

        if ($checker->check($this->user, $entity, $foreignEntity)) {
            return;
        }

        throw ForbiddenSilent::createWithBody(
            "No access for link operation ({$entityType}:{$link}).",
            ErrorBody::create()
                ->withMessageTranslation('noLinkAccess')
                ->encode()
        );
    }

    /**
     * @return ?LinkChecker<Entity, Entity>
     */
    private function getLinkChecker(string $entityType, string $link): ?LinkChecker
    {
        $key = $entityType . '_' . $link;

        if (array_key_exists($key, $this->linkCheckerCache)) {
            return $this->linkCheckerCache[$key];
        }

        $factory = $this->injectableFactory->create(LinkCheckerFactory::class);

        if (!$factory->isCreatable($entityType, $link)) {
            return null;
        }

        $checker = $factory->create($entityType, $link);

        $this->linkCheckerCache[$link] = $checker;

        return $checker;
    }
}
