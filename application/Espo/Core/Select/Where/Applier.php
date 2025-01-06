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

namespace Espo\Core\Select\Where;

use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Select\Where\Item as WhereItem;
use Espo\ORM\Query\SelectBuilder as QueryBuilder;
use Espo\Entities\User;

class Applier
{
    public function __construct(
        private string $entityType,
        private User $user,
        private ConverterFactory $converterFactory,
        private CheckerFactory $checkerFactory
    ) {}

    /**
     * @throws BadRequest
     * @throws Forbidden
     */
    public function apply(QueryBuilder $queryBuilder, WhereItem $whereItem, Params $params): void
    {
        $this->check($whereItem, $params);

        $converter = $this->converterFactory->create($this->entityType, $this->user);

        $convertedParams = new Converter\Params(useSubQueryIfMany: true);

        $queryBuilder->where(
            $converter->convert($queryBuilder, $whereItem, $convertedParams)
        );
    }

    /**
     * @throws BadRequest
     * @throws Forbidden
     */
    private function check(Item $whereItem, Params $params): void
    {
        $checker = $this->checkerFactory->create($this->entityType, $this->user);

        $checker->check($whereItem, $params);
    }
}
