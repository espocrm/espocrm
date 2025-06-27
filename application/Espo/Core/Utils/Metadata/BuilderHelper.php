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

namespace Espo\Core\Utils\Metadata;

use Espo\Core\Utils\Util;
use Espo\ORM\Defs\Params\FieldParam;

/**
 * @internal
 */
class BuilderHelper
{
    /**
     * A list of copy-from-parent params for metadata -> fields.
     *
     * @var string[]
     */
    private array $copiedDefParams = [
        'readOnly',
        'disabled',
        FieldParam::NOT_STORABLE,
        'layoutListDisabled',
        'layoutDetailDisabled',
        'layoutMassUpdateDisabled',
        'layoutFiltersDisabled',
        'directAccessDisabled',
        'directUpdateDisabled',
        'customizationDisabled',
        'importDisabled',
        'exportDisabled',
    ];

    private string $defaultFieldNaming = 'postfix';

    /**
     * Get additional field list based on field definition in metadata 'fields'.
     *
     * @param string $field
     * @param array<string, mixed> $params
     * @param array<string, mixed> $defs
     * @return ?array<string, mixed>
     * @internal
     */
    public function getAdditionalFields(string $field, array $params, array $defs): ?array
    {
        if (!$defs) {
            return null;
        }

        $type = $params['type'] ?? null;

        if (!$type) {
            return null;
        }

        $typeDefs = $defs[$type] ?? null;

        if (!$typeDefs) {
            return null;
        }

        /** @var ?array<string, mixed> $fields */
        $fields = $typeDefs['fields'] ?? null;
        /** @var string $naming */
        $naming = $typeDefs['naming'] ?? $this->defaultFieldNaming;;

        if (!is_array($fields)) {
            return null;
        }

        $copiedParams = array_intersect_key($params, array_flip($this->copiedDefParams));

        $output = [];

        foreach ($fields as $subField => $subParams) {
            $subName = Util::getNaming($field, $subField, $naming);

            $output[$subName] = array_merge($copiedParams, $subParams);

            // A trick to allow some fields to be combined with the main field.
            if (array_key_exists('detailLayoutIncompatibleFieldList', $output[$subName])) {
                continue;
            }

            $output[$subName]['detailLayoutIncompatibleFieldList'] = [$field];
        }

        return $output;
    }
}
