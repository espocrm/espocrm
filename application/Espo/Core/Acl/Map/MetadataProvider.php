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

namespace Espo\Core\Acl\Map;

use Espo\Core\Utils\Metadata;

class MetadataProvider
{
    protected string $type = 'acl';

    public function __construct(private Metadata $metadata)
    {}

    /**
     * @return string[]
     */
    public function getScopeList(): array
    {
        /** @var string[] */
        return array_keys($this->metadata->get('scopes') ?? []);
    }

    public function isScopeEntity(string $scope): bool
    {
        return (bool) $this->metadata->get(['scopes', $scope, 'entity']);
    }

    /**
     * @return string[]
     */
    public function getScopeFieldList(string $scope): array
    {
        /** @var string[] */
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
                if (str_ends_with($item, 'Permission')) {
                    return substr($item, 0, -10);
                }

                return $item;
            },
            $itemList
        );
    }
}
