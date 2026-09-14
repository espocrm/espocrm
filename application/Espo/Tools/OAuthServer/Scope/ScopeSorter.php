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

namespace Espo\Tools\OAuthServer\Scope;

use Espo\Core\Acl\Scope;
use stdClass;

class ScopeSorter
{
    /**
     * @var array<string, int>
     */
    private array $priority = [
        Scope::GLOBAL => 0,
        Scope::ADMIN => 1,
    ];

    /**
     * @param (stdClass & object{name: string, label: string})[] $items
     * @return (stdClass & object{name: string, label: string})[]
     */
    public function sortDataItems(array $items): array
    {
        usort($items, function ($a, $b) {
            $aPriority = $this->priority[$a->name] ?? PHP_INT_MAX;
            $bPriority = $this->priority[$b->name] ?? PHP_INT_MAX;

            return $aPriority <=> $bPriority ?: strcasecmp($a->label, $b->label);
        });

        return $items;
    }

    /**
     * @param non-empty-string[] $scopes
     * @return non-empty-string[]
     */
    public function sort(array $scopes): array
    {
        usort($scopes, function ($a, $b) {
            $aPriority = $this->priority[$a] ?? PHP_INT_MAX;
            $bPriority = $this->priority[$b] ?? PHP_INT_MAX;

            return $aPriority <=> $bPriority ?: strcmp($a, $b);
        });

        return $scopes;
    }
}
