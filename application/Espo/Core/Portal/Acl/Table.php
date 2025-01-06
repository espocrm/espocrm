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

namespace Espo\Core\Portal\Acl;

use Espo\Core\Acl\Table\DefaultTable as BaseTable;

use stdClass;

class Table extends BaseTable
{
    public const LEVEL_ACCOUNT = 'account';
    public const LEVEL_CONTACT = 'contact';

    protected string $type = 'aclPortal';

    /**
     * @var string[]
     */
    protected $levelList = [
        self::LEVEL_YES,
        self::LEVEL_ALL,
        self::LEVEL_ACCOUNT,
        self::LEVEL_CONTACT,
        self::LEVEL_OWN,
        self::LEVEL_NO,
    ];

    /**
     * @return string[]
     */
    protected function getScopeWithAclList(): array
    {
        $scopeList = [];

        $scopes = $this->metadata->get('scopes');

        foreach ($scopes as $scope => $item) {
            if (empty($item['acl'])) {
                continue;
            }

            if (empty($item['aclPortal'])) {
                continue;
            }

            $scopeList[] = $scope;
        }

        return $scopeList;
    }

    protected function applyDefault(stdClass &$table, stdClass &$fieldTable): void
    {
        parent::applyDefault($table, $fieldTable);

        foreach ($this->getScopeList() as $scope) {
            if (!isset($table->$scope)) {
                $table->$scope = false;
            }
        }
    }

    protected function applyDisabled(stdClass $table, stdClass $fieldTable): void
    {
        foreach ($this->getScopeList() as $scope) {
            $item = $this->metadata->get(['scopes', $scope]) ?? [];

            if (!empty($item['disabled']) || !empty($item['portalDisabled'])) {
                $table->$scope = false;

                unset($fieldTable->$scope);
            }
        }
    }
}
