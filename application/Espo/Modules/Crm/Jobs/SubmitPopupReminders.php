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
    ORM\EntityManager,
    Utils\Config,
    WebSocket\Submission as WebSocketSubmission,
    Jobs\Job,
};

use Throwable;
use DateTime;

class SubmitPopupReminders implements Job
{
    const REMINDER_PAST_HOURS = 24;

    protected $entityManager;
    protected $config;
    protected $webSocketSubmission;

    public function __construct(EntityManager $entityManager, Config $config, WebSocketSubmission $webSocketSubmission)
    {
        $this->entityManager = $entityManager;
        $this->config = $config;
        $this->webSocketSubmission = $webSocketSubmission;
    }

    public function run() : void
    {
        if (!$this->config->get('useWebSocket')) {
            return;
        }

        $dt = new DateTime();

        $now = $dt->format('Y-m-d H:i:s');

        $pastHours = $this->config->get('reminderPastHours', self::REMINDER_PAST_HOURS);

        $nowShifted = $dt->modify('-' . $pastHours . ' hours')->format('Y-m-d H:i:s');

        $reminderList = $this->entityManager
            ->getRepository('Reminder')
            ->where([
                'type' => 'Popup',
                'remindAt<=' => $now,
                'startAt>' => $nowShifted,
                'isSubmitted' => false,
            ])
            ->find();

        $submitData = [];

        foreach ($reminderList as $reminder) {
            $userId = $reminder->get('userId');
            $entityType = $reminder->get('entityType');
            $entityId = $reminder->get('entityId');

            if (!$userId || !$entityType || !$entityId) {
                $this->deleteReminder($reminder);

                continue;
            }

            $entity = $this->entityManager->getEntity($entityType, $entityId);

            if (!$entity) {
                $this->deleteReminder($reminder);

                continue;
            }

            if ($entity->hasLinkMultipleField('users')) {
                $entity->loadLinkMultipleField('users', ['status' => 'acceptanceStatus']);

                $status = $entity->getLinkMultipleColumn('users', 'status', $userId);

                if ($status === 'Declined') {
                    $this->deleteReminder($reminder);

                    continue;
                }
            }

            $dateAttribute = 'dateStart';

            if ($entityType === 'Task') {
                $dateAttribute = 'dateEnd';
            }

            $data = [
                'id' => $reminder->id,
                'data' => [
                    'id' => $entity->id,
                    'entityType' => $entityType,
                    $dateAttribute => $entity->get($dateAttribute),
                    'name' => $entity->get('name'),
                ],
            ];

            if (!array_key_exists($userId, $submitData)) {
                $submitData[$userId] = [];
            }

            $submitData[$userId][] = $data;

            $reminder->set('isSubmitted', true);

            $this->entityManager->saveEntity($reminder);
        }

        foreach ($submitData as $userId => $list) {
            try {
                $this->webSocketSubmission->submit('popupNotifications.event', $userId, (object) [
                    'list' => $list
                ]);
            } catch (Throwable $e) {
                $GLOBALS['log']->error('Job SubmitPopupReminders: [' . $e->getCode() . '] ' .$e->getMessage());
            }
        }
    }

    protected function deleteReminder($reminder)
    {
        $this->entityManager->getRepository('Reminder')->deleteFromDb($reminder->id);
    }
}
