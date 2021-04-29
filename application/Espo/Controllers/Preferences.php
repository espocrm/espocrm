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

use Espo\Core\Exceptions\Error;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\NotFound;

use Espo\Core\{
    Controllers\Base,
    Api\Request,
    Di,
};

use StdClass;

class Preferences extends Base implements

    Di\EntityManagerAware,
    Di\CryptAware
{
    use Di\EntityManagerSetter;
    use Di\CryptSetter;

    protected $entityManager;

    protected $crypt;

    protected function handleUserAccess(string $userId): void
    {
        if (!$this->user->isAdmin()) {
            if ($this->user->getId() != $userId) {
                throw new Forbidden();
            }
        }
    }

    public function deleteActionDelete(Request $request): StdClass
    {
        $params = $request->getRouteParams();

        $userId = $params['id'];

        if (empty($userId)) {
            throw new BadRequest();
        }

        $this->handleUserAccess($userId);

        $result = $this->entityManager
            ->getRepository('Preferences')
            ->resetToDefaults($userId);

        if (!$result) {
            throw new NotFound();
        }

        return $result;
    }

    public function putActionUpdate(Request $request): StdClass
    {
        $params = $request->getRouteParams();

        $data = $request->getParsedBody();

        $userId = $params['id'];

        $this->handleUserAccess($userId);

        if ($this->acl->getLevel('Preferences', 'edit') === 'no') {
            throw new Forbidden();
        }

        foreach ($this->acl->getScopeForbiddenAttributeList('Preferences', 'edit') as $attribute) {
            unset($data->$attribute);
        }

        if (property_exists($data, 'smtpPassword')) {
            $data->smtpPassword = $this->crypt->encrypt($data->smtpPassword);
        }

        $user = $this->entityManager->getEntity('User', $userId);

        $entity = $this->entityManager->getEntity('Preferences', $userId);

        if ($entity && $user) {
            $entity->set($data);

            $this->entityManager->saveEntity($entity);

            $entity->set('smtpEmailAddress', $user->get('emailAddress'));
            $entity->set('name', $user->get('name'));

            $entity->clear('smtpPassword');

            return $entity->getValueMap();
        }

        throw new Error();
    }

    public function getActionRead(Request $request): StdClass
    {
        $params = $request->getRouteParams();

        $userId = $params['id'];

        $this->handleUserAccess($userId);

        $entity = $this->entityManager->getEntity('Preferences', $userId);
        $user = $this->entityManager->getEntity('User', $userId);

        if (!$entity || !$user) {
            throw new NotFound();
        }

        $entity->set('smtpEmailAddress', $user->get('emailAddress'));
        $entity->set('name', $user->get('name'));
        $entity->set('isPortalUser', $user->isPortal());

        $entity->clear('smtpPassword');

        foreach ($this->acl->getScopeForbiddenAttributeList('Preferences', 'read') as $attribute) {
            $entity->clear($attribute);
        }

        return $entity->getValueMap();
    }

    public function postActionResetDashboard(Request $request): StdClass
    {
        $data = $request->getParsedBody();

        if (empty($data->id)) {
            throw new BadRequest();
        }

        $userId = $data->id;

        $this->handleUserAccess($userId);

        $user = $this->entityManager->getEntity('User', $userId);
        $preferences = $this->entityManager->getEntity('Preferences', $userId);

        if (!$user) {
            throw new NotFound();
        }

        if (!$preferences) {
            throw new NotFound();
        }

        if ($user->isPortal()) {
            throw new Forbidden();
        }

        if ($this->acl->getLevel('Preferences', 'edit') === 'no') {
            throw new Forbidden();
        }

        $forbiddenAttributeList = $this->acl->getScopeForbiddenAttributeList('Preferences', 'edit');

        if (in_array('dashboardLayout', $forbiddenAttributeList)) {
            throw new Forbidden();
        }

        $dashboardLayout = $this->config->get('dashboardLayout');
        $dashletsOptions = $this->config->get('dashletsOptions');

        $preferences->set([
            'dashboardLayout' => $dashboardLayout,
            'dashletsOptions' => $dashletsOptions,
        ]);

        $this->entityManager->saveEntity($preferences);

        return (object) [
            'dashboardLayout' => $preferences->get('dashboardLayout'),
            'dashletsOptions' => $preferences->get('dashletsOptions'),
        ];
    }
}
