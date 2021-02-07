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

namespace Espo\Classes\Select\Email\Appliers;

use Espo\Core\{
    Select\Appliers\TextFilterApplier as TextFilterApplierBase,
    Di\EntityManagerAware,
    Di\EntityManagerSetter,
};

use Espo\{
    ORM\QueryParams\SelectBuilder as QueryBuilder,
};

class TextFilterApplier extends TextFilterApplierBase implements EntityManagerAware
{
    use EntityManagerSetter;

    protected $fullTextOrderType = self::FT_ORDER_ORIGINAL;

    protected $useContainsAttributeList = ['name'];

    protected function modifyOrGroup(
        QueryBuilder $queryBuilder, string $filter, array &$orGroup, bool $hasFullTextSearch
    )  : void {

        if (strlen($filter) < self::MIN_LENGTH_FOR_CONTENT_SEARCH) {
            return;
        }

        if (strpos($filter, '@') === false) {
            return;
        }

        if ($hasFullTextSearch) {
            return;
        }

        $emailAddressId = $this->getEmailAddressIdByValue($filter);

        if (!$emailAddressId) {
            $orGroup = [];

            return;
        }

        $this->leftJoinEmailAddress($queryBuilder);

        $orGroup = [];

        $orGroup['fromEmailAddressId'] = $emailAddressId;
        $orGroup['emailEmailAddress.emailAddressId'] = $emailAddressId;
    }

    protected function leftJoinEmailAddress(QueryBuilder $queryBuilder) : void
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

    protected function getEmailAddressIdByValue(string $value) : ?string
    {
        $emailAddress = $this->entityManager
            ->getRepository('EmailAddress')
            ->select('id')
            ->where([
                'lower' => strtolower($value),
            ])
            ->findOne();

        if (!$emailAddress) {
            return null;
        }

        return $emailAddress->id;
    }
}
