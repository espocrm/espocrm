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

namespace Espo\Core\Notification;

use Espo\Core\{
    ORM\EntityManager,
};

class UserEnabledChecker
{
    private $assignmentCache = [];

    private $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function checkAssignment(string $entityType, string $userId): bool
    {
        $key = $entityType . '_' . $userId;

        if (!array_key_exists($key, $this->assignmentCache)) {
            $preferences = $this->entityManager->getEntity('Preferences', $userId);

            $isEnabled = false;

            $ignoreList = [];

            if ($preferences) {
                $isEnabled = true;

                $ignoreList = $preferences->get('assignmentNotificationsIgnoreEntityTypeList') ?? [];
            }

            if ($preferences && in_array($entityType, $ignoreList)) {
                $isEnabled = false;
            }

            $this->assignmentCache[$key] = $isEnabled;
        }

        return $this->assignmentCache[$key];
    }
}
