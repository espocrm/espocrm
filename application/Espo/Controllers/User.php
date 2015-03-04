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
 ************************************************************************/

namespace Espo\Controllers;

use \Espo\Core\Exceptions\Error;
use \Espo\Core\Exceptions\NotFound;
use \Espo\Core\Exceptions\Forbidden;
use \Espo\Core\Exceptions\BadRequest;

class User extends \Espo\Core\Controllers\Record
{
    public function actionAcl($params, $data, $request)
    {
        $userId = $request->get('id');
        if (empty($userId)) {
            throw new Error();
        }

        if (!$this->getUser()->isAdmin() && $this->getUser()->id != $userId) {
            throw new Forbidden();
        }

        $user = $this->getEntityManager()->getEntity('User', $userId);
        if (empty($user)) {
            throw new NotFound();
        }

        $acl = new \Espo\Core\Acl($user, $this->getConfig(), $this->getContainer()->get('fileManager'), $this->getMetadata());

        return $acl->toArray();
    }

    public function actionChangeOwnPassword($params, $data, $request)
    {
        if (!$request->isPost()) {
            throw new BadRequest();
        }
        return $this->getService('User')->changePassword($this->getUser()->id, $data['password']);
    }

    public function actionChangePasswordByRequest($params, $data, $request)
    {
        if (!$request->isPost()) {
            throw new BadRequest();
        }
        if (empty($data['requestId']) || empty($data['password'])) {
            throw new BadRequest();
        }

        $p = $this->getEntityManager()->getRepository('PasswordChangeRequest')->where(array(
            'requestId' => $data['requestId']
        ))->findOne();

        if (!$p) {
            throw new Forbidden();
        }
        $userId = $p->get('userId');
        if (!$userId) {
            throw new Error();
        }

        $this->getEntityManager()->removeEntity($p);

        return $this->getService('User')->changePassword($userId, $data['password']);
    }

    public function actionPasswordChangeRequest($params, $data, $request)
    {
        if (!$request->isPost()) {
            throw new Forbidden();
        }
        
        if (empty($data['userName']) || empty($data['emailAddress'])) {
            throw new BadRequest();
        }

        $userName = $data['userName'];
        $emailAddress = $data['emailAddress'];

        return $this->getService('User')->passwordChangeRequest($userName, $emailAddress);
    }
}

