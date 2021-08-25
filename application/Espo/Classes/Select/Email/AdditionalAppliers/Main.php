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

namespace Espo\Classes\Select\Email\AdditionalAppliers;

use Espo\Core\Select\Applier\AdditionalApplier;
use Espo\ORM\Query\SelectBuilder;
use Espo\Core\Select\SearchParams;

use Espo\Classes\Select\Email\Helpers\JoinHelper;

use Espo\Entities\User;

class Main implements AdditionalApplier
{
    private $user;

    private $joinHelper;

    public function __construct(User $user, JoinHelper $joinHelper)
    {
        $this->user = $user;
        $this->joinHelper = $joinHelper;
    }

    public function apply(SelectBuilder $queryBuilder, SearchParams $searchParams): void
    {
        $folder = $this->retrieveFolder($searchParams);

        if ($folder === 'drafts') {
            $queryBuilder->useIndex('createdById');
        }
        else if ($folder === 'important') {
            // skip
        }
        else if ($this->checkApplyDateSentIndex($queryBuilder, $searchParams)) {
            $queryBuilder->useIndex('dateSent');
        }

        if ($folder !== 'drafts') {
            $this->joinEmailUser($queryBuilder);
        }
    }

    protected function joinEmailUser(SelectBuilder $queryBuilder): void
    {
        $this->joinHelper->joinEmailUser($queryBuilder, $this->user->id);

        if ($queryBuilder->build()->getSelect() === []) {
            $queryBuilder->select('*');
        }

        $itemList = [
            'isRead',
            'isImportant',
            'inTrash',
            'folderId',
        ];

        foreach ($itemList as $item) {
            $queryBuilder->select('emailUser.' . $item, $item);
        }
    }

    protected function retrieveFolder(SearchParams $searchParams): ?string
    {
        if (!$searchParams->getWhere()) {
            return null;
        }

        foreach ($searchParams->getWhere()->getItemList() as $item) {
            if ($item->getType() === 'inFolder') {
                return $item->getValue();
            }
        }

        return null;
    }

    protected function checkApplyDateSentIndex(SelectBuilder $queryBuilder, SearchParams $searchParams): bool
    {
        if ($searchParams->getTextFilter()) {
            return false;
        }

        if ($searchParams->getOrderBy() && $searchParams->getOrderBy() !== 'dateSent') {
            return false;
        }

        $whereItemList = [];

        if ($searchParams->getWhere()) {
            $whereItemList = $searchParams->getWhere()->getItemList();
        }

        foreach ($whereItemList as $item) {
            $itemAttribute = $item->getAttribute();

            if (
                $itemAttribute &&
                $itemAttribute !== 'folderId' &&
                !in_array($itemAttribute, ['teams', 'users', 'status'])
            ) {
                return false;
            }
        }

        if ($queryBuilder->hasLeftJoinAlias('teamsAccess')) {
            return false;
        }

        return true;
    }
}
