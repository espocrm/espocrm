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

namespace Espo\Services;

use Espo\ORM\Entity;

use Espo\Core\Exceptions\NotFound;
use Espo\Core\Exceptions\Forbidden;

use Espo\Entities\User;

class DashboardTemplate extends Record
{
    protected function applyLayout(Entity $preferences, Entity $template, bool $append)
    {
        if (!$append) {
            $preferences->set([
                'dashboardLayout' => $template->get('layout'),
                'dashletsOptions' => $template->get('dashletsOptions'),
            ]);
        }
        else {
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

    public function deployToUsers(string $id, array $userIdList, bool $append = false): void
    {
        $template = $this->getEntityManager()->getEntity('DashboardTemplate', $id);

        if (!$template) {
            throw new NotFound();
        }

        foreach ($userIdList as $userId) {
            /** @var User|null $user */
            $user = $this->getEntityManager()->getEntity('User', $userId);

            if ($user) {
                if ($user->isPortal() || $user->isApi()) {
                    throw new Forbidden("Not allowed user type.");
                }
            }
        }

        foreach ($userIdList as $userId) {
            $preferences = $this->getEntityManager()->getEntity('Preferences', $userId);

            if (!$preferences) {
                continue;
            }

            $this->applyLayout($preferences, $template, $append);

            $this->getEntityManager()->saveEntity($preferences);
        }
    }

    public function deployToTeam(string $id, string $teamId, bool $append = false): void
    {
        $template = $this->getEntityManager()->getEntity('DashboardTemplate', $id);

        if (!$template) {
            throw new NotFound();
        }

        $team = $this->getEntityManager()->getEntity('Team', $teamId);

        if (!$team) {
            throw new NotFound();
        }

        $userList = $this->getEntityManager()
            ->getRDBRepository('User')
            ->join('teams')
            ->distinct()
            ->where([
                'teams.id' => $teamId,
            ])
            ->find();

        foreach ($userList as $user) {
            $preferences = $this->getEntityManager()->getEntity('Preferences', $user->getId());

            if (!$preferences) {
                continue;
            }

            $this->applyLayout($preferences, $template, $append);

            $this->getEntityManager()->saveEntity($preferences);
        }
    }
}
