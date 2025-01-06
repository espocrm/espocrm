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

namespace Espo\Core\Select\AccessControl;

use Espo\Core\Select\OrmSelectBuilder;
use Espo\Core\Select\AccessControl\FilterFactory as AccessControlFilterFactory;
use Espo\Core\Select\AccessControl\FilterResolverFactory as AccessControlFilterResolverFactory;
use Espo\Core\Select\SelectManager;

use Espo\Entities\User;
use Espo\ORM\Query\SelectBuilder as QueryBuilder;

use RuntimeException;

class Applier
{
    public function __construct(
        private string $entityType,
        private User $user,
        private AccessControlFilterFactory $accessControlFilterFactory,
        private AccessControlFilterResolverFactory $accessControlFilterResolverFactory,
        private SelectManager $selectManager
    ) {}

    public function apply(QueryBuilder $queryBuilder): void
    {
        // For backward compatibility.
        if (
            $this->selectManager->hasInheritedAccessMethod() &&
            $queryBuilder instanceof OrmSelectBuilder
        ) {
            $this->selectManager->applyAccessToQueryBuilder($queryBuilder);

            return;
        }

        $this->applyMandatoryFilter($queryBuilder);

        $accessControlFilterResolver = $this->accessControlFilterResolverFactory
            ->create($this->entityType, $this->user);

        $filterName = $accessControlFilterResolver->resolve();

        if (!$filterName) {
            return;
        }

        // For backward compatibility.
        if (
            $this->selectManager->hasInheritedAccessFilterMethod($filterName) &&
            $queryBuilder instanceof OrmSelectBuilder
        ) {
            $this->selectManager->applyAccessFilterToQueryBuilder($queryBuilder, $filterName);

            return;
        }

        if ($this->accessControlFilterFactory->has($this->entityType, $filterName)) {
            $filter = $this->accessControlFilterFactory
                ->create($this->entityType, $this->user, $filterName);

            $filter->apply($queryBuilder);

            return;
        }

        throw new RuntimeException("No access filter '{$filterName}' for '{$this->entityType}'.");
    }

    private function applyMandatoryFilter(QueryBuilder $queryBuilder): void
    {
        $filter = $this->accessControlFilterFactory
            ->create($this->entityType, $this->user, 'mandatory');

        $filter->apply($queryBuilder);
    }
}
