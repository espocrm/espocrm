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

namespace Espo\Classes\Select\Email\Where\ItemConverters;

use Espo\Core\{
    Select\Where\ItemConverter,
    Select\Where\Item,
};

use Espo\{
    ORM\Query\SelectBuilder as QueryBuilder,
    ORM\Query\Part\WhereItem as WhereClauseItem,
    ORM\Query\Part\WhereClause,
    ORM\EntityManager,
    Entities\User,
    Classes\Select\Email\Helpers\JoinHelper,
};

class InFolder implements ItemConverter
{
    /**
     * @var User
     */
    protected $user;

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var JoinHelper
     */
    protected $joinHelper;

    public function __construct(User $user, EntityManager $entityManager, JoinHelper $joinHelper)
    {
        $this->user = $user;
        $this->entityManager = $entityManager;
        $this->joinHelper = $joinHelper;
    }

    public function convert(QueryBuilder $queryBuilder, Item $item): WhereClauseItem
    {
        $folderId = $item->getValue();

        switch ($folderId) {
            case 'all':
                return WhereClause::fromRaw([]);

            case 'inbox':
                return $this->convertInbox($queryBuilder);

            case 'important':
                return $this->convertImportant($queryBuilder);

            case 'sent':
                return $this->convertSent($queryBuilder);

            case 'trash':
                return $this->convertTrash($queryBuilder);

            case 'drafts':
                return $this->convertDraft($queryBuilder);

            default:
                return $this->convertFolderId($queryBuilder, $folderId);
        }
    }

    protected function convertInbox(QueryBuilder $queryBuilder): WhereClauseItem
    {
        $this->joinEmailUser($queryBuilder);

        $whereClause = [
            'emailUser.inTrash' => false,
            'emailUser.folderId' => null,
            'emailUser.userId' => $this->user->id,
            [
                'status' => ['Archived', 'Sent'],
            ],
        ];

        $emailAddressIdList = $this->getEmailAddressIdList();

        if (!empty($emailAddressIdList)) {
            $whereClause['fromEmailAddressId!='] = $emailAddressIdList;

            $whereClause[] = [
                'OR' => [
                    'status' => 'Archived',
                    'createdById!=' => $this->user->id,
                ],
            ];
        }
        else {
            $whereClause[] = [
                'status' => 'Archived',
                'createdById!=' => $this->user->id,
            ];
        }

        return WhereClause::fromRaw($whereClause);
    }

    protected function convertSent(QueryBuilder $queryBuilder): WhereClauseItem
    {
        $this->joinEmailUser($queryBuilder);

        return WhereClause::fromRaw([
            'OR' => [
                'fromEmailAddressId' => $this->getEmailAddressIdList(),
                [
                    'status' => 'Sent',
                    'createdById' => $this->user->id,
                ]
            ],
            [
                'status!=' => 'Draft',
            ],
            'emailUser.inTrash' => false,
        ]);
    }

    protected function convertImportant(QueryBuilder $queryBuilder): WhereClauseItem
    {
        $this->joinEmailUser($queryBuilder);

        return WhereClause::fromRaw([
            'emailUser.userId' => $this->user->id,
            'emailUser.isImportant' => true,
        ]);
    }

    protected function convertTrash(QueryBuilder $queryBuilder): WhereClauseItem
    {
        $this->joinEmailUser($queryBuilder);

        return WhereClause::fromRaw([
            'emailUser.userId' => $this->user->id,
            'emailUser.inTrash' => true,
        ]);
    }

    protected function convertDraft(QueryBuilder $queryBuilder): WhereClauseItem
    {
        return WhereClause::fromRaw([
            'status' => 'Draft',
            'createdById' => $this->user->id,
        ]);
    }

    protected function convertFolderId(QueryBuilder $queryBuilder, string $folderId): WhereClauseItem
    {
        $this->joinEmailUser($queryBuilder);

        return WhereClause::fromRaw([
            'emailUser.inTrash' => false,
            'emailUser.folderId' => $folderId,
        ]);
    }

    protected function joinEmailUser(QueryBuilder $queryBuilder)
    {
        $this->joinHelper->joinEmailUser($queryBuilder, $this->user->id);
    }

    protected function getEmailAddressIdList(): array
    {
        $emailAddressList = $this->entityManager
            ->getRDBRepository('User')
            ->getRelation($this->user, 'emailAddresses')
            ->select(['id'])
            ->find();

        $emailAddressIdList = [];

        foreach ($emailAddressList as $emailAddress) {
            $emailAddressIdList[] = $emailAddress->getId();
        }

        return $emailAddressIdList;
    }
}
