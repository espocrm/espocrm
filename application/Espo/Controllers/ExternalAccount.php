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

use \Espo\Core\Exceptions\Error;
use \Espo\Core\Exceptions\Forbidden;
use \Espo\Core\Exceptions\BadRequest;

class ExternalAccount extends \Espo\Core\Controllers\Record
{
    public static $defaultAction = 'list';

    protected function checkControllerAccess()
    {
        if (!$this->getAcl()->checkScope('ExternalAccount')) {
            throw new Forbidden();
        }
    }

    public function actionList($params, $data, $request)
    {
        $integrations = $this->getEntityManager()->getRepository('Integration')->find();

        $list = [];
        foreach ($integrations as $entity) {
            if ($entity->get('enabled') && $this->getMetadata()->get('integrations.' . $entity->id .'.allowUserAccounts')) {

                $userAccountAclScope = $this->getMetadata()->get(['integrations', $entity->id, 'userAccountAclScope']);

                if ($userAccountAclScope) {
                    if (!$this->getAcl()->checkScope($userAccountAclScope)) {
                        continue;
                    }
                }

                $list[] = [
                    'id' => $entity->id
                ];
            }
        }
        return [
            'list' => $list
        ];
    }

    public function actionGetOAuth2Info($params, $data, $request)
    {
        $id = $request->get('id');
        list($integration, $userId) = explode('__', $id);

        if ($this->getUser()->id != $userId) {
            throw new Forbidden();
        }

        $entity = $this->getEntityManager()->getEntity('Integration', $integration);
        if ($entity) {
            return array(
                'clientId' => $entity->get('clientId'),
                'redirectUri' => $this->getConfig()->get('siteUrl') . '?entryPoint=oauthCallback',
                'isConnected' => $this->getRecordService()->ping($integration, $userId)
            );
        }
    }

    public function actionRead($params, $data, $request)
    {
        list($integration, $userId) = explode('__', $params['id']);

        if ($this->getUser()->id != $userId) {
            throw new Forbidden();
        }

        $entity = $this->getEntityManager()->getEntity('ExternalAccount', $params['id']);
        return $entity->toArray();
    }

    public function actionUpdate($params, $data, $request)
    {
        return $this->actionPatch($params, $data, $request);
    }

    public function actionPatch($params, $data, $request)
    {
        if (!$request->isPut() && !$request->isPost() && !$request->isPatch()) {
            throw new BadRequest();
        }

        list($integration, $userId) = explode('__', $params['id']);

        if ($this->getUser()->id != $userId) {
            throw new Forbidden();
        }

        if (isset($data->enabled) && !$data->enabled) {
            $data->data = null;
        }

        $entity = $this->getEntityManager()->getEntity('ExternalAccount', $params['id']);
        $entity->set($data);
        $this->getEntityManager()->saveEntity($entity);

        return $entity->toArray();
    }

    public function actionAuthorizationCode($params, $data, $request)
    {
        if (!$request->isPost()) {
            throw new Error('Bad HTTP method type.');
        }

        $id = $data->id;
        $code = $data->code;

        list($integration, $userId) = explode('__', $id);

        if ($this->getUser()->id != $userId) {
            throw new Forbidden();
        }

        $service = $this->getRecordService();
        return $service->authorizationCode($integration, $userId, $code);
    }
}
