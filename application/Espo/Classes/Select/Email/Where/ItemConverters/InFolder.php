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

namespace Espo\Classes\Select\Email\Where\ItemConverters;

use Espo\Core\Name\Link;
use Espo\Core\Select\Where\ItemConverter;
use Espo\Core\Select\Where\Item;

use Espo\Entities\Email;
use Espo\ORM\Name\Attribute;
use Espo\ORM\Query\SelectBuilder as QueryBuilder;
use Espo\ORM\Query\Part\WhereItem as WhereClauseItem;
use Espo\ORM\Query\Part\WhereClause;
use Espo\ORM\EntityManager;
use Espo\Entities\User;
use Espo\Classes\Select\Email\Helpers\JoinHelper;
use Espo\Tools\Email\Folder;

/**
 * @noinspection PhpUnused
 */
class InFolder implements ItemConverter
{
    public function __construct(
        private User $user,
        private EntityManager $entityManager,
        private JoinHelper $joinHelper
    ) {}

    public function convert(QueryBuilder $queryBuilder, Item $item): WhereClauseItem
    {
        $folderId = $item->getValue();

        return match ($folderId) {
            Folder::ALL => WhereClause::fromRaw([]),
            Folder::INBOX => $this->convertInbox($queryBuilder),
            Folder::IMPORTANT => $this->convertImportant($queryBuilder),
            Folder::SENT => $this->convertSent($queryBuilder),
            Folder::TRASH => $this->convertTrash($queryBuilder),
            Folder::ARCHIVE => $this->convertArchive($queryBuilder),
            Folder::DRAFTS => $this->convertDraft(),
            default => $this->convertFolderId($queryBuilder, $folderId),
        };
    }

    private function convertInbox(QueryBuilder $queryBuilder): WhereClauseItem
    {
        $this->joinEmailUser($queryBuilder);

        $whereClause = [
            Email::ALIAS_INBOX . '.inTrash' => false,
            Email::ALIAS_INBOX . '.inArchive' => false,
            Email::ALIAS_INBOX . '.folderId' => null,
            Email::ALIAS_INBOX . '.userId' => $this->user->getId(),
            [
                'status' => [
                    Email::STATUS_ARCHIVED,
                    Email::STATUS_SENT,
                ],
                'groupFolderId' => null,
            ],
        ];

        $emailAddressIdList = $this->getEmailAddressIdList();

        if ($emailAddressIdList !== []) {
            $whereClause['fromEmailAddressId!='] = $emailAddressIdList;

            $whereClause[] = [
                'OR' => [
                    'status' => Email::STATUS_ARCHIVED,
                    'createdById!=' => $this->user->getId(),
                ],
            ];
        } else {
            $whereClause[] = [
                'status' => Email::STATUS_ARCHIVED,
                'createdById!=' => $this->user->getId(),
            ];
        }

        return WhereClause::fromRaw($whereClause);
    }

    private function convertSent(QueryBuilder $queryBuilder): WhereClauseItem
    {
        $this->joinEmailUser($queryBuilder);

        return WhereClause::fromRaw([
            'OR' => [
                'fromEmailAddressId' => $this->getEmailAddressIdList(),
                [
                    'status' => Email::STATUS_SENT,
                    'createdById' => $this->user->getId(),
                ]
            ],
            [
                'status!=' => Email::STATUS_DRAFT,
            ],
            Email::ALIAS_INBOX . '.inTrash' => false,
            [
                'OR' => [
                    'groupFolderId' => null,
                    'groupFolderId!=' => Email::GROUP_STATUS_FOLDER_TRASH,
                ]
            ]
        ]);
    }

    private function convertImportant(QueryBuilder $queryBuilder): WhereClauseItem
    {
        $this->joinEmailUser($queryBuilder);

        return WhereClause::fromRaw([
            Email::ALIAS_INBOX . '.userId' => $this->user->getId(),
            Email::ALIAS_INBOX . '.isImportant' => true,
        ]);
    }

    private function convertTrash(QueryBuilder $queryBuilder): WhereClauseItem
    {
        $this->joinEmailUser($queryBuilder);

        return WhereClause::fromRaw([
            'OR' => [
                [
                    Email::ALIAS_INBOX . '.userId' => $this->user->getId(),
                    Email::ALIAS_INBOX . '.inTrash' => true,
                ],
                [
                    'groupFolderId!=' => null,
                    'groupStatusFolder' => Email::GROUP_STATUS_FOLDER_TRASH,
                ],
            ]
        ]);
    }

    private function convertArchive(QueryBuilder $queryBuilder): WhereClauseItem
    {
        $this->joinEmailUser($queryBuilder);

        return WhereClause::fromRaw([
            'OR' => [
                [
                    Email::ALIAS_INBOX . '.userId' => $this->user->getId(),
                    Email::ALIAS_INBOX . '.inArchive' => true,
                ],
                [
                    'groupFolderId!=' => null,
                    'groupStatusFolder' => Email::GROUP_STATUS_FOLDER_ARCHIVE,
                ],
            ]
        ]);
    }

    private function convertDraft(): WhereClauseItem
    {
        return WhereClause::fromRaw([
            'status' => Email::STATUS_DRAFT,
            'createdById' => $this->user->getId(),
        ]);
    }

    private function convertFolderId(QueryBuilder $queryBuilder, string $folderId): WhereClauseItem
    {
        $this->joinEmailUser($queryBuilder);

        if (str_starts_with($folderId, 'group:')) {
            $groupFolderId = substr($folderId, 6);

            if ($groupFolderId === '') {
                $groupFolderId = null;
            }

            return WhereClause::fromRaw([
                'groupFolderId' => $groupFolderId,
                'groupStatusFolder' => null,
                'createdById!=' => $this->user->getId(),
                'fromEmailAddressId!=' => $this->getEmailAddressIdList(),
                'status' => [
                    Email::STATUS_ARCHIVED,
                    Email::STATUS_SENT,
                ],
                'OR' => [
                    'status' => Email::STATUS_ARCHIVED,
                    'createdById!=' => $this->user->getId(),
                ],
            ]);
        }

        return WhereClause::fromRaw([
            Email::ALIAS_INBOX . '.inTrash' => false,
            Email::ALIAS_INBOX . '.inArchive' => false,
            Email::ALIAS_INBOX . '.folderId' => $folderId,
            'groupFolderId' => null,
            'status' => [
                Email::STATUS_ARCHIVED,
                Email::STATUS_SENT,
            ],
        ]);
    }

    protected function joinEmailUser(QueryBuilder $queryBuilder): void
    {
        $this->joinHelper->joinEmailUser($queryBuilder, $this->user->getId());
    }

    /**
     * @return string[]
     */
    protected function getEmailAddressIdList(): array
    {
        $emailAddressList = $this->entityManager
            ->getRelation($this->user, Link::EMAIL_ADDRESSES)
            ->select([Attribute::ID])
            ->find();

        $emailAddressIdList = [];

        foreach ($emailAddressList as $emailAddress) {
            $emailAddressIdList[] = $emailAddress->getId();
        }

        return $emailAddressIdList;
    }
}
