<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

class ForeignType implements FieldSchemaBuilder
{
    public function __construct(
        private Defs $defs,
    ) {}

    public function build(string $entityType, string $field): FieldSchemaResult
    {
        $schema = (object) [];
        $schema->readOnly = true;

        $fieldDefs = $this->defs->getEntity($entityType)->getField($field);

        $link = $fieldDefs->getParam(FieldParam::LINK);
        $foreignField = $fieldDefs->getParam(FieldParam::FIELD);

        $foreignEntityType = $this->defs
            ->getEntity($entityType)
            ->tryGetRelation($link)
            ?->tryGetForeignEntityType();

        if (!$foreignEntityType) {
            return new FieldSchemaResult([]);
        }

        $foreignFieldDefs = $this->defs
            ->getEntity($foreignEntityType)
            ->tryGetField($foreignField);

        if (!$foreignFieldDefs) {
            return new FieldSchemaResult([]);
        }

        $fieldType = $foreignFieldDefs->getType();

        if (
            $fieldType === FieldType::ENUM ||
            $fieldType === FieldType::VARCHAR ||
            $fieldType === FieldType::TEXT ||
            $fieldType === FieldType::DATE ||
            $fieldType === FieldType::DATETIME ||
            $fieldType === FieldType::EMAIL ||
            $fieldType === FieldType::PHONE ||
            $fieldType === FieldType::WYSIWYG ||
            $fieldType === FieldType::DECIMAL
        ) {
            $schema->type = Type::STRING;
        } else if (
            $foreignEntityType === FieldType::INT
        ) {
            $schema->type = Type::INTEGER;
        } else if (
            $foreignEntityType === FieldType::FLOAT
        ) {
            $schema->type = Type::NUMBER;
        } else {
            return new FieldSchemaResult([]);
        }

        $schema->type = [
            $schema->type,
            Type::NULL,
        ];

        return new FieldSchemaResult(
            properties: [
                $field => get_object_vars($schema),
            ],
        );
    }
}

