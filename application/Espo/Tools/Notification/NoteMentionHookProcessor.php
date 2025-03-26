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

namespace Espo\Tools\Notification;

use Espo\Core\Acl;
use Espo\Core\Acl\Permission;
use Espo\Core\AclManager;
use Espo\ORM\EntityManager;
use Espo\Entities\User;
use Espo\Entities\Note;

use stdClass;

class NoteMentionHookProcessor
{
    public function __construct(
        private Service $service,
        private EntityManager $entityManager,
        private User $user,
        private Acl $acl,
        private AclManager $aclManager
    ) {}

    public function beforeSave(Note $note): void
    {
        if ($note->getType() !== Note::TYPE_POST) {
            return;
        }

        $this->process($note);
    }

    private function process(Note $note): void
    {
        $mentionData = (object) [];

        $previousMentionList = [];

        if (!$note->isNew()) {
            $previousMentionList = array_keys(get_object_vars($note->getData()->mentions ?? (object) []));
        }

        $matches = null;

        preg_match_all('/(@[\w@.-]+)/', $note->getPost() ?? '', $matches);

        $mentionCount = 0;

        if (!empty($matches[0]) && is_array($matches[0])) {
            $mentionCount = $this->processMatches($matches[0], $note, $mentionData, $previousMentionList);
        }

        $data = $note->getData();

        if ($mentionCount) {
            $data->mentions = $mentionData;
        } else {
            unset($data->mentions);
        }

        $note->setData($data);
    }

    /**
     * @param string[] $matchList
     * @param string[] $previousMentionList
     */
    private function processMatches(
        array $matchList,
        Note $note,
        stdClass $mentionData,
        array $previousMentionList
    ): int {

        $mentionCount = 0;

        $parent = $note->getParentId() && $note->getParentType() ?
            $this->entityManager->getEntityById($note->getParentType(), $note->getParentId()) :
            null;

        foreach ($matchList as $item) {
            $userName = substr($item, 1);

            $user = $this->entityManager
                ->getRDBRepositoryByClass(User::class)
                ->where([
                    'userName' => $userName,
                    'isActive' => true,
                ])
                ->findOne();

            if (!$user) {
                continue;
            }

            if (!$this->acl->checkUserPermission($user, Permission::MENTION)) {
                continue;
            }

            $mentionData->$item = (object) [
                'id' => $user->getId(),
                'name' => $user->getName(),
                'userName' => $user->getUserName(),
                '_scope' => $user->getEntityType(),
            ];

            $mentionCount++;

            if (in_array($item, $previousMentionList)) {
                continue;
            }

            if ($user->getId() === $this->user->getId()) {
                continue;
            }

            if ($user->isPortal()) {
                continue;
            }

            if ($parent && !$this->aclManager->checkEntityStream($user, $parent)) {
                continue;
            }

            $note->addNotifiedUserId($user->getId());

            $this->service->notifyAboutMentionInPost($user->getId(), $note);
        }

        return $mentionCount;
    }
}
