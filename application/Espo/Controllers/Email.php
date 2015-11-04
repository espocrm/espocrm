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

namespace Espo\Controllers;

use \Espo\Core\Exceptions\BadRequest;
use \Espo\Core\Exceptions\Forbidden;
use \Espo\Core\Exceptions\Error;

class Email extends \Espo\Core\Controllers\Record
{
    public function actionGetCopiedAttachments($params, $data, $request)
    {
        $id = $request->get('id');

        return $this->getRecordService()->getCopiedAttachments($id);
    }

    public function actionSendTestEmail($params, $data, $request)
    {
        if (!$request->isPost()) {
            throw new BadRequest();
        }

        if (is_null($data['password'])) {
            if ($data['type'] == 'preferences') {
                if (!$this->getUser()->isAdmin() && $data['id'] != $this->getUser()->id) {
                    throw new Forbidden();
                }
                $preferences = $this->getEntityManager()->getEntity('Preferences', $data['id']);
                if (!$preferences) {
                    throw new Error();
                }

                $data['password'] = $this->getContainer()->get('crypt')->decrypt($preferences->get('smtpPassword'));
            } else {
                if (!$this->getUser()->isAdmin()) {
                    throw new Forbidden();
                }
                $data['password'] = $this->getConfig()->get('smtpPassword');
            }
        }

        return $this->getRecordService()->sendTestEmail($data);
    }

    public function postActionMarkAsRead($params, $data, $request)
    {
        if (!empty($data['ids'])) {
            $ids = $data['ids'];
        } else {
            if (!empty($data['id'])) {
                $ids = [$data['id']];
            } else {
                throw new BadRequest();
            }
        }
        return $this->getRecordService()->markAsReadByIds($ids);
    }

    public function postActionMarkAsNotRead($params, $data, $request)
    {
        if (!empty($data['ids'])) {
            $ids = $data['ids'];
        } else {
            if (!empty($data['id'])) {
                $ids = [$data['id']];
            } else {
                throw new BadRequest();
            }
        }
        return $this->getRecordService()->markAsNotReadByIds($ids);
    }

    public function postActionMarkAllAsRead($params, $data, $request)
    {
        return $this->getRecordService()->markAllAsRead();
    }

    public function postActionMarkAsImportant($params, $data, $request)
    {
        if (!empty($data['ids'])) {
            $ids = $data['ids'];
        } else {
            if (!empty($data['id'])) {
                $ids = [$data['id']];
            } else {
                throw new BadRequest();
            }
        }
        return $this->getRecordService()->markAsImportantByIds($ids);
    }

    public function postActionMarkAsNotImportant($params, $data, $request)
    {
        if (!empty($data['ids'])) {
            $ids = $data['ids'];
        } else {
            if (!empty($data['id'])) {
                $ids = [$data['id']];
            } else {
                throw new BadRequest();
            }
        }
        return $this->getRecordService()->markAsNotImportantByIds($ids);
    }
}

