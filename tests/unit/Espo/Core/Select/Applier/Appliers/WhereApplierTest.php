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

namespace tests\unit\Espo\Core\Select\Applier\Appliers;

use Espo\Core\Select\Where\Applier as WhereApplier;
use Espo\Core\Select\Where\Checker;
use Espo\Core\Select\Where\CheckerFactory;
use Espo\Core\Select\Where\Converter;
use Espo\Core\Select\Where\ConverterFactory;
use Espo\Core\Select\Where\Item as WhereItem;
use Espo\Core\Select\Where\Params;

use Espo\Entities\User;
use Espo\ORM\Query\SelectBuilder as QueryBuilder;
use PHPUnit\Framework\TestCase;

class WhereApplierTest extends TestCase
{
    private $user;
    private $queryBuilder;
    private $whereItem;
    private $converterFactory;
    private $converter;
    private $checkerFactory;
    private $checker;
    private $params;
    private $entityType;
    private $applier;

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
