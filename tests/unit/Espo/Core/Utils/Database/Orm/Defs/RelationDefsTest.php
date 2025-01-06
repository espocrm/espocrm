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
use Espo\Core\Utils\Database\Orm\Defs\RelationDefs;
use Espo\ORM\Type\AttributeType;
use Espo\ORM\Type\RelationType;
use PHPUnit\Framework\TestCase;

class RelationDefsTest extends TestCase
{
    public function testWithParamsMerged(): void
    {
        $defs = RelationDefs::create('test')
            ->withParamsMerged([
                'a' => 'a',
                'b' => 'b',
            ])
            ->withParamsMerged([
                'b' => 'mb',
                'c' => 'mc',
            ])
            ->withParam('e', 'e');

        $this->assertEquals('a', $defs->getParam('a'));
        $this->assertEquals('mb', $defs->getParam('b'));
        $this->assertEquals('mc', $defs->getParam('c'));
        $this->assertEquals('e', $defs->getParam('e'));

        $this->assertTrue($defs->hasParam('c'));
        $this->assertFalse($defs->hasParam('d'));
    }

    public function testToAssoc(): void
    {
        $params = [
            'a' => 'a',
            'b' => 'b',
        ];

        $defs = RelationDefs::create('test')
            ->withParamsMerged($params);

        $this->assertEquals($params, $defs->toAssoc());
    }

    public function testParams(): void
    {
        $defs = RelationDefs::create('test')
            ->withType(RelationType::MANY_MANY)
            ->withForeignEntityType('Test')
            ->withForeignRelationName('foreign')
            ->withRelationshipName('Name')
            ->withKey('key')
            ->withForeignKey('foreignKey')
            ->withMidKeys('k1', 'k2')
            ->withAdditionalColumn(
                AttributeDefs::create('entityType')
                    ->withType(AttributeType::VARCHAR)
                    ->withLength(100)
            )
            ->withAdditionalColumn(
                AttributeDefs::create('primary')
                    ->withType(AttributeType::BOOL)
                    ->withDefault(false)
            );

        $this->assertEquals(RelationType::MANY_MANY, $defs->getType());
        $this->assertEquals('Test', $defs->getForeignEntityType());
        $this->assertEquals('foreign', $defs->getForeignRelationName());
        $this->assertEquals('Name', $defs->getRelationshipName());
        $this->assertEquals('key', $defs->getKey());
        $this->assertEquals('foreignKey', $defs->getForeignKey());
        $this->assertEquals(['k1', 'k2'], $defs->getParam('midKeys'));

        $params = $defs->toAssoc();

        $this->assertEquals([
            'entityType' => [
                'type' => AttributeType::VARCHAR,
                'len' => 100,
            ],
            'primary' => [
                'type' => AttributeType::BOOL,
                'default' => false,
            ],
        ], $params['additionalColumns']);
    }
}
