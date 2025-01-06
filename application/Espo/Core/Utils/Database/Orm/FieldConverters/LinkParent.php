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

namespace Espo\Core\Utils\Database\Orm\FieldConverters;

use Espo\Core\ORM\Type\FieldType;
use Espo\Core\Utils\Database\Orm\Defs\AttributeDefs;
use Espo\Core\Utils\Database\Orm\Defs\EntityDefs;
use Espo\Core\Utils\Database\Orm\FieldConverter;
use Espo\ORM\Defs\FieldDefs;
use Espo\ORM\Defs\Params\AttributeParam;
use Espo\ORM\Type\AttributeType;

class LinkParent implements FieldConverter
{
    private const TYPE_LENGTH = 100;

    public function convert(FieldDefs $fieldDefs, string $entityType): EntityDefs
    {
        $name = $fieldDefs->getName();

        $idName = $name . 'Id';
        $typeName = $name . 'Type';
        $nameName = $name . 'Name';

        $idDefs = AttributeDefs::create($idName)
            ->withType(AttributeType::FOREIGN_ID)
            ->withParamsMerged([
                'index' => $name,
                'attributeRole' => 'id',
                'fieldType' => FieldType::LINK_PARENT,
            ]);

        $typeDefs = AttributeDefs::create($typeName)
            ->withType(AttributeType::FOREIGN_TYPE)
            ->withParam(AttributeParam::NOT_NULL, false)
            ->withParam('index', $name)
            ->withLength(self::TYPE_LENGTH)
            ->withParamsMerged([
                'attributeRole' => 'type',
                'fieldType' => FieldType::LINK_PARENT,
            ]);

        $nameDefs = AttributeDefs::create($nameName)
            ->withType(AttributeType::VARCHAR)
            ->withNotStorable()
            ->withParamsMerged([
                AttributeParam::RELATION => $name,
                'isParentName' => true,
                'attributeRole' => 'name',
                'fieldType' => FieldType::LINK_PARENT,
            ]);

        if ($fieldDefs->isNotStorable()) {
            $idDefs = $idDefs->withNotStorable();
            $typeDefs = $typeDefs->withNotStorable();
        }

        /** @var array<string, mixed> $defaults */
        $defaults = $fieldDefs->getParam('defaultAttributes') ?? [];

        if (array_key_exists($idName, $defaults)) {
            $idDefs = $idDefs->withDefault($defaults[$idName]);
        }

        if (array_key_exists($typeName, $defaults)) {
            $typeDefs = $idDefs->withDefault($defaults[$typeName]);
        }

        return EntityDefs::create()
            ->withAttribute($idDefs)
            ->withAttribute($typeDefs)
            ->withAttribute($nameDefs);
    }
}
