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
use \Espo\Core\Exceptions\NotFound;

class Preferences extends \Espo\Core\Controllers\Base
{
    protected function getPreferences()
    {
        return $this->getContainer()->get('preferences');
    }

    protected function getEntityManager()
    {
        return $this->getContainer()->get('entityManager');
    }

    protected function getCrypt()
    {
        return $this->getContainer()->get('crypt');
    }

    protected function handleUserAccess($userId)
    {
        if (!$this->getUser()->isAdmin()) {
            if ($this->getUser()->id != $userId) {
                throw new Forbidden();
            }
        }
    }

    public function actionDelete($params, $data, $request)
    {
        $userId = $params['id'];
        if (empty($userId)) {
            throw new BadRequest();
        }
        if (!$request->isDelete()) {
            throw new BadRequest();
        }
        $this->handleUserAccess($userId);

        return $this->getEntityManager()->getRepository('Preferences')->resetToDefaults($userId);
    }

    public function actionPatch($params, $data, $request)
    {
        return $this->actionUpdate($params, $data, $request);
    }

    public function actionUpdate($params, $data, $request)
    {
        $userId = $params['id'];
        $this->handleUserAccess($userId);

        if (!$request->isPost() && !$request->isPatch() && !$request->isPut()) {
            throw new BadRequest();
        }

        if ($this->getAcl()->getLevel('Preferences', 'edit') === 'no') {
            throw new Forbidden();
        }

        foreach ($this->getAcl()->getScopeForbiddenAttributeList('Preferences', 'edit') as $attribute) {
            unset($data->$attribute);
        }

        if (property_exists($data, 'smtpPassword')) {
            $data->smtpPassword = $this->getCrypt()->encrypt($data->smtpPassword);
        }

        $user = $this->getEntityManager()->getEntity('User', $userId);

        $entity = $this->getEntityManager()->getEntity('Preferences', $userId);

        if ($entity && $user) {
            $entity->set($data);
            $this->getEntityManager()->saveEntity($entity);

            $entity->set('smtpEmailAddress', $user->get('emailAddress'));
            $entity->set('name', $user->get('name'));

            $entity->clear('smtpPassword');

            return $entity->getValueMap();
        }
        throw new Error();
    }

    public function actionRead($params)
    {
        $userId = $params['id'];
        $this->handleUserAccess($userId);

        $entity = $this->getEntityManager()->getEntity('Preferences', $userId);
        $user = $this->getEntityManager()->getEntity('User', $userId);

        if (!$entity || !$user) {
            throw new NotFound();
        }

        $entity->set('smtpEmailAddress', $user->get('emailAddress'));
        $entity->set('name', $user->get('name'));
        $entity->set('isPortalUser', $user->isPortal());

        $entity->clear('smtpPassword');

        foreach ($this->getAcl()->getScopeForbiddenAttributeList('Preferences', 'read') as $attribute) {
            $entity->clear($attribute);
        }

        return $entity->getValueMap();
    }

    public function postActionResetDashboard($params, $data)
    {
        if (empty($data->id)) throw new BadRequest();

        $userId = $data->id;

        $this->handleUserAccess($userId);

        $user = $this->getEntityManager()->getEntity('User', $userId);
        $preferences = $this->getEntityManager()->getEntity('Preferences', $userId);
        if (!$user)  throw new NotFound();
        if (!$preferences)  throw new NotFound();

        if ($user->isPortal()) throw new Forbidden();

        if ($this->getAcl()->getLevel('Preferences', 'edit') === 'no') {
            throw new Forbidden();
        }

        $forbiddenAttributeList = $this->getAcl()->getScopeForbiddenAttributeList('Preferences', 'edit');

        if (in_array('dashboardLayout', $forbiddenAttributeList)) {
            throw new Forbidden();
        }

        $dashboardLayout = $this->getConfig()->get('dashboardLayout');
        $dashletsOptions = $this->getConfig()->get('dashletsOptions');

        $preferences->set([
            'dashboardLayout' => $dashboardLayout,
            'dashletsOptions' => $dashletsOptions
        ]);

        $this->getEntityManager()->saveEntity($preferences);

        return (object) [
            'dashboardLayout' => $preferences->get('dashboardLayout'),
            'dashletsOptions' => $preferences->get('dashletsOptions')
        ];
    }
}
