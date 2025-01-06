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
use Espo\Entities\EmailAddress;
use Espo\ORM\Defs\RelationDefs as LinkDefs;
use Espo\ORM\Name\Attribute;
use Espo\ORM\Type\AttributeType;
use Espo\ORM\Type\RelationType;

class EmailEmailAddress implements LinkConverter
{
    public function __construct() {}

    public function convert(LinkDefs $linkDefs, string $entityType): EntityDefs
    {
        $name = $linkDefs->getName();
        $hasField = $linkDefs->getParam('hasField');

        $foreignEntityType = EmailAddress::ENTITY_TYPE;

        $key1 = lcfirst($entityType) . 'Id';
        $key2 = lcfirst($foreignEntityType) . 'Id';

        $relationDefs = RelationDefs::create($name)
            ->withType(RelationType::MANY_MANY)
            ->withForeignEntityType($foreignEntityType)
            ->withKey(Attribute::ID)
            ->withForeignKey(Attribute::ID)
            ->withMidKeys($key1, $key2);

        return EntityDefs::create()
            ->withAttribute(
                AttributeDefs::create($name . 'Ids')
                    ->withType(AttributeType::JSON_ARRAY)
                    ->withNotStorable()
                    ->withParam('isLinkStub', !$hasField)
            )
            ->withAttribute(
                AttributeDefs::create($name . 'Names')
                    ->withType(AttributeType::JSON_OBJECT)
                    ->withNotStorable()
                    ->withParam('isLinkStub', !$hasField)
            )
            ->withRelation($relationDefs);
    }
}
