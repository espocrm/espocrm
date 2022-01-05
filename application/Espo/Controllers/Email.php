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

namespace Espo\Controllers;

use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\NotFound;

use Espo\Core\Controllers\Record;
use Espo\Core\Api\Request;

use Espo\Services\Email as Service;
use Espo\Services\EmailTemplate as EmailTemplateService;

use stdClass;

class Email extends Record
{
    public function postActionGetCopiedAttachments(Request $request): stdClass
    {
        $data = $request->getParsedBody();

        if (empty($data->id)) {
            throw new BadRequest();
        }

        $id = $data->id;
        $parentType = $data->parentType ?? null;
        $field = $data->field ?? null;

        return $this->getEmailService()->getCopiedAttachments($id, $parentType, null, $field);
    }

    /**
     * @todo Move to service.
     */
    public function postActionSendTestEmail(Request $request)
    {
        $data = $request->getParsedBody();

        if (!$this->acl->checkScope('Email')) {
            throw new Forbidden();
        }

        if (is_null($data->password)) {
            if ($data->type == 'preferences') {
                if (!$this->user->isAdmin() && $data->id !== $this->user->id) {
                    throw new Forbidden();
                }

                $preferences = $this->getEntityManager()->getEntity('Preferences', $data->id);

                if (!$preferences) {
                    throw new NotFound();
                }

                if (is_null($data->password)) {
                    $data->password = $this->getContainer()
                        ->get('crypt')
                        ->decrypt($preferences->get('smtpPassword'));
                }
            }
            else if ($data->type == 'emailAccount') {
                if (!$this->acl->checkScope('EmailAccount')) {
                    throw new Forbidden();
                }

                if (!empty($data->id)) {
                    $emailAccount = $this->getEntityManager()
                        ->getEntity('EmailAccount', $data->id);

                    if (!$emailAccount) {
                        throw new NotFound();
                    }

                    if (!$this->user->isAdmin()) {
                        if ($emailAccount->get('assignedUserId') !== $this->user->id) {
                            throw new Forbidden();
                        }
                    }
                    if (is_null($data->password)) {
                        $data->password = $this->getContainer()
                            ->get('crypt')
                            ->decrypt($emailAccount->get('smtpPassword'));
                    }
                }
            }
            else if ($data->type == 'inboundEmail') {
                if (!$this->user->isAdmin()) {
                    throw new Forbidden();
                }

                if (!empty($data->id)) {
                    $emailAccount = $this->getEntityManager()->getEntity('InboundEmail', $data->id);

                    if (!$emailAccount) {
                        throw new NotFound();
                    }

                    if (is_null($data->password)) {
                        $data->password = $this->getContainer()
                            ->get('crypt')
                            ->decrypt($emailAccount->get('smtpPassword'));
                    }
                }
            }
            else {
                if (!$this->user->isAdmin()) {
                    throw new Forbidden();
                }

                if (is_null($data->password)) {
                    $data->password = $this->getConfig()->get('smtpPassword');
                }
            }
        }

        return $this->getEmailService()->sendTestEmail(get_object_vars($data));
    }

    public function postActionMarkAsRead(Request $request)
    {
        $data = $request->getParsedBody();

        if (!empty($data->ids)) {
            $idList = $data->ids;
        }
        else {
            if (!empty($data->id)) {
                $idList = [$data->id];
            }
            else {
                throw new BadRequest();
            }
        }

        return $this->getEmailService()->markAsReadByIdList($idList);
    }

    public function postActionMarkAsNotRead(Request $request): bool
    {
        $data = $request->getParsedBody();

        if (!empty($data->ids)) {
            $idList = $data->ids;
        }
        else {
            if (!empty($data->id)) {
                $idList = [$data->id];
            }
            else {
                throw new BadRequest();
            }
        }

        $this->getEmailService()->markAsNotReadByIdList($idList);

        return true;
    }

    public function postActionMarkAllAsRead(): bool
    {
        $this->getEmailService()->markAllAsRead();

        return true;
    }

    public function postActionMarkAsImportant(Request $request): bool
    {
        $data = $request->getParsedBody();

        if (!empty($data->ids)) {
            $idList = $data->ids;
        }
        else {
            if (!empty($data->id)) {
                $idList = [$data->id];
            }
            else {
                throw new BadRequest();
            }
        }

        $this->getEmailService()->markAsImportantByIdList($idList);

        return true;
    }

    public function postActionMarkAsNotImportant(Request $request): bool
    {
        $data = $request->getParsedBody();

        if (!empty($data->ids)) {
            $idList = $data->ids;
        }
        else {
            if (!empty($data->id)) {
                $idList = [$data->id];
            }
            else {
                throw new BadRequest();
            }
        }

        $this->getEmailService()->markAsNotImportantByIdList($idList);

        return true;
    }

    public function postActionMoveToTrash(Request $request): bool
    {
        $data = $request->getParsedBody();

        if (!empty($data->ids)) {
            $idList = $data->ids;
        }
        else {
            if (!empty($data->id)) {
                $idList = [$data->id];
            }
            else {
                throw new BadRequest();
            }
        }

        $this->getEmailService()->moveToTrashByIdList($idList);

        return true;
    }

    public function postActionRetrieveFromTrash(Request $request): bool
    {
        $data = $request->getParsedBody();

        if (!empty($data->ids)) {
            $idList = $data->ids;
        }
        else {
            if (!empty($data->id)) {
                $idList = [$data->id];
            }
            else {
                throw new BadRequest();
            }
        }

        $this->getEmailService()->retrieveFromTrashByIdList($idList);

        return true;
    }

    public function getActionGetFoldersNotReadCounts(): stdClass
    {
        return $this->getEmailService()->getFoldersNotReadCounts();
    }

    public function postActionMoveToFolder(Request $request): bool
    {
        $data = $request->getParsedBody();

        if (!empty($data->ids)) {
            $idList = $data->ids;
        }
        else if (!empty($data->id)) {
            $idList = [$data->id];
        }
        else {
            throw new BadRequest();
        }

        if (empty($data->folderId)) {
            throw new BadRequest();
        }

        $this->getEmailService()->moveToFolderByIdList($idList, $data->folderId);

        return true;
    }

    public function getActionGetInsertFieldData(Request $request)
    {
        if (!$this->acl->checkScope('Email', 'create')) {
            throw new Forbidden();
        }

        return $this->getEmailTemplateService()->getInsertFieldData([
            'parentId' => $request->getQueryParam('parentId'),
            'parentType' => $request->getQueryParam('parentType'),
            'to' => $request->getQueryParam('to'),
        ]);
    }

    private function getEmailService(): Service
    {
        /** @var Service */
        return $this->getRecordService();
    }

    private function getEmailTemplateService(): EmailTemplateService
    {
        /** @var EmailTemplateService */
        return $this->getServiceFactory()->create('EmailTemplate');
    }
}
