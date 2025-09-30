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

namespace tests\unit\Espo\Core\Utils\Database\Orm\Defs;

use Espo\Core\Utils\Database\Orm\Defs\AttributeDefs;
use Espo\ORM\Type\AttributeType;
use PHPUnit\Framework\TestCase;

class AttributeDefsTest extends TestCase
{
    public function testWithParamsMerged(): void
    {
        $defs = AttributeDefs::create('test')
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

        $defs = AttributeDefs::create('test')
            ->withParamsMerged($params);

        $this->assertEquals($params, $defs->toAssoc());
    }

    public function testType(): void
    {
        $defs = AttributeDefs::create('test')->withType(AttributeType::VARCHAR);

        $this->assertEquals(AttributeType::VARCHAR, $defs->getType());
    }

    public function testParams(): void
    {
        $defs = AttributeDefs::create('test')
            ->withNotStorable()
            ->withDefault('1');

        $this->assertEquals([
            'notStorable' => true,
            'default' => '1',
        ], $defs->toAssoc());
    }
}
