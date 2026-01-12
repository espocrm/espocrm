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
use Espo\ORM\Defs;
use Espo\ORM\Defs\Params\FieldParam;
use Espo\Tools\OpenApi\FieldSchemaBuilder;
use Espo\Tools\OpenApi\FieldSchemaResult;

class PhoneType implements FieldSchemaBuilder
{
    const int DEFAULT_MAX_LENGTH = 36;

    public function __construct(
        private Defs $defs,
    ) {}

    public function build(string $entityType, string $field): FieldSchemaResult
    {
        $fieldDefs = $this->defs->getEntity($entityType)->getField($field);

        $schema = (object) [];
        $schema->type = Type::STRING;
        $schema->maxLength = self::DEFAULT_MAX_LENGTH;
        $schema->readOnly = $fieldDefs->getParam(FieldParam::READ_ONLY) ?? false;

        if (!$fieldDefs->getParam(FieldParam::REQUIRED)) {
            $schema->type = [
                $schema->type,
                Type::NULL,
            ];
        }

        $schema->description = 'A primary phone number.';

        $itemSchema = (object) [];
        $itemSchema->type = Type::OBJECT;
        $itemSchema->properties = [
            'phoneNumber' => [
                'type' => Type::STRING,
            ],
            'primary' => [
                'type' => Type::BOOLEAN,
            ],
            'optOut' => [
                'type' => Type::BOOLEAN,
            ],
            'invalid' => [
                'type' => Type::BOOLEAN,
            ],
            'type' => [
                'type' => Type::STRING,
                'enum' => $fieldDefs->getParam('typeList'),
            ],
        ];
        $itemSchema->required = [
            'phoneNumber',
            'primary',
        ];

        $dataSchema = (object) [];
        $dataSchema->type = Type::ARRAY;
        $dataSchema->items = get_object_vars($itemSchema);
        $dataSchema->type = [
            $dataSchema->type,
            Type::NULL,
        ];
        $dataSchema->description = 'Multiple phone numbers.';

        $required = [];

        if ($fieldDefs->getParam(FieldParam::REQUIRED)) {
            $required[] = $field;
        }

        return new FieldSchemaResult(
            properties: [
                $field => get_object_vars($schema),
                $field . 'Data' => get_object_vars($dataSchema),
            ],
            required: $required,
        );
    }
}
