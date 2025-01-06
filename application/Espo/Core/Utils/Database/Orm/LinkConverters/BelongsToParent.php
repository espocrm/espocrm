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

namespace Espo\Core\Utils\Database\Orm\LinkConverters;

use Espo\Core\Utils\Database\Orm\Defs\AttributeDefs;
use Espo\Core\Utils\Database\Orm\Defs\EntityDefs;
use Espo\Core\Utils\Database\Orm\Defs\RelationDefs;
use Espo\Core\Utils\Database\Orm\LinkConverter;
use Espo\ORM\Defs\Params\AttributeParam;
use Espo\ORM\Defs\Params\RelationParam;
use Espo\ORM\Defs\RelationDefs as LinkDefs;
use Espo\ORM\Type\AttributeType;
use Espo\ORM\Type\RelationType;

class BelongsToParent implements LinkConverter
{
    private const TYPE_LENGTH = 100;

    public function convert(LinkDefs $linkDefs, string $entityType): EntityDefs
    {
        $name = $linkDefs->getName();

        $foreignRelationName = $linkDefs->hasForeignRelationName() ?
            $linkDefs->getForeignRelationName() : null;

        $idName = $name . 'Id';
        $nameName = $name . 'Name';
        $typeName = $name . 'Type';

        $relationDefs = RelationDefs::create($name)
            ->withType(RelationType::BELONGS_TO_PARENT)
            ->withKey($idName)
            ->withForeignRelationName($foreignRelationName);

        if ($linkDefs->getParam(RelationParam::DEFERRED_LOAD)) {
            $relationDefs = $relationDefs->withParam(RelationParam::DEFERRED_LOAD, true);
        }

        return EntityDefs::create()
            ->withAttribute(
                AttributeDefs::create($idName)
                    ->withType(AttributeType::FOREIGN_ID)
                    ->withParam('index', $name)
            )
            ->withAttribute(
                AttributeDefs::create($typeName)
                    ->withType(AttributeType::FOREIGN_TYPE)
                    ->withParam(AttributeParam::NOT_NULL, false) // Revise whether needed.
                    ->withParam('index', $name)
                    ->withLength(self::TYPE_LENGTH)
            )
            ->withAttribute(
                AttributeDefs::create($nameName)
                    ->withType(AttributeType::VARCHAR)
                    ->withNotStorable()
            )
            ->withRelation($relationDefs);
    }
}
