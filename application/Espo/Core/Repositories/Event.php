<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2015 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

namespace Espo\Core\Repositories;

use Espo\ORM\Entity;
use Espo\Core\Utils\Util;

class Event extends \Espo\Core\ORM\Repositories\RDB
{
    protected $reminderDateAttribute = 'dateStart';

    protected function afterRemove(Entity $entity, array $options = array())
    {
        $pdo = $this->getEntityManager()->getPDO();
        $sql = "
            DELETE FROM `reminder`
            WHERE
                entity_id = ".$pdo->quote($entity->id)." AND
                entity_type = ".$pdo->quote($entity->getEntityType())." AND
                deleted = 0
        ";
        $pdo->query($sql);
    }

    protected function afterSave(Entity $entity, array $options = array())
    {
        parent::afterSave($entity, $options);

        if (
            $entity->isNew() ||
            $entity->isAttributeChanged('assignedUserId') ||
            $entity->isAttributeChanged('usersIds') ||
            $entity->isAttributeChanged($this->reminderDateAttribute) ||
            $entity->has('reminders')
        ) {
            $pdo = $this->getEntityManager()->getPDO();

            $reminderTypeList = $this->getMetadata()->get('entityDefs.Reminder.fields.type.options');

            if (!$entity->has('reminders')) {
                $reminderList = $this->getEntityReminderList($entity);
            } else {
                $reminderList = $entity->get('reminders');
            }

            if (!$entity->isNew()) {
                $sql = "
                    DELETE FROM `reminder`
                    WHERE
                        entity_id = ".$pdo->quote($entity->id)." AND
                        entity_type = ".$pdo->quote($entity->getEntityType())." AND
                        deleted = 0
                ";
                $pdo->query($sql);
            }

            if (empty($reminderList) || !is_array($reminderList)) return;

            $entityType = $entity->getEntityType();

            $dateValue = $entity->get($this->reminderDateAttribute);

            if (!$dateValue) {
                $e = $this->get($entity->id);
                if ($e) {
                    $dateValue = $e->get($this->reminderDateAttribute);
                }
            }

            if ($entity->hasLinkMultipleField('users')) {
                $userIdList = $entity->getLinkMultipleIdList('users');
            } else {
                $userIdList = [];
                if ($entity->get('assignedUserId')) {
                    $userIdList[] = $entity->get('assignedUserId');
                }
            }

            if (!$dateValue) return;
            if (empty($userIdList)) return;

            $dateValueObj = new \DateTime($dateValue);
            if (!$dateValueObj) return;

            foreach ($reminderList as $item) {
                $remindAt = clone $dateValueObj;
                $seconds = intval($item->seconds);
                $type = $item->type;

                if (!in_array($type , $reminderTypeList)) continue;

                $remindAt->sub(new \DateInterval('PT' . $seconds . 'S'));

                foreach ($userIdList as $userId) {
                    $id = Util::generateId();

                    $sql = "
                        INSERT
                        INTO `reminder`
                        (id, entity_id, entity_type, `type`, user_id, remind_at, start_at, `seconds`)
                        VALUES (
                            ".$pdo->quote($id).",
                            ".$pdo->quote($entity->id).",
                            ".$pdo->quote($entityType).",
                            ".$pdo->quote($type).",
                            ".$pdo->quote($userId).",
                            ".$pdo->quote($remindAt->format('Y-m-d H:i:s')).",
                            ".$pdo->quote($dateValue).",
                            ".$pdo->quote($seconds)."
                        )
                    ";
                    $pdo->query($sql);
                }
            }
        }
    }

    public function getEntityReminderList(Entity $entity)
    {
        $pdo = $this->getEntityManager()->getPDO();
        $reminderList = [];

        $sql = "
            SELECT DISTINCT `seconds`, `type`
            FROM `reminder`
            WHERE
                `entity_type` = ".$pdo->quote($entity->getEntityType())." AND
                `entity_id` = ".$pdo->quote($entity->id)." AND
                `deleted` = 0
            ORDER BY `seconds` ASC
        ";

        $sth = $pdo->prepare($sql);
        $sth->execute();
        $rows = $sth->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($rows as $row) {
            $o = new \StdClass();
            $o->seconds = intval($row['seconds']);
            $o->type = $row['type'];
            $reminderList[] = $o;
        }

        return $reminderList;
    }
}

