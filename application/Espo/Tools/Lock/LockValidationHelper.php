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

namespace Espo\Tools\Lock;

use Espo\Core\Acl\AssignmentChecker\Helper;
use Espo\Core\Exceptions\Conflict;
use Espo\Core\Exceptions\ConflictSilent;
use Espo\Core\Exceptions\Error\Body;
use Espo\Core\Name\Field;
use Espo\Core\Utils\FieldUtil;
use Espo\Core\Utils\Language;
use Espo\Core\Utils\Log;
use Espo\ORM\Defs;
use Espo\ORM\Defs\Params\FieldParam;
use Espo\ORM\Entity;

class LockValidationHelper
{
    private const string PARAM_NOT_LOCKABLE = 'notLockable';

    /** @var string[] */
    private array $ignoreFieldList = [
        Field::MODIFIED_AT,
        Field::MODIFIED_BY,
        Field::IS_LOCKED,
        Field::STREAM_UPDATED_AT,
    ];

    public function __construct(
        private LockMetadataProvider $lockMetadataProvider,
        private FieldUtil $fieldUtil,
        private Defs $defs,
        private Log $log,
        private Language $language,
        private Helper $assignmentHelper,
    ) {}

    /**
     * @throws Conflict
     */
    public function validateBeforeSave(Entity $entity): void
    {
        if ($entity->isNew()) {
            return;
        }

        if (!$this->lockMetadataProvider->isEnabled($entity->getEntityType())) {
            return;
        }

        if (!$entity->getFetched(Field::IS_LOCKED)) {
            return;
        }

        $changedField = $this->getChangedLockedField($entity);

        if ($changedField === null) {
            return;
        }

        $this->log->info("Cannot modify a locked record, '{field}' cannot be changed.", ['field' => $changedField]);

        $fieldLabel = $this->language->translateLabel($changedField, 'fields', $entity->getEntityType());

        throw ConflictSilent::createWithBody(
            'cannotModifyLockedRecord',
            Body::create()->withMessageTranslation('cannotModifyLockedRecord', data: ['field' => $fieldLabel])
        );
    }

    /**
     * @throws Conflict
     */
    public function validateBeforeRemove(Entity $entity): void
    {
        if (!$this->lockMetadataProvider->isEnabled($entity->getEntityType())) {
            return;
        }

        if (!$entity->get(Field::IS_LOCKED)) {
            return;
        }

        throw ConflictSilent::createWithBody(
            'cannotRemoveLockedRecord',
            Body::create()->withMessageTranslation('cannotRemoveLockedRecord')
        );
    }

    private function getChangedLockedField(Entity $entity): ?string
    {
        $entityType = $entity->getEntityType();

        $entityDefs = $this->defs->getEntity($entityType);

        $ignoreFieldList = $this->getIgnoreFieldList($entityType);

        foreach ($entityDefs->getFieldList() as $fieldDefs) {
            $field = $fieldDefs->getName();

            if (
                $fieldDefs->getParam(FieldParam::READ_ONLY) ||
                $fieldDefs->getParam(FieldParam::READ_ONLY_AFTER_CREATE)
            ) {
                continue;
            }

            if (in_array($field, $ignoreFieldList)) {
                continue;
            }

            if ($fieldDefs->getParam(self::PARAM_NOT_LOCKABLE)) {
                continue;
            }

            foreach ($this->fieldUtil->getActualAttributeList($entityType, $field) as $attribute) {
                if ($entity->isAttributeChanged($attribute)) {
                    return $field;
                }
            }
        }

        return null;
    }

    /**
     * @return string[]
     */
    private function getIgnoreFieldList(string $entityType): array
    {
        $ignoreFieldList = $this->ignoreFieldList;

        $entityDefs = $this->defs->getEntity($entityType);

        if ($this->assignmentHelper->hasCollaboratorsField($entityType)) {
            if ($this->assignmentHelper->hasAssignedUsersField($entityType)) {
                if (
                    $entityDefs->tryGetField(Field::ASSIGNED_USERS)
                        ?->getParam(self::PARAM_NOT_LOCKABLE)
                ) {
                    $ignoreFieldList[] = Field::COLLABORATORS;
                }
            } else if ($this->assignmentHelper->hasAssignedUserField($entityType)) {
                if (
                    $entityDefs->tryGetField(Field::ASSIGNED_USER)
                        ?->getParam(self::PARAM_NOT_LOCKABLE)
                ) {
                    $ignoreFieldList[] = Field::COLLABORATORS;
                }
            }
        }
        return $ignoreFieldList;
    }
}
