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

use Espo\Core\Name\Field;
use Espo\Core\Utils\Database\Orm\Defs\AttributeDefs;
use Espo\Core\Utils\Database\Orm\Defs\EntityDefs;
use Espo\Core\Utils\Database\Orm\Defs\RelationDefs;
use Espo\Core\Utils\Database\Orm\FieldConverter;
use Espo\Entities\Attachment;
use Espo\ORM\Defs\FieldDefs;
use Espo\ORM\Defs\Params\AttributeParam;
use Espo\ORM\Defs\Params\RelationParam;
use Espo\ORM\Name\Attribute;
use Espo\ORM\Type\AttributeType;
use Espo\ORM\Type\RelationType;

class File implements FieldConverter
{
    public function convert(FieldDefs $fieldDefs, string $entityType): EntityDefs
    {
        $name = $fieldDefs->getName();

        $idName = $name . 'Id';
        $nameName = $name . 'Name';

        $idDefs = AttributeDefs::create($idName)
            ->withType(AttributeType::FOREIGN_ID)
            ->withParam('index', false);

        $nameDefs = AttributeDefs::create($nameName)
            ->withType(AttributeType::FOREIGN);

        if ($fieldDefs->isNotStorable()) {
            $idDefs = $idDefs->withNotStorable();

            $nameDefs = $nameDefs->withType(AttributeType::VARCHAR);
        }

        /** @var array<string, mixed> $defaults */
        $defaults = $fieldDefs->getParam('defaultAttributes') ?? [];

        if (array_key_exists($idName, $defaults)) {
            $idDefs = $idDefs->withDefault($defaults[$idName]);
        }

        $relationDefs = null;

        if (!$fieldDefs->isNotStorable()) {
            $nameDefs = $nameDefs->withParamsMerged([
                AttributeParam::RELATION => $name,
                AttributeParam::FOREIGN => Field::NAME,
            ]);

            $relationDefs = RelationDefs::create($name)
                ->withType(RelationType::BELONGS_TO)
                ->withForeignEntityType(Attachment::ENTITY_TYPE)
                ->withKey($idName)
                ->withForeignKey(Attribute::ID)
                ->withParam(RelationParam::FOREIGN, null);
        }

        $entityDefs = EntityDefs::create()
            ->withAttribute($idDefs)
            ->withAttribute($nameDefs);

        if ($relationDefs) {
            $entityDefs = $entityDefs->withRelation($relationDefs);
        }

        return $entityDefs;
    }
}
