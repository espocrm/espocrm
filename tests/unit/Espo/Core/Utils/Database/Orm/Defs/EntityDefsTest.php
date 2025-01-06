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

namespace tests\unit\Espo\Core\Utils\Database\Orm\Defs;

use Espo\Core\Utils\Database\Orm\Defs\AttributeDefs;
use Espo\Core\Utils\Database\Orm\Defs\EntityDefs;
use Espo\Core\Utils\Database\Orm\Defs\IndexDefs;
use Espo\Core\Utils\Database\Orm\Defs\RelationDefs;
use Espo\ORM\Type\AttributeType;
use Espo\ORM\Type\RelationType;
use PHPUnit\Framework\TestCase;

class EntityDefsTest extends TestCase
{
    public function testWith(): void
    {
        $entityDefs = EntityDefs::create();

        $a1 = AttributeDefs::create('a1')->withType(AttributeType::VARCHAR);
        $a2 = AttributeDefs::create('a2')->withType(AttributeType::INT);
        $r1 = RelationDefs::create('r1')->withType(RelationType::MANY_MANY);
        $i1 = IndexDefs::create('i1')->withParam('key', 'KEY_1');

        $entityDefs = $entityDefs
            ->withAttribute($a1)
            ->withAttribute($a2)
            ->withRelation($r1)
            ->withIndex($i1);

        $this->assertEquals([
            'attributes' => [
                'a1' => $a1->toAssoc(),
                'a2' => $a2->toAssoc(),
            ],
            'relations' => [
                'r1' => $r1->toAssoc(),
            ],
            'indexes' => [
                'i1' => $i1->toAssoc(),
            ],
        ], $entityDefs->toAssoc());
    }
}
