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

namespace Espo\Tools\OpenApi\FieldSchemaBuilders;

use Espo\Tools\OpenApi\Type;
use Espo\Core\ORM\Type\FieldType;
use Espo\ORM\Defs;
use Espo\ORM\Defs\Params\FieldParam;
use Espo\Tools\OpenApi\FieldSchemaBuilder;
use Espo\Tools\OpenApi\FieldSchemaResult;
use Espo\Tools\OpenApi\Util\EnumOptionsProvider;

class LinkMultipleType implements FieldSchemaBuilder
{
    public function __construct(
        private Defs $defs,
        private EnumOptionsProvider $enumOptionsProvider,
    ) {}

    public function build(string $entityType, string $field): FieldSchemaResult
    {
        $fieldDefs = $this->defs->getEntity($entityType)->getField($field);
        $linkDefs = $this->defs->getEntity($entityType)->tryGetRelation($field);

        $idsSchema = (object) [
            'type' => Type::ARRAY,
            'items' => [
                'type' => Type::STRING,
            ],
        ];

        $idsSchema->readOnly = $fieldDefs->getParam(FieldParam::READ_ONLY) ?? false;

        $foreignEntityType = null;

        if ($linkDefs && $linkDefs->tryGetForeignEntityType()) {
            $foreignEntityType = $linkDefs->tryGetForeignEntityType();

            $idsSchema->description = "IDs of records of the $foreignEntityType type.";
        }

        if (!$fieldDefs->getParam(FieldParam::REQUIRED)) {
            $idsSchema->type = [
                $idsSchema->type,
                Type::NULL,
            ];
        }

        $namesSchema = (object) [];
        $namesSchema->type = Type::OBJECT;
        $namesSchema->additionalProperties = [
            'type' => Type::STRING,
        ];
        $namesSchema->readOnly = true;
        $namesSchema->description = 'An {ID => record name} map.';

        $namesSchema->type = [
            $namesSchema->type,
            Type::NULL,
        ];

        $output = [
            $field . 'Ids' => get_object_vars($idsSchema),
            $field . 'Names' => get_object_vars($namesSchema),
        ];

        /** @var ?array<string, string> $columns */
        $columns = $fieldDefs->getParam('columns');

        if (is_array($columns) && $foreignEntityType) {
            $columnsSchema = (object) [];
            $columnsSchema->type = Type::OBJECT;
            $columnsSchema->readOnly = $fieldDefs->getParam(FieldParam::READ_ONLY) ?? false;
            $columnsSchema->description = 'An {ID => object} map. Relationship column values.';

            $columnsSchema->type = [
                $columnsSchema->type,
                Type::NULL,
            ];

            $properties = array_map(function ($columnField) use ($entityType, $foreignEntityType) {
                return $this->prepareColumnSchema($entityType, $foreignEntityType, $columnField);
            }, $columns);

            $columnsSchema->additionalProperties = [
                'type' => Type::OBJECT,
                'properties' => $properties,
            ];

            $output[$field . 'Columns'] = get_object_vars($columnsSchema);
        }

        $required = [];

        if ($fieldDefs->getParam(FieldParam::REQUIRED)) {
            $required[] = $field . 'Ids';
        }

        return new FieldSchemaResult(
            properties: $output,
            required: $required,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function prepareColumnSchema(string $entityType, string $foreignEntityType, string $columnField): array
    {
        $colSchema = (object) [];

        $fieldDefs = $this->defs->getEntity($foreignEntityType)->tryGetField($columnField);

        if (!$fieldDefs) {
            $fieldDefs = $this->defs->getEntity($entityType)->tryGetField($columnField);
        }

        if (!$fieldDefs) {
            return get_object_vars($colSchema);
        }

        $optionList = $this->enumOptionsProvider->get($fieldDefs);

        if ($optionList) {
            $colSchema->type = Type::STRING;

            if (in_array('', $optionList)) {
                $colSchema->type = [
                    $colSchema->type,
                    Type::NULL,
                ];
            }

            $optionList = array_filter($optionList, fn($it) => $it !== '');
            $optionList = array_values($optionList);

            $colSchema->enum = $optionList;

            return get_object_vars($colSchema);
        }

        if ($fieldDefs->getType() === FieldType::BOOL) {
            $colSchema->type = Type::BOOLEAN;
        } else if ($fieldDefs->getType() === FieldType::INT) {
            $colSchema->type = Type::INTEGER;
        } else if ($fieldDefs->getType() === FieldType::FLOAT) {
            $colSchema->type = Type::NUMBER;
        } else if ($fieldDefs->getType() === FieldType::VARCHAR) {
            $colSchema->type = Type::STRING;

            if ($fieldDefs->getParam(FieldParam::MAX_LENGTH)) {
                $colSchema->maxLength = $fieldDefs->getParam(FieldParam::MAX_LENGTH);
            }
        }

        return get_object_vars($colSchema);
    }
}
