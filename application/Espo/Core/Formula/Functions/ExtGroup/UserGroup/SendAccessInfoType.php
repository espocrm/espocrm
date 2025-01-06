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

namespace Espo\Core\Formula\Functions\ExtGroup\UserGroup;

use Espo\Core\Formula\Functions\BaseFunction;
use Espo\Core\Formula\ArgumentList;
use Espo\Core\Job\JobSchedulerFactory;

use Espo\Tools\UserSecurity\Password\Jobs\SendAccessInfo as SendAccessInfoJob;
use Espo\Core\Job\Job\Data as JobData;

use Espo\Entities\User;

use Espo\Core\Di;

class SendAccessInfoType extends BaseFunction implements

    Di\EntityManagerAware,
    Di\InjectableFactoryAware
{
    use Di\EntityManagerSetter;
    use Di\InjectableFactorySetter;

    public function process(ArgumentList $args)
    {
        if (count($args) < 1) {
            $this->throwTooFewArguments(1);
        }

        $evaluatedArgs = $this->evaluate($args);

        $userId = $evaluatedArgs[0];

        if (!$userId || !is_string($userId)) {
            $this->throwBadArgumentType(1, 'string');
        }

        $user = $this->entityManager->getEntityById(User::ENTITY_TYPE, $userId);

        if (!$user) {
            $this->log("User '$userId' does not exist.");

            return;
        }

        $this->createJobScheduledFactory()
            ->create()
            ->setClassName(SendAccessInfoJob::class)
            ->setData(
                JobData::create()->withTargetId($user->getId())
            )
            ->schedule();
    }

    private function createJobScheduledFactory(): JobSchedulerFactory
    {
        return $this->injectableFactory->create(JobSchedulerFactory::class);
    }
}
