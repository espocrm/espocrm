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

namespace tests\unit\Espo\ORM;

use Espo\ORM\Type\AttributeType;
use Espo\ORM\Util;
use PHPUnit\Framework\TestCase;

class UtilTest extends TestCase
{
    public function testAreValuesEqualScalar(): void
    {
        $this->assertTrue(
            Util::areValuesEqual(
                AttributeType::VARCHAR,
                'a1',
                'a1'
            )
        );

        $this->assertTrue(
            Util::areValuesEqual(
                AttributeType::VARCHAR,
                1,
                1
            )
        );

        $this->assertTrue(
            Util::areValuesEqual(
                AttributeType::VARCHAR,
                1.1,
                1.1
            )
        );

        $this->assertFalse(
            Util::areValuesEqual(
                AttributeType::VARCHAR,
                'a1',
                'a2'
            )
        );
    }

    public function testAreValuesEqualArray(): void
    {
        $this->assertTrue(
            Util::areValuesEqual(
                AttributeType::JSON_ARRAY,
                ['a1', 'a2'],
                ['a1', 'a2'],
            )
        );

        $this->assertFalse(
            Util::areValuesEqual(
                AttributeType::JSON_ARRAY,
                ['a1', 'a2'],
                ['a2', 'a1'],
            )
        );

        $this->assertTrue(
            Util::areValuesEqual(
                AttributeType::JSON_ARRAY,
                ['a1', 'a2'],
                ['a1', 'a2'],
                isUnordered: true,
            )
        );

        $this->assertTrue(
            Util::areValuesEqual(
                AttributeType::JSON_ARRAY,
                ['a1', 'a2'],
                ['a2', 'a1'],
                isUnordered: true,
            )
        );

        $this->assertTrue(
            Util::areValuesEqual(
                AttributeType::JSON_ARRAY,
                ['a1', 'a2'],
                ['a2', 'a1'],
                isUnordered: true,
            )
        );

        $this->assertTrue(
            Util::areValuesEqual(
                AttributeType::JSON_ARRAY,
                [['a1'], ['a2']],
                [['a1'], ['a2']],
            )
        );

        $this->assertTrue(
            Util::areValuesEqual(
                AttributeType::JSON_ARRAY,
                [(object) ['a1' => 1], (object) ['a2' => 1]],
                [(object) ['a1' => 1], (object) ['a2' => 1]],
            )
        );

        $this->assertFalse(
            Util::areValuesEqual(
                AttributeType::JSON_ARRAY,
                [(object) ['a1' => 1], (object) ['a2' => 1]],
                [(object) ['a1' => 2], (object) ['a2' => 1]],
            )
        );

        $this->assertTrue(
            Util::areValuesEqual(
                AttributeType::JSON_ARRAY,
                [[(object) ['a1' => 1], (object) ['a2' => 1]]],
                [[(object) ['a1' => 1], (object) ['a2' => 1]]],
            )
        );

        $this->assertFalse(
            Util::areValuesEqual(
                AttributeType::JSON_ARRAY,
                [[(object) ['a1' => 1], (object) ['a2' => 1]]],
                [[(object) ['a1' => 2], (object) ['a2' => 1]]],
            )
        );
    }

    public function testAreValuesEqualObject(): void
    {
        $this->assertTrue(
            Util::areValuesEqual(
                AttributeType::JSON_OBJECT,
                (object) ['a1' => 1],
                (object) ['a1' => 1],
            )
        );

        $this->assertFalse(
            Util::areValuesEqual(
                AttributeType::JSON_OBJECT,
                (object) ['a1' => 1],
                (object) ['a1' => 2],
            )
        );

        $this->assertFalse(
            Util::areValuesEqual(
                AttributeType::JSON_OBJECT,
                (object) ['a1' => 1],
                (object) ['a2' => 2],
            )
        );

        $this->assertTrue(
            Util::areValuesEqual(
                AttributeType::JSON_OBJECT,
                (object) ['a1' => 1, 'a2' => 2],
                (object) ['a2' => 2, 'a1' => 1],
            )
        );

        $this->assertTrue(
            Util::areValuesEqual(
                AttributeType::JSON_OBJECT,
                (object) ['a1' => [1, 2], 'a2' => [3, 4]],
                (object) ['a1' => [1, 2], 'a2' => [3, 4]],
            )
        );
    }
}
