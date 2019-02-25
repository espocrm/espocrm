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

namespace Espo\Entities;

class Note extends \Espo\Core\ORM\Entity
{
    private $aclIsProcessed = false;

    public function setAclIsProcessed()
    {
        $this->aclIsProcessed = true;
    }

    public function isAclProcessed()
    {
        return $this->aclIsProcessed;
    }

    public function loadAttachments()
    {
        $data = $this->get('data');
        if (!empty($data) && !empty($data->attachmentsIds) && is_array($data->attachmentsIds)) {
            $attachmentsIds = $data->attachmentsIds;
            $collection = $this->entityManager->getRepository('Attachment')->select(['id', 'name', 'type'])->order('createdAt')->where([
                'id' => $attachmentsIds
            ])->find();
        } else {
            $this->loadLinkMultipleField('attachments');
            return;
        }

        $ids = array();
        $names = new \stdClass();
        $types = new \stdClass();
        foreach ($collection as $e) {
            $id = $e->id;
            $ids[] = $id;
            $names->$id = $e->get('name');
            $types->$id = $e->get('type');
        }
        $this->set('attachmentsIds', $ids);
        $this->set('attachmentsNames', $names);
        $this->set('attachmentsTypes', $types);
    }

    public function addNotifiedUserId($userId)
    {
        $userIdList = $this->get('notifiedUserIdList');
        if (!is_array($userIdList)) {
            $userIdList = [];
        }
        if (!in_array($userId, $userIdList)) {
            $userIdList[] = $userId;
        }
        $this->set('notifiedUserIdList', $userIdList);
    }

    public function isUserIdNotified($userId)
    {
        $userIdList = $this->get('notifiedUserIdList');
        if (!is_array($userIdList)) {
            $userIdList = [];
        }
        return in_array($userId, $userIdList);
    }
}
