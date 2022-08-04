<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2022 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

use Espo\Core\Acl\Table;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Error;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\NotFound;

use Espo\Core\Controllers\Record;
use Espo\Core\Api\Request;

use Espo\Entities\Email as EmailEntity;
use Espo\Services\Email as Service;
use Espo\Services\EmailTemplate as EmailTemplateService;

use stdClass;

class Email extends Record
{
    /**
     * @throws BadRequest
     * @throws Forbidden
     * @throws NotFound
     */
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
     * @throws Forbidden
     * @throws NotFound
     * @throws Error
     * @throws BadRequest
     */
    public function postActionSendTestEmail(Request $request): bool
    {
        if (!$this->acl->checkScope(EmailEntity::ENTITY_TYPE)) {
            throw new Forbidden();
        }

        $data = get_object_vars($request->getParsedBody());

        $allowedParamList = [
            'type',
            'id',
            'username',
            'password',
            'auth',
            'authMechanism',
            'userId',
            'fromAddress',
            'fromName',
            'server',
            'port',
            'security',
            'emailAddress',
        ];

        foreach (array_keys($data) as $key) {
            if (!in_array($key, $allowedParamList)) {
                throw new BadRequest("Not allowed parameter `{$key}`.");
            }
        }

        $emailAddress = $data['emailAddress'] ?? null;

        if (!is_string($emailAddress)) {
            throw new BadRequest("No email address.");
        }

        /**
         * @var array{
         *     type?: ?string,
         *     id?: ?string,
         *     username?: ?string,
         *     password?: ?string,
         *     auth?: bool,
         *     authMechanism?: ?string,
         *     userId?: ?string,
         *     fromAddress?: ?string,
         *     fromName?: ?string,
         *     server: string,
         *     port: int,
         *     security: string,
         *     emailAddress: string,
         * } $data
         */

        $this->getEmailService()->sendTestEmail($data);

        return true;
    }

    /**
     * @throws BadRequest
     */
    public function postActionMarkAsRead(Request $request): bool
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

        $this->getEmailService()->markAsReadByIdList($idList);

        return true;
    }

    /**
     * @throws BadRequest
     */
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

    /**
     * @throws BadRequest
     */
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

    /**
     * @throws BadRequest
     */
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

    /**
     * @throws BadRequest
     */
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

    /**
     * @throws BadRequest
     */
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

    /**
     * @throws BadRequest
     */
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

    /**
     * @throws Forbidden
     */
    public function getActionGetInsertFieldData(Request $request): stdClass
    {
        if (!$this->acl->checkScope(EmailEntity::ENTITY_TYPE, Table::ACTION_CREATE)) {
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
