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

namespace tests\unit\Espo\Core\Select\Applier\Appliers;

use Espo\Core\{
    Select\Where\Checker,
    Select\Where\Params,
    Select\Where\Converter,
    Select\Where\ConverterFactory,
    Select\Where\CheckerFactory,
    Select\Where\Item as WhereItem,
    Select\Applier\Appliers\Where as WhereApplier,
};

use Espo\{
    ORM\Query\SelectBuilder as QueryBuilder,
    Entities\User,
};

class WhereApplierTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        $this->user = $this->createMock(User::class);
        $this->queryBuilder = $this->createMock(QueryBuilder::class);
        $this->whereItem = $this->createMock(WhereItem::class);
        $this->converterFactory = $this->createMock(ConverterFactory::class);
        $this->converter = $this->createMock(Converter::class);
        $this->checkerFactory = $this->createMock(CheckerFactory::class);
        $this->checker = $this->createMock(Checker::class);
        $this->params = $this->createMock(Params::class);

        $this->entityType = 'Test';

        $this->applier = new WhereApplier(
            $this->entityType,
            $this->user,
            $this->converterFactory,
            $this->checkerFactory
        );
    }

    public function testApply1()
    {
        $this->checker
            ->expects($this->once())
            ->method('check')
            ->with($this->whereItem, $this->params);

        $this->checkerFactory
            ->expects($this->once())
            ->method('create')
            ->with($this->entityType, $this->user)
            ->willReturn($this->checker);

        $this->converter
            ->expects($this->once())
            ->method('convert')
            ->with($this->queryBuilder, $this->whereItem);

        $this->converterFactory
            ->expects($this->once())
            ->method('create')
            ->with($this->entityType, $this->user)
            ->willReturn($this->converter);

        $this->applier->apply($this->queryBuilder, $this->whereItem, $this->params);
    }

    public function testApply2()
    {
        $this->checker
            ->expects($this->once())
            ->method('check')
            ->with($this->whereItem, $this->params);

        $this->checkerFactory
            ->expects($this->once())
            ->method('create')
            ->with($this->entityType, $this->user)
            ->willReturn($this->checker);

        $this->converter
            ->expects($this->once())
            ->method('convert')
            ->with($this->queryBuilder, $this->whereItem);

        $this->converterFactory
            ->expects($this->once())
            ->method('create')
            ->with($this->entityType, $this->user)
            ->willReturn($this->converter);

        $this->applier->apply($this->queryBuilder, $this->whereItem, $this->params);
    }
}
