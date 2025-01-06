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

use Espo\Core\Utils\Metadata;

class Helper
{
    public function __construct(private Metadata $metadata)
    {}

    /**
     * Get field definitions by a type in metadata, "fields" key.
     *
     * @param array<string, mixed> $defs It can be a string or field definition from entityDefs.
     * @return ?array<string, mixed>
     */
    public function getFieldDefsByType($defs)
    {
        if (isset($defs['type'])) {
            return $this->metadata->get('fields.' . $defs['type']);
        }

        return null;
    }

    /**
     * @param array<string, mixed> $defs
     * @return ?array<string, mixed>
     */
    public function getFieldDefsInFieldMetadata($defs)
    {
        $fieldDefsByType = $this->getFieldDefsByType($defs);

        if (isset($fieldDefsByType['fieldDefs'])) {
            return $fieldDefsByType['fieldDefs'];
        }

        return null;
    }

    /**
     * Get link definition defined in 'fields' metadata.
     * In linkDefs can be used as value (e.g. "type": "hasChildren") and/or variables (e.g. "entityName": "{entity}").
     * Variables should be defined into fieldDefs (in 'entityDefs' metadata).
     *
     * @param string $entityType
     * @param array<string, mixed> $defs
     * @return ?array<string, mixed>
     */
    public function getLinkDefsInFieldMeta($entityType, $defs)
    {
        $fieldDefsByType = $this->getFieldDefsByType($defs);

        if (!isset($fieldDefsByType['linkDefs'])) {
            return null;
        }

        $linkFieldDefsByType = $fieldDefsByType['linkDefs'];

        foreach ($linkFieldDefsByType as &$paramValue) {
            if (preg_match('/{(.*?)}/', $paramValue, $matches)) {
                if (in_array($matches[1], array_keys($defs))) {
                    $value = $defs[$matches[1]];
                } else if (strtolower($matches[1]) == 'entity') {
                    $value = $entityType;
                }

                if (isset($value)) {
                    $paramValue = str_replace('{'.$matches[1].'}', $value, $paramValue);
                }
            }
        }

        return $linkFieldDefsByType;
    }
}
