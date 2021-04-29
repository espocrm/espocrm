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

use Espo\Core\Exceptions\Forbidden;


use Espo\Core\{
    Controllers\RecordBase,
    Api\Request,
    Api\Response,
};

use StdClass;

class ExternalAccount extends RecordBase
{
    public static $defaultAction = 'list';

    protected function checkAccess(): bool
    {
        return $this->acl->checkScope('ExternalAccount');
    }

    public function getActionList(Request $request, Response $response): StdClass
    {
        $integrations = $this->entityManager->getRepository('Integration')->find();

        $list = [];

        foreach ($integrations as $entity) {
            if (
                $entity->get('enabled') &&
                $this->metadata->get('integrations.' . $entity->id .'.allowUserAccounts')
            ) {

                $userAccountAclScope = $this->metadata
                    ->get(['integrations', $entity->id, 'userAccountAclScope']);

                if ($userAccountAclScope) {
                    if (!$this->acl->checkScope($userAccountAclScope)) {
                        continue;
                    }
                }

                $list[] = [
                    'id' => $entity->getId(),
                ];
            }
        }

        return (object) [
            'list' => $list
        ];
    }

    public function getActionGetOAuth2Info(Request $request): ?StdClass
    {
        $id = $request->getQueryParam('id');

        list($integration, $userId) = explode('__', $id);

        if ($this->user->getId() != $userId && !$this->user->isAdmin()) {
            throw new Forbidden();
        }

        $entity = $this->entityManager->getEntity('Integration', $integration);

        if ($entity) {
            return (object) [
                'clientId' => $entity->get('clientId'),
                'redirectUri' => $this->config->get('siteUrl') . '?entryPoint=oauthCallback',
                'isConnected' => $this->getRecordService()->ping($integration, $userId)
            ];
        }

        return null;
    }

    public function getActionRead(Request $request, Response $response): StdClass
    {
        $id = $request->getRouteParam('id');

        return $this->getRecordService()->read($id)->getValueMap();
    }

    public function putActionUpdate(Request $request, Response $response): StdClass
    {
        $id = $request->getRouteParam('id');

        $data = $request->getParsedBody();

        list($integration, $userId) = explode('__', $id);

        if ($this->user->getId() !== $userId && !$this->user->isAdmin()) {
            throw new Forbidden();
        }

        if (isset($data->enabled) && !$data->enabled) {
            $data->data = null;
        }

        $entity = $this->entityManager->getEntity('ExternalAccount', $id);

        $entity->set($data);

        $this->entityManager->saveEntity($entity);

        return $entity->getValueMap();
    }

    public function postActionAuthorizationCode(Request $request)
    {
        $data = $request->getParsedBody();

        $id = $data->id;
        $code = $data->code;

        list($integration, $userId) = explode('__', $id);

        if ($this->user->getId() != $userId && !$this->user->isAdmin()) {
            throw new Forbidden();
        }

        $service = $this->getRecordService();

        return $service->authorizationCode($integration, $userId, $code);
    }
}
