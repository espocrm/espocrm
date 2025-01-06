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

namespace Espo\Core\Select\Bool;

use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Select\OrmSelectBuilder;
use Espo\Core\Select\SelectManager;
use Espo\Core\Select\Bool\FilterFactory as BoolFilterFactory;
use Espo\ORM\Query\Select;
use Espo\ORM\Query\SelectBuilder as QueryBuilder;
use Espo\ORM\Query\Part\Where\OrGroupBuilder;
use Espo\ORM\Query\Part\WhereClause;

use Espo\Entities\User;

class Applier
{
    public function __construct(
        private string $entityType,
        private User $user,
        private BoolFilterFactory $boolFilterFactory,
        private SelectManager $selectManager
    ) {}

    /**
     * @param string[] $boolFilterNameList
     * @throws BadRequest
     */
    public function apply(QueryBuilder $queryBuilder, array $boolFilterNameList): void
    {
        $orGroupBuilder = new OrGroupBuilder();

        $isMultiple = count($boolFilterNameList) > 1;

        if ($isMultiple) {
            $queryBefore = $queryBuilder->build();
        }

        foreach ($boolFilterNameList as $filterName) {
            $this->applyBoolFilter($queryBuilder, $orGroupBuilder, $filterName);
        }

        if ($isMultiple) {
            $this->handleMultiple($queryBefore, $queryBuilder);
        }

        $queryBuilder->where(
            $orGroupBuilder->build()
        );
    }

    /**
     * @throws BadRequest
     */
    private function applyBoolFilter(
        QueryBuilder $queryBuilder,
        OrGroupBuilder $orGroupBuilder,
        string $filterName
    ): void {

        if ($this->boolFilterFactory->has($this->entityType, $filterName)) {
            $filter = $this->boolFilterFactory->create($this->entityType, $this->user, $filterName);

            $filter->apply($queryBuilder, $orGroupBuilder);

            return;
        }

        // For backward compatibility.
        if (
            $this->selectManager->hasBoolFilter($filterName) &&
            $queryBuilder instanceof OrmSelectBuilder
        ) {
            $rawWhereClause = $this->selectManager->applyBoolFilterToQueryBuilder($queryBuilder, $filterName);

            $whereItem = WhereClause::fromRaw($rawWhereClause);

            $orGroupBuilder->add($whereItem);

            return;
        }

        throw new BadRequest("No bool filter '$filterName' for '$this->entityType'.");
    }

    private function handleMultiple(Select $queryBefore, QueryBuilder $queryBuilder): void
    {
        $queryAfter = $queryBuilder->build();

        $joinCountBefore = count($queryBefore->getJoins()) + count($queryBefore->getLeftJoins());
        $joinCountAfter = count($queryAfter->getJoins()) + count($queryAfter->getLeftJoins());

        if ($joinCountBefore < $joinCountAfter) {
            $queryBuilder->distinct();
        }
    }
}
