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

namespace Espo\Controllers;

use \Espo\Core\Exceptions\BadRequest;
use \Espo\Core\Exceptions\Forbidden;
use \Espo\Core\Exceptions\Error;
use \Espo\Core\Exceptions\NotFound;

class Email extends \Espo\Core\Controllers\Record
{
    public function postActionGetCopiedAttachments($params, $data, $request)
    {
        if (empty($data->id)) {
            throw new BadRequest();
        }
        $id = $data->id;

        return $this->getRecordService()->getCopiedAttachments($id);
    }

    public function postActionSendTestEmail($params, $data, $request)
    {
        if (!$this->getAcl()->checkScope('Email')) {
            throw new Forbidden();
        }

        if (is_null($data->password)) {
            if ($data->type == 'preferences') {
                if (!$this->getUser()->isAdmin() && $data->id !== $this->getUser()->id) {
                    throw new Forbidden();
                }
                $preferences = $this->getEntityManager()->getEntity('Preferences', $data->id);
                if (!$preferences) {
                    throw new NotFound();
                }

                if (is_null($data->password)) {
                    $data->password = $this->getContainer()->get('crypt')->decrypt($preferences->get('smtpPassword'));
                }
            } else if ($data->type == 'emailAccount') {
                if (!$this->getAcl()->checkScope('EmailAccount')) {
                    throw new Forbidden();
                }
                if (!empty($data->id)) {
                    $emailAccount = $this->getEntityManager()->getEntity('EmailAccount', $data->id);
                    if (!$emailAccount) {
                        throw new NotFound();
                    }
                    if (!$this->getUser()->isAdmin()) {
                        if ($emailAccount->get('assigniedUserId') !== $this->getUser()->id) {
                            throw new Forbidden();
                        }
                    }
                    if (is_null($data->password)) {
                        $data->password = $this->getContainer()->get('crypt')->decrypt($emailAccount->get('smtpPassword'));
                    }
                }
            } else if ($data->type == 'inboundEmail') {
                if (!$this->getUser()->isAdmin()) {
                    throw new Forbidden();
                }
                if (!empty($data->id)) {
                    $emailAccount = $this->getEntityManager()->getEntity('InboundEmail', $data->id);
                    if (!$emailAccount) {
                        throw new NotFound();
                    }
                    if (is_null($data->password)) {
                        $data->password = $this->getContainer()->get('crypt')->decrypt($emailAccount->get('smtpPassword'));
                    }
                }
            } else {
                if (!$this->getUser()->isAdmin()) {
                    throw new Forbidden();
                }
                if (is_null($data->password)) {
                    $data->password = $this->getConfig()->get('smtpPassword');
                }
            }
        }

        return $this->getRecordService()->sendTestEmail(get_object_vars($data));
    }

    public function postActionMarkAsRead($params, $data, $request)
    {
        if (!empty($data->ids)) {
            $idList = $data->ids;
        } else {
            if (!empty($data->id)) {
                $idList = [$data->id];
            } else {
                throw new BadRequest();
            }
        }
        return $this->getRecordService()->markAsReadByIdList($idList);
    }

    public function postActionMarkAsNotRead($params, $data, $request)
    {
        if (!empty($data->ids)) {
            $idList = $data->ids;
        } else {
            if (!empty($data->id)) {
                $idList = [$data->id];
            } else {
                throw new BadRequest();
            }
        }
        return $this->getRecordService()->markAsNotReadByIdList($idList);
    }

    public function postActionMarkAllAsRead($params, $data, $request)
    {
        return $this->getRecordService()->markAllAsRead();
    }

    public function postActionMarkAsImportant($params, $data, $request)
    {
        if (!empty($data->ids)) {
            $idList = $data->ids;
        } else {
            if (!empty($data->id)) {
                $idList = [$data->id];
            } else {
                throw new BadRequest();
            }
        }
        return $this->getRecordService()->markAsImportantByIdList($idList);
    }

    public function postActionMarkAsNotImportant($params, $data, $request)
    {
        if (!empty($data->ids)) {
            $idList = $data->ids;
        } else {
            if (!empty($data->id)) {
                $idList = [$data->id];
            } else {
                throw new BadRequest();
            }
        }
        return $this->getRecordService()->markAsNotImportantByIdList($idList);
    }

    public function postActionMoveToTrash($params, $data)
    {
        if (!empty($data->ids)) {
            $idList = $data->ids;
        } else {
            if (!empty($data->id)) {
                $idList = [$data->id];
            } else {
                throw new BadRequest();
            }
        }
        return $this->getRecordService()->moveToTrashByIdList($idList);
    }

    public function postActionRetrieveFromTrash($params, $data)
    {
        if (!empty($data->ids)) {
            $idList = $data->ids;
        } else {
            if (!empty($data->id)) {
                $idList = [$data->id];
            } else {
                throw new BadRequest();
            }
        }
        return $this->getRecordService()->retrieveFromTrashByIdList($idList);
    }

    public function getActionGetFoldersNotReadCounts(&$params, $request, $data)
    {
        return $this->getRecordService()->getFoldersNotReadCounts();
    }

    protected function fetchListParamsFromRequest(&$params, $request, $data)
    {
        parent::fetchListParamsFromRequest($params, $request, $data);

        $folderId = $request->get('folderId');
        if ($folderId) {
            $params['folderId'] = $request->get('folderId');
        }
    }

    public function postActionMoveToFolder($params, $data)
    {
        if (!empty($data->ids)) {
            $idList = $data->ids;
        } else {
            if (!empty($data->id)) {
                $idList = [$data->id];
            } else {
                throw new BadRequest();
            }
        }

        if (empty($data->folderId)) {
            throw new BadRequest();
        }
        return $this->getRecordService()->moveToFolderByIdList($idList, $data->folderId);
    }
}
