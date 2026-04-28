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

namespace Espo\Core\Mail\Account\Fetcher;

use Espo\Core\Mail\Account\Account;
use Espo\Entities\EmailFilter;
use Espo\Entities\InboundEmail;
use Espo\ORM\Collection;
use Espo\ORM\EntityManager;
use Espo\ORM\Query\Part\Expression;
use Espo\ORM\Query\Part\Order;

class FiltersProvider
{
    public function __construct(
        private EntityManager $entityManager,
    ) {}

    /**
     * @return Collection<EmailFilter>
     */
    public function get(Account $account): Collection
    {
        $actionList = [EmailFilter::ACTION_SKIP];

        if ($account->getEntityType() === InboundEmail::ENTITY_TYPE) {
            $actionList[] = EmailFilter::ACTION_MOVE_TO_GROUP_FOLDER;
        }

        $builder = $this->entityManager
            ->getRDBRepository(EmailFilter::ENTITY_TYPE)
            ->where([
                'action' => $actionList,
                'OR' => [
                    [
                        'parentType' => $account->getEntityType(),
                        'parentId' => $account->getId(),
                        'action' => $actionList,
                    ],
                    [
                        'parentId' => null,
                        'action' => EmailFilter::ACTION_SKIP,
                    ],
                ]
            ]);

        if (count($actionList) > 1) {
            $builder->order(
                Order::createByPositionInList(
                    Expression::column('action'),
                    $actionList
                )
            );
        }

        /** @var Collection<EmailFilter> */
        return $builder->find();
    }
}
