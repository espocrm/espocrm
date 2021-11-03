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

namespace Espo\Modules\Crm\Jobs;

use Espo\Core\{
    InjectableFactory,
    ORM\EntityManager,
    Utils\Config,
    Job\JobDataLess,
    Utils\Log,
};

use Espo\Modules\Crm\Business\Reminder\EmailReminder;

use Throwable;
use DateTime;
use DateInterval;

class SendEmailReminders implements JobDataLess
{
    private const MAX_PORTION_SIZE = 10;

    private $injectableFactory;

    private $entityManager;

    private $config;

    private $log;

    public function __construct(
        InjectableFactory $injectableFactory,
        EntityManager $entityManager,
        Config $config,
        Log $log
    ) {
        $this->injectableFactory = $injectableFactory;
        $this->entityManager = $entityManager;
        $this->config = $config;
        $this->log = $log;
    }

    public function run(): void
    {
        $dt = new DateTime();

        $now = $dt->format('Y-m-d H:i:s');

        $nowShifted = $dt->sub(new DateInterval('PT1H'))->format('Y-m-d H:i:s');

        $maxPortionSize = $this->config->get('emailReminderPortionSize') ?? self::MAX_PORTION_SIZE;

        $collection = $this->entityManager
            ->getRDBRepository('Reminder')
            ->where([
                'type' => 'Email',
                'remindAt<=' => $now,
                'startAt>' => $nowShifted,
            ])
            ->limit(0, $maxPortionSize)
            ->find();

        if (count($collection) === 0) {
            return;
        }

        $emailReminder = $this->injectableFactory->create(EmailReminder::class);

        foreach ($collection as $entity) {
            try {
                $emailReminder->send($entity);
            }
            catch (Throwable $e) {
                $this->log->error(
                    "Email reminder '{$entity->getId()}': " . $e->getMessage()
                );
            }

            $this->entityManager
                ->getRDBRepository('Reminder')
                ->deleteFromDb($entity->getId());
        }
    }
}
