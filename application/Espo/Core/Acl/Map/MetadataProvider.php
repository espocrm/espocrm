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

namespace Espo\Core\Acl\Map;

use Espo\Core\{
    Utils\Metadata,
};

class MetadataProvider
{
    protected $type = 'acl';

    private $metadata;

    public function __construct(Metadata $metadata)
    {
        $this->metadata = $metadata;
    }

    /**
     * @return array<int, string>
     */
    public function getScopeList(): array
    {
        return array_keys($this->metadata->get('scopes') ?? []);
    }

    public function isScopeEntity(string $scope): bool
    {
        return (bool) $this->metadata->get(['scopes', $scope, 'entity']);
    }

    /**
     * @return array<int, string>
     */
    public function getScopeFieldList(string $scope): array
    {
        return array_keys($this->metadata->get(['entityDefs', $scope, 'fields']) ?? []);
    }

    /**
     * @return array<int, string>
     */
    public function getPermissionList(): array
    {
        $itemList = $this->metadata->get(['app', $this->type, 'valuePermissionList']) ?? [];

        return array_map(
            function (string $item): string {
                if (substr($item, -10) === 'Permission') {
                    return substr($item, 0, -10);
                }

                return $item;
            },
            $itemList
        );
    }
}
