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

use Espo\Core\Select\OrmSelectBuilder;

use Espo\Core\{
    Exceptions\Error,
    Select\SelectManager,
    Select\Primary\FilterFactory,
};

use Espo\{
    ORM\Query\SelectBuilder as QueryBuilder,
    Entities\User,
};

class PrimaryFilter
{
    protected $entityType;

    /**
     * @var User
     */
    protected $user;

    /**
     * @var SelectManager
     */
    protected $selectManager;

    /**
     * @var FilterFactory
     */
    protected $primaryFilterFactory;

    public function __construct(
        string $entityType,
        User $user,
        FilterFactory $primaryFilterFactory,
        SelectManager $selectManager
    ) {
        $this->entityType = $entityType;
        $this->user = $user;
        $this->primaryFilterFactory = $primaryFilterFactory;
        $this->selectManager = $selectManager;
    }

    public function apply(QueryBuilder $queryBuilder, string $filterName): void
    {
        if ($this->primaryFilterFactory->has($this->entityType, $filterName)) {
            $filter = $this->primaryFilterFactory->create($this->entityType, $this->user, $filterName);

            $filter->apply($queryBuilder);

            return;
        }

        // For backward compatibility.
        if (
            $this->selectManager->hasPrimaryFilter($filterName) &&
            $queryBuilder instanceof OrmSelectBuilder
        ) {
            $this->selectManager->applyPrimaryFilterToQueryBuilder($queryBuilder, $filterName);

            return;
        }

        throw new Error("No primary filter '{$filterName}' for '{$this->entityType}'.");
    }
}
