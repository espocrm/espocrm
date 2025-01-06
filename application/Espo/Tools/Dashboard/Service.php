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

namespace Espo\Tools\Dashboard;

use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\NotFound;
use Espo\Core\Name\Field;
use Espo\Entities\DashboardTemplate;
use Espo\Entities\Preferences;
use Espo\Entities\Team;
use Espo\Entities\User;
use Espo\ORM\Entity;
use Espo\ORM\EntityManager;

class Service
{
    private EntityManager $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param string[] $userIdList
     * @throws NotFound
     * @throws Forbidden
     */
    public function deployTemplateToUsers(string $id, array $userIdList, bool $append = false): void
    {
        $template = $this->entityManager->getEntityById(DashboardTemplate::ENTITY_TYPE, $id);

        if (!$template) {
            throw new NotFound();
        }

        foreach ($userIdList as $userId) {
            /** @var ?User $user */
            $user = $this->entityManager->getEntityById(User::ENTITY_TYPE, $userId);

            if (!$user) {
                throw new NotFound("User not found.");
            }

            if ($user->isPortal() || $user->isApi()) {
                throw new Forbidden("Not allowed user type.");
            }
        }

        foreach ($userIdList as $userId) {
            $preferences = $this->entityManager->getEntityById(Preferences::ENTITY_TYPE, $userId);

            if (!$preferences) {
                continue;
            }

            $this->applyTemplate($preferences, $template, $append);

            $this->entityManager->saveEntity($preferences);
        }
    }

    /**
     * @throws NotFound
     */
    public function deployTemplateToTeam(string $id, string $teamId, bool $append = false): void
    {
        /** @var ?DashboardTemplate $template */
        $template = $this->entityManager->getEntityById(DashboardTemplate::ENTITY_TYPE, $id);

        if (!$template) {
            throw new NotFound();
        }

        $team = $this->entityManager->getEntityById(Team::ENTITY_TYPE, $teamId);

        if (!$team) {
            throw new NotFound();
        }

        $userList = $this->entityManager
            ->getRDBRepository(User::ENTITY_TYPE)
            ->join(Field::TEAMS)
            ->distinct()
            ->where([
                Field::TEAMS . '.id' => $teamId,
            ])
            ->find();

        foreach ($userList as $user) {
            $preferences = $this->entityManager->getEntityById(Preferences::ENTITY_TYPE, $user->getId());

            if (!$preferences) {
                continue;
            }

            $this->applyTemplate($preferences, $template, $append);

            $this->entityManager->saveEntity($preferences);
        }
    }

    private function applyTemplate(Entity $preferences, DashboardTemplate $template, bool $append): void
    {
        if (!$append) {
            $preferences->set([
                'dashboardLayout' => $template->get('layout'),
                'dashletsOptions' => $template->get('dashletsOptions'),
            ]);
        } else {
            $dashletsOptions = $preferences->get('dashletsOptions');

            if (!$dashletsOptions) {
                $dashletsOptions = (object) [];
            }

            $dashboardLayout = $preferences->get('dashboardLayout');

            if (!$dashboardLayout) {
                $dashboardLayout = [];
            }

            foreach ($template->get('layout') as $item) {
                $exists = false;

                foreach ($dashboardLayout as $k => $item2) {
                    if (isset($item->id) && isset($item2->id)) {
                        if ($item->id === $item2->id) {
                            $exists = true;
                            $dashboardLayout[$k] = $item;
                        }
                    }
                }

                if (!$exists) {
                    $dashboardLayout[] = $item;
                }
            }

            foreach ($template->get('dashletsOptions') as $id => $item) {
                $dashletsOptions->$id = $item;
            }

            $preferences->set([
                'dashboardLayout' => $dashboardLayout,
                'dashletsOptions' => $dashletsOptions,
            ]);
        }
    }
}
