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

namespace Espo\Classes\RecordHooks\Role;

use Espo\Core\Acl\Table;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Portal\Acl\Table as TablePortal;
use Espo\Core\Record\Hook\SaveHook;
use Espo\Core\Utils\Metadata;
use Espo\Entities\PortalRole;
use Espo\Entities\Role;
use Espo\ORM\Entity;
use stdClass;

/**
 * @noinspection PhpUnused
 * @implements SaveHook<Role|PortalRole>
 */
class BeforeSaveValidate implements SaveHook
{
    /** @var string[] */
    private array $levelList = [
        Table::LEVEL_YES,
        Table::LEVEL_ALL,
        Table::LEVEL_TEAM,
        Table::LEVEL_OWN,
        Table::LEVEL_NO,
    ];

    /** @var string[] */
    private array $portalLevelList = [
        Table::LEVEL_YES,
        Table::LEVEL_ALL,
        TablePortal::LEVEL_ACCOUNT,
        TablePortal::LEVEL_CONTACT,
        Table::LEVEL_OWN,
        Table::LEVEL_NO,
    ];

    public function __construct(
        private Metadata $metadata
    ) {}

    public function process(Entity $entity): void
    {
        $this->validateData($entity);
        $this->validateFieldData($entity);
    }

    /**
     * @throws BadRequest
     */
    private function validateData(Role|PortalRole $entity): void
    {
        if ($entity->get('data') === null) {
            return;
        }

        /** @var array<string, mixed> $data */
        $data = get_object_vars($entity->get('data'));

        foreach ($data as $scope => $item) {
            if (!is_bool($item) && !$item instanceof stdClass) {
                throw new BadRequest("Bad data. Should be bool or object.");
            }

            $this->validateDataItem($scope, $entity, $item);
        }
    }

    /**
     * @throws BadRequest
     */
    private function validateDataItem(string $scope, Role|PortalRole $entity, bool|stdClass $item): void
    {
        $key = $entity instanceof PortalRole ?
            'aclPortal' : 'acl';

        $type = $this->metadata->get("scopes.$scope.$key");

        if ($type === Table\ScopeDataType::BOOLEAN) {
            if (!is_bool($item)) {
                throw new BadRequest("Bad data. Value for *$scope* should be be boolean.");
            }

            return;
        }

        if ($type === null) {
            throw new BadRequest("Bad data. Scope *$scope* is not allowed.");
        }

        if ($item === false) {
            return;
        }

        if (is_bool($item)) {
            throw new BadRequest("Bad data. Value for *$scope* should be be false or object.");
        }

        $actions = [
            Table::ACTION_CREATE,
            Table::ACTION_READ,
            Table::ACTION_EDIT,
            Table::ACTION_DELETE,
            Table::ACTION_STREAM,
        ];

        $levels = $entity instanceof PortalRole ?
            $this->portalLevelList : $this->levelList;

        foreach ($actions as $action) {
            if (!property_exists($item, $action)) {
                continue;
            }

            $level = $item->$action;

            if (!in_array($level, $levels)) {
                throw new BadRequest("Level `$level` is not allowed for action *$action* for *$scope*.");
            }
        }
    }

    /**
     * @throws BadRequest
     */
    private function validateFieldData(Role|PortalRole $entity): void
    {
        if ($entity->get('fieldData') === null) {
            return;
        }

        /** @var array<string, mixed> $data */
        $data = get_object_vars($entity->get('fieldData'));

        foreach ($data as $scope => $item) {
            if (!$item instanceof stdClass) {
                throw new BadRequest("Bad field-level data. Should be object.");
            }

            $this->validateFieldDataItem($scope, $entity, $item);
        }
    }

    /**
     * @throws BadRequest
     */
    private function validateFieldDataItem(string $scope, PortalRole|Role $entity, stdClass $item): void
    {
        $disabledKey = $entity instanceof PortalRole ? 'aclPortalFieldLevelDisabled' : 'aclFieldLevelDisabled';
        $key = $entity instanceof PortalRole ? 'aclPortal' : 'acl';

        if (
            !$this->metadata->get("scopes.$scope.entity") ||
            !$this->metadata->get("scopes.$scope.$key") ||
            $this->metadata->get("scopes.$scope.$disabledKey")
        ) {
            throw new BadRequest("Bad field-level data. Scope *$scope* is not allowed.");
        }

        /** @var array<string, mixed> $data */
        $data = get_object_vars($item);

        foreach ($data as $field => $fieldItem) {
            if (!$fieldItem instanceof stdClass) {
                throw new BadRequest("Data for field *$field*, scope *$scope* should be object.");
            }

            $this->validateFieldDataItemItem($scope, $field, $fieldItem);
        }
    }

    /**
     * @throws BadRequest
     */
    private function validateFieldDataItemItem(string $scope, string $field, stdClass $item): void
    {
        if (!$this->metadata->get("entityDefs.$scope.fields.$field")) {
            throw new BadRequest("Field *$field* does not exist in *$scope*.");
        }

        $actions = [
            Table::ACTION_READ,
            Table::ACTION_EDIT,
        ];

        $levels = [
            Table::LEVEL_YES,
            Table::LEVEL_NO,
        ];

        foreach ($actions as $action) {
            if (!property_exists($item, $action)) {
                continue;
            }

            $level = $item->$action;

            if (!in_array($level, $levels)) {
                throw new BadRequest("Level `$level` is not allowed for *$scope*, field *$field*.");
            }
        }
    }
}
