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

namespace Espo\Classes\Select\Email;

use Espo\Core\Select\Text\Filter;
use Espo\Core\Select\Text\Filter\Data;
use Espo\Core\Select\Text\DefaultFilter;
use Espo\Core\Select\Text\ConfigProvider;
use Espo\ORM\EntityManager;
use Espo\ORM\Name\Attribute;
use Espo\ORM\Query\Part\Condition as Cond;
use Espo\ORM\Query\SelectBuilder;
use Espo\ORM\Query\SelectBuilder as QueryBuilder;
use Espo\ORM\Query\Part\Where\OrGroup;
use Espo\ORM\Query\Part\Expression as Expr;
use Espo\Entities\EmailAddress;

/**
 * @noinspection PhpUnused
 */
class TextFilter implements Filter
{
    public function __construct(
        private DefaultFilter $defaultFilter,
        private ConfigProvider $config,
        private EntityManager $entityManager
    ) {}

    public function apply(QueryBuilder $queryBuilder, Data $data): void
    {
        $filter = $data->getFilter();
        $ftWhereItem = $data->getFullTextSearchWhereItem();

        if (
            mb_strlen($filter) < $this->config->getMinLengthForContentSearch() ||
            !str_contains($filter, '@') ||
            $data->forceFullTextSearch()
        ) {
            $this->defaultFilter->apply($queryBuilder, $data);

            return;
        }

        $emailAddressId = $this->getEmailAddressIdByValue($filter);

        $orGroupBuilder = OrGroup::createBuilder();

        if ($ftWhereItem && !$emailAddressId) {
            $orGroupBuilder->add($ftWhereItem);
        }

        if (!$emailAddressId) {
            $orGroupBuilder->add(
                Cond::equal(Expr::column('id'), null)
            );

            $queryBuilder->where($orGroupBuilder->build());

            return;
        }

        $orGroupBuilder
            ->add(
                Cond::equal(
                    Expr::column('fromEmailAddressId'),
                    $emailAddressId
                )
            )
            ->add(
                Cond::exists(
                    SelectBuilder::create()
                        ->from('EmailEmailAddress', 'sq')
                        ->where(['emailAddressId' => $emailAddressId])
                        ->where(
                            Cond::equal(
                                Expr::column('sq.emailId'),
                                Expr::column('email.id')
                            )
                        )
                        ->build()
                )
            );

        $queryBuilder->where($orGroupBuilder->build());
    }

    private function getEmailAddressIdByValue(string $value): ?string
    {
        $emailAddress = $this->entityManager
            ->getRDBRepository(EmailAddress::ENTITY_TYPE)
            ->select(Attribute::ID)
            ->where(['lower' => strtolower($value)])
            ->findOne();

        if (!$emailAddress) {
            return null;
        }

        return $emailAddress->getId();
    }
}
