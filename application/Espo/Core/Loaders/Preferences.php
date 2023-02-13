<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2023 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

namespace Espo\Core\Loaders;

use Espo\Core\ApplicationState;
use Espo\Core\ApplicationUser as ApplicationUser;
use Espo\Core\Container\Loader;
use Espo\Core\ORM\EntityManager;

use Espo\Entities\Preferences as PreferencesEntity;

class Preferences implements Loader
{
    public function __construct(
        private EntityManager $entityManager,
        private ApplicationState $applicationState
    ) {}

    public function load(): PreferencesEntity
    {
        $id = ApplicationUser::SYSTEM_USER_ID;

        if ($this->applicationState->hasUser()) {
            $id = $this->applicationState->getUser()->getId();
        }

        /** @var PreferencesEntity */
        return $this->entityManager->getEntity(PreferencesEntity::ENTITY_TYPE, $id);
    }
}
