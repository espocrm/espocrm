<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

namespace Espo\Modules\Crm\EntryPoints;

use \Espo\Core\Utils\Util;

use \Espo\Core\Exceptions\NotFound;
use \Espo\Core\Exceptions\Forbidden;
use \Espo\Core\Exceptions\BadRequest;
use \Espo\Core\Exceptions\Error;

class EventConfirmation extends \Espo\Core\EntryPoints\Base
{
    public static $authRequired = false;

    public function run()
    {
        $uid = $_GET['uid'];
        $action = $_GET['action'];
        if (empty($uid) || empty($action)) {
            throw new BadRequest();
        }

        if (!in_array($action, array('accept', 'decline', 'tentative'))) {
            throw new BadRequest();
        }

        $uniqueId = $this->getEntityManager()->getRepository('UniqueId')->where(array('name' => $uid))->findOne();

        if (!$uniqueId) {
            throw new NotFound();
        }

        $data = $uniqueId->get('data');

        $eventType = $data->eventType;
        $eventId = $data->eventId;
        $inviteeType = $data->inviteeType;
        $inviteeId = $data->inviteeId;
        $link = $data->link;

        if (!empty($eventType) && !empty($eventId) && !empty($inviteeType) && !empty($inviteeId) && !empty($link)) {
            $event = $this->getEntityManager()->getEntity($eventType, $eventId);
            $invitee = $this->getEntityManager()->getEntity($inviteeType, $inviteeId);

            if (!$event || !$invitee) {
                throw new NotFound();
            }

            if ($event->get('status') === 'Held' || $event->get('status') === 'Not Held') {
                throw new NotFound();
            }

            $status = 'None';
            $hookMethodName = 'afterConfirmation';
            if ($action == 'accept') {
                $status = 'Accepted';
            } else if ($action == 'decline') {
                $status = 'Declined';
            } else if ($action == 'tentative') {
                $status = 'Tentative';
            }

            $data = (object) [
                'status' => $status
            ];
            $this->getEntityManager()->getRepository($eventType)->updateRelation($event, $link, $invitee->id, $data);

            $actionData = [
                'eventName' => $event->get('name'),
                'eventType' => $event->getEntityType(),
                'eventId' => $event->id,
                'dateStart' => $event->get('dateStart'),
                'action' => $action,
                'status' => $status,
                'link' => $link,
                'inviteeType' => $invitee->getEntityType(),
                'inviteeId' => $invitee->id
            ];

            $this->getEntityManager()->getHookManager()->process($event->getEntityType(), $hookMethodName, $event, [], $actionData);

            $runScript = "
                Espo.require('crm:controllers/event-confirmation', function (Controller) {
                    var controller = new Controller(app.baseController.params, app.getControllerInjection());
                    controller.masterView = app.masterView;
                    controller.doAction('confirmEvent', ".json_encode($actionData).");
                });
            ";

            $this->getClientManager()->display($runScript);

            return;
        }

        throw new Error();
    }
}
