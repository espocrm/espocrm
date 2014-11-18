<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014  Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
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
        
        if (!in_array($action, array('accept', 'decline'))) {
            throw new BadRequest();
        }
                
        $uniqueId = $this->getEntityManager()->getRepository('UniqueId')->where(array('name' => $uid))->findOne();
        
        if (!$uniqueId) {
            throw new NotFound();
            return;
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
            if ($event && $invitee) {
                $relDefs = $event->getRelations();
                $tableName = Util::toUnderscore($relDefs[$link]['relationName']);
                
                $status = 'None';
                if ($action == 'accept') {
                    $status = 'Accepted';
                } else if ($action == 'decline') {
                    $status = 'Declined';
                }
                
                $pdo = $this->getEntityManager()->getPDO();
                $sql = "
                    UPDATE `{$tableName}` SET status = '{$status}'
                    WHERE ".strtolower($eventType)."_id = '{$eventId}' AND ".strtolower($inviteeType)."_id = '{$inviteeId}'
                ";

                $sth = $pdo->prepare($sql);
                $sth->execute();
                
                $this->getEntityManager()->getRepository('UniqueId')->remove($uniqueId);
                
                echo $status;
                return;
            }
        }
        
        throw new Error();
    }
}

