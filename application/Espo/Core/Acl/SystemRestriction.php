<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2026 EspoCRM, Inc.
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

namespace Espo\Core\Acl;

use Espo\Core\Acl\Exceptions\Restricted;
use Espo\Core\Utils\Metadata;
use Espo\Entities\AppLogRecord;
use Espo\Entities\ArrayValue;
use Espo\Entities\Extension;
use Espo\Entities\MassAction;
use Espo\Entities\PasswordChangeRequest;
use Espo\Entities\SystemData;
use Espo\Entities\TwoFactorCode;
use Espo\Entities\User;
use Espo\ORM\Entity;

/**
 * @since 10.0.0
 */
class SystemRestriction
{
    /** @var string[] */
    private array $writeForbiddenEntityTypeList = [
        Extension::ENTITY_TYPE,
        AppLogRecord::ENTITY_TYPE,
        PasswordChangeRequest::ENTITY_TYPE,
        TwoFactorCode::ENTITY_TYPE,
        SystemData::ENTITY_TYPE,
        MassAction::ENTITY_TYPE,
        ArrayValue::ENTITY_TYPE,
    ];

    public function __construct(
        private Metadata $metadata,
        private GlobalRestriction $globalRestriction,
    ) {}

    /**
     * @throws Restricted
     */
    public function assertUpdate(Entity $entity): void
    {
        $entityType = $entity->getEntityType();

        if (!$this->checkEntityTypeWrite($entity->getEntityType())) {
            throw new Restricted("Cannot write '$entityType' entity.");
        }

        if ($entity instanceof User) {
            $this->assertUserUpdate($entity);
        }
    }

    /**
     * @throws Restricted
     */
    public function assertRemoval(Entity $entity): void
    {
        $entityType = $entity->getEntityType();

        if (!$this->checkEntityTypeWrite($entity->getEntityType())) {
            throw new Restricted("Cannot remove '$entityType' entity.");
        }

        if ($entity instanceof User) {
            $this->assertRemovalUser($entity);
        }
    }

    public function checkEntityTypeWrite(string $entityType): bool
    {
        if (in_array($entityType, $this->writeForbiddenEntityTypeList)) {
            return false;
        }

        if ($this->metadata->get("entityAcl.$entityType.systemWriteForbidden")) {
            return false;
        }

        return true;
    }

    /**
     * @return string[]
     */
    private static function getUserRestrictedTypeList(): array
    {
        return [
            User::TYPE_SUPER_ADMIN,
            User::TYPE_SYSTEM,
        ];
    }

    /**
     * @throws Restricted
     */
    private function assertUserUpdate(User $entity): void
    {
        $restrictedTypeList = self::getUserRestrictedTypeList();

        if (
            $entity->isAttributeChanged(User::ATTR_TYPE) &&
            (
                in_array($entity->getFetched(User::ATTR_TYPE), $restrictedTypeList) ||
                in_array($entity->getType(), $restrictedTypeList)
            )
        ) {
            throw new Restricted("Cannot change user type.");
        }
    }

    /**
     * @throws Restricted
     */
    private function assertRemovalUser(User $entity): void
    {
        if (in_array($entity->getType(), self::getUserRestrictedTypeList())) {
            throw new Restricted("Cannot remove {$entity->getId()} user.");
        }
    }

    /**
     * @return string[]
     */
    public function getReadRestrictedAttributeList(string $entityType): array
    {
        $list1 = $this->globalRestriction
            ->getScopeRestrictedAttributeList($entityType, GlobalRestriction::TYPE_FORBIDDEN);

        $list2 = $this->globalRestriction
            ->getScopeRestrictedAttributeList($entityType, GlobalRestriction::TYPE_INTERNAL);

        $list = array_merge($list1, $list2);
        $list = array_unique($list);

        return array_values($list);
    }

    /**
     * @return string[]
     */
    public function getWriteRestrictedAttributeList(string $entityType): array
    {
        return $this->globalRestriction
            ->getScopeRestrictedAttributeList($entityType, GlobalRestriction::TYPE_FORBIDDEN);
    }

    public function checkLinkWrite(string $entityType, string $link): bool
    {
        $forbiddenList = $this->globalRestriction
            ->getScopeRestrictedLinkList($entityType, GlobalRestriction::TYPE_FORBIDDEN);

        return !in_array($link, $forbiddenList);
    }

    public function checkLinkRead(string $entityType, string $link): bool
    {
        $internalList = $this->globalRestriction
            ->getScopeRestrictedLinkList($entityType, GlobalRestriction::TYPE_INTERNAL);

        $forbiddenList = $this->globalRestriction
            ->getScopeRestrictedLinkList($entityType, GlobalRestriction::TYPE_FORBIDDEN);

        return !in_array($link, $internalList) && !in_array($link, $forbiddenList);
    }

    public function checkAttributeRead(string $entityType, string $attribute): bool
    {
        $internalList = $this->globalRestriction
            ->getScopeRestrictedAttributeList($entityType, GlobalRestriction::TYPE_INTERNAL);

        $forbiddenList = $this->globalRestriction
            ->getScopeRestrictedAttributeList($entityType, GlobalRestriction::TYPE_FORBIDDEN);

        return !in_array($attribute, $internalList) && !in_array($attribute, $forbiddenList);
    }

    public function checkFieldRead(string $entityType, string $field): bool
    {
        $internalList = $this->globalRestriction
            ->getScopeRestrictedFieldList($entityType, GlobalRestriction::TYPE_INTERNAL);

        $forbiddenList = $this->globalRestriction
            ->getScopeRestrictedFieldList($entityType, GlobalRestriction::TYPE_FORBIDDEN);

        return !in_array($field, $internalList) && !in_array($field, $forbiddenList);
    }

    public function checkFieldWrite(string $entityType, string $field): bool
    {
        $forbiddenList = $this->globalRestriction
            ->getScopeRestrictedFieldList($entityType, GlobalRestriction::TYPE_FORBIDDEN);

        return !in_array($field, $forbiddenList);
    }

    public function checkAttributeWrite(string $entityType, string $attribute): bool
    {
        $forbiddenList = $this->globalRestriction
            ->getScopeRestrictedAttributeList($entityType, GlobalRestriction::TYPE_FORBIDDEN);

        return !in_array($attribute, $forbiddenList);
    }
}
