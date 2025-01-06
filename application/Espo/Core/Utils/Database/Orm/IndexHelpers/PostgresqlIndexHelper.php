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

namespace Espo\Core\Utils\Database\Orm\IndexHelpers;

use Espo\Core\Utils\Database\Orm\IndexHelper;
use Espo\Core\Utils\Util;
use Espo\ORM\Defs\IndexDefs;

class PostgresqlIndexHelper implements IndexHelper
{
    private const MAX_LENGTH = 59;

    public function composeKey(IndexDefs $defs, string $entityType): string
    {
        $name = $defs->getName();
        $prefix = $defs->isUnique() ? 'UNIQ' : 'IDX';

        $parts = [
            $prefix,
            strtoupper(Util::toUnderScore($entityType)),
            strtoupper(Util::toUnderScore($name)),
        ];

        $key = implode('_', $parts);

        return self::decreaseLength($key);
    }

    private static function decreaseLength(string $key): string
    {
        if (strlen($key) <= self::MAX_LENGTH) {
            return $key;
        }

        $list = explode('_', $key);

        $maxItemLength = 0;
        foreach ($list as $item) {
            if (strlen($item) > $maxItemLength) {
                $maxItemLength = strlen($item);
            }
        }
        $maxItemLength--;

        $list = array_map(
            fn ($item) => substr($item, 0, min($maxItemLength, strlen($item))),
            $list
        );

        $key = implode('_', $list);

        return self::decreaseLength($key);
    }
}
