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
use Espo\Core\Utils\Util;
use Espo\ORM\Defs\RelationDefs as LinkDefs;
use Espo\ORM\Name\Attribute;
use Espo\ORM\Type\AttributeType;
use Espo\ORM\Type\RelationType;

class ManyMany implements LinkConverter
{
    public function convert(LinkDefs $linkDefs, string $entityType): EntityDefs
    {
        $name = $linkDefs->getName();
        $foreignEntityType = $linkDefs->getForeignEntityType();
        $foreignRelationName = $linkDefs->getForeignRelationName();
        $hasField = $linkDefs->getParam('hasField');
        $columnAttributeMap = $linkDefs->getParam('columnAttributeMap');

        $relationshipName = $linkDefs->hasRelationshipName() ?
            $linkDefs->getRelationshipName() :
            self::composeRelationshipName($entityType, $foreignEntityType);

        if ($linkDefs->hasMidKey() && $linkDefs->hasForeignMidKey()) {
            $key1 = $linkDefs->getMidKey();
            $key2 = $linkDefs->getForeignMidKey();
        } else {
            $key1 = lcfirst($entityType) . 'Id';
            $key2 = lcfirst($foreignEntityType) . 'Id';

            if ($key1 === $key2) {
                [$key1, $key2] = strcmp($name, $foreignRelationName) > 0 ?
                    ['leftId', 'rightId'] :
                    ['rightId', 'leftId'];
            }
        }

        $relationDefs = RelationDefs::create($name)
            ->withType(RelationType::MANY_MANY)
            ->withForeignEntityType($foreignEntityType)
            ->withRelationshipName($relationshipName)
            ->withKey(Attribute::ID)
            ->withForeignKey(Attribute::ID)
            ->withMidKeys($key1, $key2)
            ->withForeignRelationName($foreignRelationName);

        if ($columnAttributeMap) {
            $relationDefs = $relationDefs->withParam('columnAttributeMap', $columnAttributeMap);
        }

        return EntityDefs::create()
            ->withAttribute(
                AttributeDefs::create($name . 'Ids')
                    ->withType(AttributeType::JSON_ARRAY)
                    ->withNotStorable()
                    ->withParam('isLinkStub', !$hasField) // Revise.
            )
            ->withAttribute(
                AttributeDefs::create($name . 'Names')
                    ->withType(AttributeType::JSON_OBJECT)
                    ->withNotStorable()
                    ->withParam('isLinkStub', !$hasField) // Revise.
            )
            ->withRelation($relationDefs);
    }

    private static function composeRelationshipName(string $left, string $right): string
    {
        $parts = [
            Util::toCamelCase(lcfirst($left)),
            Util::toCamelCase(lcfirst($right)),
        ];

        sort($parts);

        return Util::toCamelCase(implode('_', $parts));
    }
}
