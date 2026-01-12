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

class LinkParentType implements FieldSchemaBuilder
{
    public function __construct(
        private Defs $defs,
    ) {}

    public function build(string $entityType, string $field): FieldSchemaResult
    {
        $fieldDefs = $this->defs->getEntity($entityType)->getField($field);

        $idSchema = (object) [];
        $idSchema->type = Type::STRING;
        $idSchema->readOnly = $fieldDefs->getParam(FieldParam::READ_ONLY) ?? false;

        $idSchema->description = "A foreign record ID.";

        if (!$fieldDefs->getParam(FieldParam::REQUIRED)) {
            $idSchema->type = [
                $idSchema->type,
                Type::NULL,
            ];
        }

        $typeSchema = (object) [];
        $typeSchema->type = Type::STRING;
        $typeSchema->readOnly = $fieldDefs->getParam(FieldParam::READ_ONLY) ?? false;

        if (!$fieldDefs->getParam(FieldParam::REQUIRED)) {
            $typeSchema->type = [
                $typeSchema->type,
                Type::NULL,
            ];
        }

        if ($fieldDefs->getParam('entityList')) {
            $typeSchema->enum = $fieldDefs->getParam('entityList');
        }

        $typeSchema->description = "An entity type.";

        $nameSchema = (object) [];
        $nameSchema->type = Type::STRING;
        $nameSchema->readOnly = true;

        $nameSchema->type = [
            $nameSchema->type,
            Type::NULL,
        ];

        $required = [];

        if ($fieldDefs->getParam(FieldParam::REQUIRED)) {
            $required[] = $field . 'Id';
            $required[] = $field . 'Type';
        }

        $nameSchema->description = 'A foreign record name.';

        return new FieldSchemaResult(
            properties: [
                $field . 'Id' => get_object_vars($idSchema),
                $field . 'Type' => get_object_vars($typeSchema),
                $field . 'Name' => get_object_vars($nameSchema),
            ],
            required: $required,
        );
    }
}
