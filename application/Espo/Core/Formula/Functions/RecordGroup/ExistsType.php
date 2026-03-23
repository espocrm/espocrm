<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2026 EspoCRM, Inc.
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

namespace Espo\Core\Formula\Functions\RecordGroup;

use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Formula\EvaluatedArgumentList;
use Espo\Core\Formula\Exceptions\BadArgumentType;
use Espo\Core\Formula\Exceptions\NotAllowedUsage;
use Espo\Core\Formula\Exceptions\TooFewArguments;
use Espo\Core\Formula\Func;
use Espo\Core\Formula\Functions\RecordGroup\Util\FindQueryUtil;
use Espo\Core\Select\Primary\Filters\All;
use Espo\Core\Select\SelectBuilderFactory;
use Espo\ORM\EntityManager;
use Espo\ORM\Name\Attribute;

/**
 * @noinspection PhpUnused
 */
class ExistsType implements Func
{
    public function __construct(
        private EntityManager $entityManager,
        private SelectBuilderFactory $selectBuilderFactory,
        private FindQueryUtil $findQueryUtil,
    ) {}

    public function process(EvaluatedArgumentList $arguments): bool
    {
        if (count($arguments) < 1) {
            throw TooFewArguments::create(1);
        }

        $entityType = $arguments[0];

        if (!is_string($entityType)) {
            throw BadArgumentType::create(1, 'string');
        }

        if (count($arguments) <= 2) {
            $filter = null;

            if (count($arguments) === 2) {
                $filter = $arguments[1];
            }

            $builder = $this->selectBuilderFactory
                ->create()
                ->withPrimaryFilter(All::NAME)
                ->from($entityType);

            $this->findQueryUtil->applyFilter($builder, $filter, 2);

            try {
                return (bool) $this->entityManager
                    ->getRDBRepository($entityType)
                    ->clone($builder->build())
                    ->findOne();
            } catch (BadRequest|Forbidden $e) {
                throw new NotAllowedUsage($e->getMessage(), 0, $e);
            }
        }

        $whereClause = [];

        $i = 1;

        while ($i < count($arguments) - 1) {
            $key = $arguments[$i];
            $value = $arguments[$i + 1];

            $this->findQueryUtil->assertWhereClauseKeyValid($entityType, $key);

            $whereClause[] = [$key => $value];

            $i = $i + 2;
        }

        return (bool) $this->entityManager
            ->getRDBRepository($entityType)
            ->select([Attribute::ID])
            ->where($whereClause)
            ->findOne();
    }
}
