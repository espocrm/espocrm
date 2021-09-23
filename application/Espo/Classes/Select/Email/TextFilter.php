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

namespace Espo\Classes\Select\Email;

use Espo\Core\Select\Text\Filter;
use Espo\Core\Select\Text\Filter\Data;
use Espo\Core\Select\Text\DefaultFilter;
use Espo\Core\Select\Text\ConfigProvider;

use Espo\ORM\EntityManager;
use Espo\ORM\Query\SelectBuilder as QueryBuilder;
use Espo\ORM\Query\Part\Where\OrGroup;
use Espo\ORM\Query\Part\Where\Comparison as Cmp;
use Espo\ORM\Query\Part\Expression as Expr;

use Espo\Entities\EmailAddress;

class TextFilter implements Filter
{
    private $defaultFilter;

    private $config;

    private $entityManager;

    public function __construct(
        DefaultFilter $defaultFilter,
        ConfigProvider $config,
        EntityManager $entityManager
    ) {
        $this->defaultFilter = $defaultFilter;
        $this->config = $config;
        $this->entityManager = $entityManager;
    }

    public function apply(QueryBuilder $queryBuilder, Data $data): void
    {
        $filter = $data->getFilter();

        if (
            mb_strlen($filter) < $this->config->getMinLengthForContentSearch() ||
            strpos($filter, '@') === false ||
            $data->forceFullTextSearch() ||
            $data->getFullTextSearchWhereItem()
        ) {
            $this->defaultFilter->apply($queryBuilder, $data);

            return;
        }

        $emailAddressId = $this->getEmailAddressIdByValue($filter);

        if (!$emailAddressId) {
            $queryBuilder->where(['id' => null]);

            return;
        }

        $this->leftJoinEmailAddress($queryBuilder);

        $orGroupBuilder = OrGroup::createBuilder()
            ->add(
                Cmp::equal(
                    Expr::column('fromEmailAddressId'),
                    $emailAddressId
                )
            )
            ->add(
                Cmp::equal(
                    Expr::column('emailEmailAddress.emailAddressId'),
                    $emailAddressId
                )
            );

        $queryBuilder->where($orGroupBuilder->build());
    }

    private function leftJoinEmailAddress(QueryBuilder $queryBuilder): void
    {
        if ($queryBuilder->hasLeftJoinAlias('emailEmailAddress')) {
            return;
        }

        $queryBuilder->distinct();
        $queryBuilder->leftJoin(
            'EmailEmailAddress',
            'emailEmailAddress',
            [
                'emailId:' => 'id',
                'deleted' => false,
            ]
        );
    }

    private function getEmailAddressIdByValue(string $value): ?string
    {
        $emailAddress = $this->entityManager
            ->getRDBRepository(EmailAddress::ENTITY_TYPE)
            ->select('id')
            ->where([
                'lower' => strtolower($value),
            ])
            ->findOne();

        if (!$emailAddress) {
            return null;
        }

        return $emailAddress->getId();
    }
}
