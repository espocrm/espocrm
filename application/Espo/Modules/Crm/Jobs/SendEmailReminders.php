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

namespace Espo\Modules\Crm\Jobs;

use Espo\Core\InjectableFactory;
use Espo\Core\Job\JobDataLess;
use Espo\Core\ORM\EntityManager;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\DateTime as DateTimeUtil;
use Espo\Core\Utils\Log;
use Espo\Modules\Crm\Tools\Reminder\Sender\EmailReminder;
use Espo\Modules\Crm\Entities\Reminder;
use Throwable;
use DateTime;
use DateInterval;

class SendEmailReminders implements JobDataLess
{
    private const MAX_PORTION_SIZE = 10;

    public function __construct(
        private InjectableFactory $injectableFactory,
        private EntityManager $entityManager,
        private Config $config,
        private Log $log
    ) {}

    public function run(): void
    {
        $dt = new DateTime();

        $now = $dt->format(DateTimeUtil::SYSTEM_DATE_TIME_FORMAT);

        $nowShifted = $dt
            ->sub(new DateInterval('PT1H'))
            ->format(DateTimeUtil::SYSTEM_DATE_TIME_FORMAT);

        $maxPortionSize = $this->config->get('emailReminderPortionSize') ?? self::MAX_PORTION_SIZE;

        $collection = $this->entityManager
            ->getRDBRepositoryByClass(Reminder::class)
            ->where([
                'type' => Reminder::TYPE_EMAIL,
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
            } catch (Throwable $e) {
                $this->log->error("Email reminder '{$entity->getId()}': " . $e->getMessage());
            }

            $this->entityManager
                ->getRDBRepository(Reminder::ENTITY_TYPE)
                ->deleteFromDb($entity->getId());
        }
    }
}
