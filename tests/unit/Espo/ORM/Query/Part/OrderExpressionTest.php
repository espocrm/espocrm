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

namespace tests\unit\Espo\ORM\Query\Part;

use Espo\ORM\Query\Part\Order as OrderExpr;
use Espo\ORM\Query\Part\Expression as Expr;

class OrderExpressionTest extends \PHPUnit\Framework\TestCase
{
    public function testCreate1(): void
    {
        $order = OrderExpr::fromString('test')->withDesc();

        $this->assertEquals(Expr::create('test'), $order->getExpression());
        $this->assertEquals(OrderExpr::DESC, $order->getDirection());
        $this->assertEquals(true, $order->isDesc());
    }

    public function testCreate2(): void
    {
        $order = OrderExpr::fromString('test');

        $this->assertEquals(OrderExpr::ASC, $order->getDirection());
        $this->assertEquals(false, $order->isDesc());
    }

    public function testCreate3(): void
    {
        $order = OrderExpr::fromString('test')->withAsc();

        $this->assertEquals(Expr::create('test'), $order->getExpression());
        $this->assertEquals(OrderExpr::ASC, $order->getDirection());
        $this->assertEquals(false, $order->isDesc());
    }

    public function testCreate4(): void
    {
        $order = OrderExpr::fromString('test')->withDirection(OrderExpr::DESC);

        $this->assertEquals(OrderExpr::DESC, $order->getDirection());
    }

    public function testReverseOrder(): void
    {
        $order = OrderExpr::fromString('test')
            ->withDirection(OrderExpr::DESC)
            ->withReverseDirection();

        $this->assertEquals(OrderExpr::ASC, $order->getDirection());
    }
}
