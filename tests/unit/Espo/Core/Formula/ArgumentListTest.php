<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2021 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
 * Website: https://www.espocrm.com
 *
 * EspoCRM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * EspoCRM is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with EspoCRM. If not, see http://www.gnu.org/licenses/.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

namespace tests\unit\Espo\Core\Formula;

use Espo\Core\Formula\ArgumentList;

class ArgumentListTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp() : void
    {
    }

    protected function tearDown() : void
    {
    }

    public function testCount()
    {
        $list = new ArgumentList([]);
        $this->assertEquals(0, count($list));

        $list = new ArgumentList([
            null,
            '1',
            (object) [],
        ]);
        $this->assertEquals(3, count($list));
    }

    public function testAccess()
    {
        $list = new ArgumentList([
            null,
            '1',
            (object) [],
        ]);
        $this->assertEquals(null, $list[0]->getData());
        $this->assertEquals('1', $list[1]->getData());
    }

    public function testIteration()
    {
        $list = new ArgumentList([
            null,
            '1',
        ]);

        $array = [];
        foreach ($list as $item) {
            $array[] = $item->getData();
        }
        $this->assertEquals(null, $array[0]);
        $this->assertEquals('1', $array[1]);
    }
}
