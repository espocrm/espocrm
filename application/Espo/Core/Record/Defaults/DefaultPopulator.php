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

namespace Espo\Core\Record\Defaults;

use Espo\Core\Acl;
use Espo\Core\Acl\Permission;
use Espo\Core\Acl\Table as AclTable;
use Espo\Core\Currency\ConfigDataProvider as CurrencyConfigDataProvider;
use Espo\Core\Field\Link;
use Espo\Core\Field\LinkMultiple;
use Espo\Core\Field\LinkMultipleItem;
use Espo\Core\Name\Field;
use Espo\Core\ORM\Entity as CoreEntity;
use Espo\Core\ORM\Type\FieldType;
use Espo\Core\Utils\FieldUtil;
use Espo\Core\Utils\Metadata;
use Espo\Entities\User;
use Espo\Modules\Crm\Entities\Account;
use Espo\Modules\Crm\Entities\Contact;
use Espo\ORM\Defs\Params\FieldParam;
use Espo\ORM\Entity;
use Espo\ORM\EntityManager;
use RuntimeException;

/**
 * @implements Populator<Entity>
 */
class DefaultPopulator implements Populator
{
    public function __construct(
        private Acl $acl,
        private User $user,
        private FieldUtil $fieldUtil,
        private EntityManager $entityManager,
        private CurrencyConfigDataProvider $currencyConfig,
        private Metadata $metadata,
    ) {}

    public function populate(Entity $entity): void
    {
        $this->processAssignedUser($entity);
        $this->processDefaultTeam($entity);
        $this->processCurrency($entity);
        $this->processPortal($entity);
    }

    /**
     * If no edit access to assignedUser field.
     */
    private function isAssignedUserShouldBeSetWithSelf(string $entityType): bool
    {
        if ($this->user->isPortal()) {
            return false;
        }

        $defs = $this->entityManager->getDefs()->getEntity($entityType);

        if ($defs->tryGetField(Field::ASSIGNED_USER)?->getType() !== FieldType::LINK) {
            return false;
        }

        if (
            $this->acl->getPermissionLevel(Permission::ASSIGNMENT) === AclTable::LEVEL_NO &&
            !$this->user->isApi()
        ) {
            return true;
        }

        if (!$this->acl->checkField($entityType, Field::ASSIGNED_USER, AclTable::ACTION_EDIT)) {
            return true;
        }

        return false;
    }

    private function toAddDefaultTeam(Entity $entity): bool
    {
        if ($this->user->isPortal()) {
            return false;
        }

        if (!$this->user->getDefaultTeam()) {
            return false;
        }

        if (!$entity instanceof CoreEntity) {
            return false;
        }

        $entityType = $entity->getEntityType();

        $defs = $this->entityManager->getDefs()->getEntity($entityType);

        if ($defs->tryGetField(Field::TEAMS)?->getType() !== FieldType::LINK_MULTIPLE) {
            return false;
        }

        if ($entity->hasLinkMultipleId(Field::TEAMS, $this->user->getDefaultTeam()->getId())) {
            return false;
        }

        if ($this->acl->getPermissionLevel(Permission::ASSIGNMENT) === AclTable::LEVEL_NO) {
            return true;
        }

        if (!$this->acl->checkField($entityType, Field::TEAMS, AclTable::ACTION_EDIT)) {
            return true;
        }

        return false;
    }

    private function processCurrency(Entity $entity): void
    {
        $entityType = $entity->getEntityType();

        foreach ($this->fieldUtil->getEntityTypeFieldList($entityType) as $field) {
            $type = $this->fieldUtil->getEntityTypeFieldParam($entityType, $field, FieldParam::TYPE);

            if ($type !== FieldType::CURRENCY) {
                continue;
            }

            $currencyAttribute = $field . 'Currency';

            if ($entity->get($field) !== null && !$entity->get($currencyAttribute)) {
                $entity->set($currencyAttribute, $this->currencyConfig->getDefaultCurrency());
            }
        }
    }

    private function processDefaultTeam(Entity $entity): void
    {
        if (!$this->toAddDefaultTeam($entity)) {
            return;
        }

        $defaultTeamId = $this->user->getDefaultTeam()?->getId();

        if (!$defaultTeamId || !$entity instanceof CoreEntity) {
            throw new RuntimeException();
        }

        $entity->addLinkMultipleId(Field::TEAMS, $defaultTeamId);

        $teamsNames = $entity->get(Field::TEAMS . 'Names');

        if (!$teamsNames || !is_object($teamsNames)) {
            $teamsNames = (object)[];
        }

        $teamsNames->$defaultTeamId = $this->user->getDefaultTeam()?->getName();

        $entity->set(Field::TEAMS . 'Names', $teamsNames);
    }

    private function processAssignedUser(Entity $entity): void
    {
        if (!$this->isAssignedUserShouldBeSetWithSelf($entity->getEntityType())) {
            return;
        }

        $entity->set(Field::ASSIGNED_USER . 'Id', $this->user->getId());
        $entity->set(Field::ASSIGNED_USER . 'Name', $this->user->getName());
    }

    private function processPortal(Entity $entity): void
    {
        if (!$this->user->isPortal()) {
            return;
        }

        $this->processPortalAccount($entity);
        $this->processPortalContact($entity);
    }

    private function processPortalAccount(Entity $entity): void
    {
        /** @var ?string $link */
        $link = $this->metadata->get("aclDefs.{$entity->getEntityType()}.accountLink");

        if (!$link) {
            return;
        }

        $account = $this->user->getContact()?->getAccount();

        if (!$account) {
            return;
        }

        $this->processPortalRecord($entity, $link, $account);
    }

    private function processPortalContact(Entity $entity): void
    {
        /** @var ?string $link */
        $link = $this->metadata->get("aclDefs.{$entity->getEntityType()}.contactLink");

        if (!$link) {
            return;
        }

        $contact = $this->user->getContact();

        if (!$contact) {
            return;
        }

        $this->processPortalRecord($entity, $link, $contact);
    }

    private function processPortalRecord(Entity $entity, string $link, Account|Contact $record): void
    {
        if (!$entity instanceof CoreEntity) {
            return;
        }

        $fieldDefs = $this->entityManager
            ->getDefs()
            ->getEntity($entity->getEntityType())
            ->tryGetField($link);

        if (!$fieldDefs) {
            return;
        }

        if (
            $fieldDefs->getType() === FieldType::LINK ||
            $fieldDefs->getType() === FieldType::LINK_ONE
        ) {
            if ($entity->has($link . 'Id')) {
                return;
            }

            $entity->setValueObject($link, Link::create($record->getId(), $record->getName()));

            return;
        }

        if ($fieldDefs->getType() === FieldType::LINK_MULTIPLE) {
            if ($entity->has($link . 'Ids')) {
                return;
            }

            $linkMultiple = LinkMultiple::create([LinkMultipleItem::create($record->getId(), $record->getName())]);

            $entity->setValueObject($link, $linkMultiple);
        }
    }
}
