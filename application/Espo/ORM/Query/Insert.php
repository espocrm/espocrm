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

namespace Espo\ORM\Query;

use RuntimeException;

/**
 * Insert parameters.
 *
 * Immutable.
 */
class Insert implements Query
{
    use BaseTrait;

    /**
     * @param array<string, mixed> $params
     */
    private function validateRawParams(array $params): void
    {
        $into = $params['into'] ?? null;

        if (!$into || !is_string($into)) {
            throw new RuntimeException("Bad or missing 'into' parameter.");
        }

        $columns = $params['columns'] ?? [];

        if (!is_array($columns)) {
            throw new RuntimeException("Bad 'columns' parameter.");
        }

        $values = $params['values'] ?? [];

        if (!is_array($values)) {
            throw new RuntimeException("Bad 'values' parameter.");
        }

        $updateSet = $params['updateSet'] ?? null;

        if ($updateSet && !is_array($updateSet)) {
            throw new RuntimeException("Bad 'updateSet' parameter.");
        }
    }
}
