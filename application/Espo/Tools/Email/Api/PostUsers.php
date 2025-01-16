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

namespace Espo\Tools\Email\Api;

use Espo\Core\Acl;
use Espo\Core\Api\Action;
use Espo\Core\Api\Request;
use Espo\Core\Api\Response;
use Espo\Core\Api\ResponseComposer;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\NotFound;
use Espo\Core\Field\LinkParent;
use Espo\Core\Notification\UserEnabledChecker;
use Espo\Core\Record\EntityProvider;
use Espo\Entities\Email;
use Espo\Entities\Notification;
use Espo\Entities\User;
use Espo\ORM\EntityManager;
use Espo\ORM\Name\Attribute;
use RuntimeException;

/**
 * @noinspection PhpUnused
 */
class PostUsers implements Action
{
    public function __construct(
        private EntityProvider $entityProvider,
        private Acl $acl,
        private EntityManager $entityManager,
        private UserEnabledChecker $userEnabledChecker,
        private User $user,
    ) {}

    public function process(Request $request): Response
    {
        $id = $request->getRouteParam('id') ?? throw new RuntimeException();
        $data = $request->getParsedBody();

        $email = $this->getEmail($id);

        $foreignIds = [];

        if (isset($data->id)) {
            $foreignIds[] = $data->id;
        }

        if (isset($data->ids) && is_array($data->ids)) {
            foreach ($data->ids as $foreignId) {
                $foreignIds[] = $foreignId;
            }
        }

        foreach ($foreignIds as $foreignId) {
            if (!is_string($foreignId)) {
                throw new BadRequest("Bad ID.");
            }
        }

        $relation = $this->entityManager->getRelation($email, 'users');

        foreach ($this->getUsers($foreignIds) as $user) {
            if ($relation->isRelated($user)) {
                continue;
            }

            $relation->relate($user);

            if ($this->user->getId() === $user->getId()) {
                continue;
            }

            $this->processNotify($email, $user);
        }

        return ResponseComposer::json(true);
    }

    /**
     * @throws Forbidden
     * @throws NotFound
     */
    private function getEmail(string $id): Email
    {
        $email = $this->entityProvider->getByClass(Email::class, $id);

        if (!$this->acl->checkEntityEdit($email)) {
            throw new Forbidden("No edit access to email.");
        }

        return $email;
    }

    /**
     * @param string[] $foreignIds
     * @return User[]
     * @throws Forbidden
     * @throws NotFound
     */
    private function getUsers(array $foreignIds): iterable
    {
        /** @var iterable<User> $users */
        $users = $this->entityManager
            ->getRDBRepositoryByClass(User::class)
            ->where([Attribute::ID => $foreignIds])
            ->find();

        if (is_countable($users) && count($users) !== count($foreignIds)) {
            throw new NotFound("Users not found.");
        }

        foreach ($users as $user) {
            if (!$this->acl->checkAssignmentPermission($user)) {
                throw new Forbidden("No assignment permission to user.");
            }

            if (!$this->acl->checkEntityRead($user)) {
                throw new Forbidden("No access to user.");
            }

            if (!$user->isRegular() && !$user->isAdmin()) {
                throw new Forbidden("Only regular and admin users allowed.");
            }
        }

        return $users;
    }

    private function processNotify(Email $email, User $user): void
    {
        if (!$this->userEnabledChecker->checkAssignment(Email::ENTITY_TYPE, $user->getId())) {
            return;
        }

        $notification = $this->entityManager->getRDBRepositoryByClass(Notification::class)->getNew();

        $notification
            ->setType('EmailInbox')
            ->setRelated(LinkParent::createFromEntity($email))
            ->setUserId($user->getId())
            ->setData([
                'emailName' => $email->getSubject(),
                'userId' => $this->user->getId(),
                'userName' => $this->user->getName(),
            ]);

        $this->entityManager->saveEntity($notification);
    }
}
