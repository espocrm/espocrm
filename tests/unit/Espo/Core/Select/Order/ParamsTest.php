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

namespace tests\unit\Espo\Core\Select\Order;

use Espo\Core\{
    Select\Order\Params,
};

use InvalidArgumentException;

class ParamsTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp() : void
    {
    }

    public function testFromArray()
    {
        $item = Params::fromAssoc([
            'order' => 'DESC',
            'orderBy' => 'test',
            //'forbidComplexExpressions' => true,
            'forceDefault' => true,
        ]);

        $this->assertEquals('DESC', $item->getOrder());
        $this->assertEquals('test', $item->getOrderBy());
        //$this->assertTrue($item->forbidComplexExpressions());
        $this->assertTrue($item->forceDefault());

        $item = Params::fromAssoc([
            //'forbidComplexExpressions' => false,
            'forceDefault' => false,
        ]);

        //$this->assertFalse($item->forbidComplexExpressions());
        $this->assertFalse($item->forceDefault());
    }

    public function testEmpty()
    {
        $item = Params::fromAssoc([
        ]);

        $this->assertEquals(null, $item->getOrder());
        $this->assertEquals(null, $item->getOrderBy());
        //$this->assertEquals(false, $item->forbidComplexExpressions());
        $this->assertEquals(false, $item->forceDefault());
    }

    public function testBadOrder()
    {
        $this->expectException(InvalidArgumentException::class);

        $params = Params::fromAssoc([
            'order' => 'd',
        ]);
    }

    public function testNonExistingParam()
    {
        $this->expectException(InvalidArgumentException::class);

        $params = Params::fromAssoc([
            'bad' => 'd',
        ]);
    }
}
