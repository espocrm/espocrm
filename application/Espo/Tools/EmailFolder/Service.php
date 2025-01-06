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

namespace Espo\Tools\EmailFolder;

use Espo\Core\Acl;
use Espo\Core\Exceptions\Error;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\ForbiddenSilent;
use Espo\Core\Exceptions\NotFound;
use Espo\Core\Name\Field;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\Language;
use Espo\Entities\Email;
use Espo\Entities\EmailFolder;
use Espo\Entities\GroupEmailFolder;
use Espo\Entities\User;
use Espo\ORM\EntityCollection;
use Espo\ORM\EntityManager;
use Espo\Tools\Email\Folder;

class Service
{
    /** @var string[] */
    protected $systemFolderList = [
        Folder::INBOX,
        Folder::IMPORTANT,
        Folder::SENT,
    ];
    /** @var string[] */
    protected $systemFolderEndList = [
        Folder::ARCHIVE,
        Folder::DRAFTS,
        Folder::TRASH,
    ];

    private const FOLDER_MAX_COUNT = 100;

    public function __construct(
        private EntityManager $entityManager,
        private Acl $acl,
        private Config $config,
        private User $user,
        private Language $language
    ) {}

    /**
     * @return array<array<string, mixed>>
     * @throws ForbiddenSilent
     * @throws NotFound
     */
    public function listAll(?string $userId = null)
    {
        if (
            $userId &&
            $userId !== $this->user->getId() &&
            !$this->user->isAdmin()
        ) {
            throw new ForbiddenSilent();
        }

        $userId ??= $this->user->getId();

        /** @var ?User $user */
        $user = $userId === $this->user->getId() ?
            $this->user :
            $this->entityManager->getEntityById(User::ENTITY_TYPE, $userId);

        if (!$user) {
            throw new NotFound();
        }

        $limit = $this->config->get('emailFolderMaxCount') ?? self::FOLDER_MAX_COUNT;

        $folderList = $this->entityManager
            ->getRDBRepositoryByClass(EmailFolder::class)
            ->where(['assignedUserId' => $userId])
            ->order('order')
            ->limit(0, $limit)
            ->find();

        $groupFolderList = $this->entityManager
            ->getRDBRepositoryByClass(GroupEmailFolder::class)
            ->distinct()
            ->leftJoin(Field::TEAMS)
            ->where(
                $user->isAdmin() ?
                    ['id!=' => null] :
                    ['teams.id' => $user->getTeamIdList()]
            )
            ->order('order')
            ->limit(0, $limit)
            ->find();

        /** @var EntityCollection<GroupEmailFolder|EmailFolder> $list */
        $list = new EntityCollection();

        foreach ($this->systemFolderList as $name) {
            $folder = $this->entityManager->getNewEntity(EmailFolder::ENTITY_TYPE);

            $folder->set(Field::NAME, $this->language->translate($name, 'presetFilters', Email::ENTITY_TYPE));
            $folder->set(Field::ID, $name);

            $list[] = $folder;
        }

        foreach ($folderList as $folder) {
            $list[] = $folder;
        }

        foreach ($groupFolderList as $folder) {
            $list[] = $folder;
        }

        foreach ($this->systemFolderEndList as $name) {
            $folder = $this->entityManager->getNewEntity(EmailFolder::ENTITY_TYPE);

            $folder->set(Field::NAME, $this->language->translate($name, 'presetFilters', Email::ENTITY_TYPE));
            $folder->set(Field::ID, $name);

            $list[] = $folder;
        }

        $finalList = [];

        foreach ($list as $item) {
            $attributes = get_object_vars($item->getValueMap());

            if ($item instanceof GroupEmailFolder) {
                $attributes['id'] = 'group:' . $item->getId();
            }

            $finalList[] = $attributes;
        }

        return $finalList;
    }

    /**
     * @throws Forbidden
     * @throws Error
     * @throws NotFound
     */
    public function moveUp(string $id): void
    {
        $entity = $this->entityManager->getEntityById(EmailFolder::ENTITY_TYPE, $id);

        if (!$entity) {
            throw new NotFound();
        }

        if (!$this->acl->check($entity, 'edit')) {
            throw new Forbidden();
        }

        $currentIndex = $entity->get('order');

        if (!is_int($currentIndex)) {
            throw new Error();
        }

        $previousEntity = $this->entityManager
            ->getRDBRepositoryByClass(EmailFolder::class)
            ->where([
                'order<' => $currentIndex,
                'assignedUserId' => $entity->get('assignedUserId'),
            ])
            ->order('order', true)
            ->findOne();

        if (!$previousEntity) {
            return;
        }

        $entity->set('order', $previousEntity->get('order'));
        $previousEntity->set('order', $currentIndex);

        $this->entityManager->saveEntity($entity);
        $this->entityManager->saveEntity($previousEntity);
    }

    /**
     * @throws Forbidden
     * @throws Error
     * @throws NotFound
     */
    public function moveDown(string $id): void
    {
        $entity = $this->entityManager->getEntityById(EmailFolder::ENTITY_TYPE, $id);

        if (!$entity) {
            throw new NotFound();
        }

        if (!$this->acl->checkEntityEdit($entity)) {
            throw new Forbidden();
        }

        $currentIndex = $entity->get('order');

        if (!is_int($currentIndex)) {
            throw new Error();
        }

        $nextEntity = $this->entityManager
            ->getRDBRepositoryByClass(EmailFolder::class)
            ->where([
                'order>' => $currentIndex,
                'assignedUserId' => $entity->get('assignedUserId'),
            ])
            ->order('order', false)
            ->findOne();

        if (!$nextEntity) {
            return;
        }

        $entity->set('order', $nextEntity->get('order'));
        $nextEntity->set('order', $currentIndex);

        $this->entityManager->saveEntity($entity);
        $this->entityManager->saveEntity($nextEntity);
    }
}
