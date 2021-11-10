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

namespace Espo\Classes\Jobs;

use Espo\Core\{
    Utils\Config,
    ORM\EntityManager,
    Job\JobDataLess,
};

use DateTime;
use DateTimeZone;

class CheckNewVersion implements JobDataLess
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @var EntityManager
     */
    protected $entityManager;

    public function __construct(Config $config, EntityManager $entityManager)
    {
        $this->config = $config;
        $this->entityManager = $entityManager;
    }

    public function run(): void
    {
        if (!$this->config->get('adminNotifications') || !$this->config->get('adminNotificationsNewVersion')) {
            return;
        }

        $job = $this->entityManager->getEntity('Job');

        $job->set([
            'name' => 'Check for New Version (job)',
            'serviceName' => 'AdminNotifications',
            'methodName' => 'jobCheckNewVersion',
            'executeTime' => $this->getRunTime(),
        ]);

        $this->entityManager->saveEntity($job);

        return;
    }

    protected function getRunTime()
    {
        $hour = rand(0, 4);
        $minute = rand(0, 59);

        $nextDay = new DateTime('+ 1 day');
        $time = $nextDay->format('Y-m-d') . ' ' . $hour . ':' . $minute . ':00';

        $timeZone = $this->config->get('timeZone');

        if (empty($timeZone)) {
            $timeZone = 'UTC';
        }

        $datetime = new DateTime($time, new DateTimeZone($timeZone));

        return $datetime->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
    }

    /**
     * For backward compatibility.
     * @deprecated
     */
    protected function getEntityManager()
    {
        return $this->entityManager;
    }
}
