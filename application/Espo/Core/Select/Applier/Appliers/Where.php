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

namespace Espo\Core\Select\Applier\Appliers;

use Espo\Core\{
    Select\Where\Params,
    Select\Where\ConverterFactory,
    Select\Where\CheckerFactory,
    Select\Where\Item as WhereItem,
};

use Espo\{
    ORM\Query\SelectBuilder as QueryBuilder,
    Entities\User,
};

class Where
{
    protected $entityType;

    /**
     * @var User
     */
    protected $user;

    /**
     * @var ConverterFactory
     */
    protected $converterFactory;

    /**
     * @var CheckerFactory
     */
    protected $checkerFactory;

    public function __construct(
        string $entityType,
        User $user,
        ConverterFactory $converterFactory,
        CheckerFactory $checkerFactory
    ) {
        $this->entityType = $entityType;
        $this->user = $user;
        $this->converterFactory = $converterFactory;
        $this->checkerFactory = $checkerFactory;
    }

    public function apply(QueryBuilder $queryBuilder, WhereItem $whereItem, Params $params): void
    {
        $checker = $this->checkerFactory->create($this->entityType, $this->user);

        $checker->check($whereItem, $params);

        $converter = $this->converterFactory->create($this->entityType, $this->user);

        $whereClause = $converter->convert($queryBuilder, $whereItem);

        $queryBuilder->where(
            $whereClause->getRaw()
        );
    }
}
