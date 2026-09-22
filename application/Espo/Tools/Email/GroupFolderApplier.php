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

namespace Espo\Tools\Email;

use Espo\Core\Field\Link;
use Espo\Entities\Email;
use Espo\Entities\GroupEmailFolder;
use Espo\Entities\Team;
use Espo\Entities\User;
use Espo\ORM\EntityManager;
use Espo\ORM\Name\Attribute;
use Espo\ORM\Query\Part\Condition;
use Espo\ORM\Query\Part\Expression;
use Espo\ORM\Query\SelectBuilder;

/**
 * @internal
 */
class GroupFolderApplier
{
    public function __construct(
        private EntityManager $entityManager,
    ) {}

    /**
     * @param string[] $ignoreUserIds
     * @param string[] $ignoreTeamIds
     */
    public function apply(
        Email $email,
        GroupEmailFolder|Link $groupFolder,
        array $ignoreUserIds = [],
        array $ignoreTeamIds = [],
    ): bool {

        $folderId = $groupFolder->getId();

        $email->setGroupFolderId($folderId);

        if ($groupFolder instanceof Link) {
            $groupFolder = $this->entityManager->getRDBRepositoryByClass(GroupEmailFolder::class)->getById($folderId);
        }

        if (
            !$groupFolder ||
            !$groupFolder->getTeams()->getCount()
        ) {
            return false;
        }

        $added = false;

        foreach (array_diff($groupFolder->getTeams()->getIdList(), $ignoreTeamIds) as $teamId) {
            $added = true;

            $email->addTeamId($teamId);
        }

        $users = $this->entityManager
            ->getRDBRepositoryByClass(User::class)
            ->select([Attribute::ID])
            ->sth()
            ->where([
                User::FIELD_TYPE => [User::TYPE_REGULAR, User::TYPE_ADMIN],
                User::FIELD_IS_ACTIVE => true,
                Attribute::ID . '!=' => $ignoreUserIds,
            ])
            ->where(
                Condition::in(
                    Expression::column(Attribute::ID),
                    SelectBuilder::create()
                        ->from(Team::RELATIONSHIP_TEAM_USER)
                        ->select('userId')
                        ->where(['teamId' => $groupFolder->getTeams()->getIdList()])
                        ->build()
                )
            )
            ->find();

        foreach ($users as $user) {
            $added = true;

            $email->addUserId($user->getId());
        }

        return $added;
    }
}
