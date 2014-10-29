<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014  Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

use Espo\Core\Controllers\Base;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Error;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\NotFound;
use Espo\Core\ORM\EntityManager;
use Espo\Core\Utils\Crypt;
use Espo\Entities\User;

class Preferences extends
    Base
{

    public function actionDelete($params, $data)
    {
        /**
         * @var \Espo\Repositories\Preferences $preferencesRepo
         */
        $userId = $params['id'];
        if (empty($userId)) {
            throw new BadRequest();
        }
        $this->handleUserAccess($userId);
        $preferencesRepo = $this->getEntityManager()->getRepository('Preferences');
        return $preferencesRepo->resetToDefaults($userId);
    }

    protected function handleUserAccess($userId)
    {
        if (!$this->getUser()->isAdmin()) {
            if ($this->getUser()->id != $userId) {
                throw new Forbidden();
            }
        }
    }

    /**
     * @return EntityManager
     * @since 1.0
     */
    protected function getEntityManager()
    {
        return $this->getContainer()->get('entityManager');
    }

    public function actionPatch($params, $data)
    {
        return $this->actionUpdate($params, $data);
    }

    public function actionUpdate($params, $data)
    {
        /**
         * @var \Espo\Entities\Preferences $entity
         * @var User                       $user
         */
        $userId = $params['id'];
        $this->handleUserAccess($userId);
        if (array_key_exists('smtpPassword', $data)) {
            $data['smtpPassword'] = $this->getCrypt()->encrypt($data['smtpPassword']);
        }
        $user = $this->getEntityManager()->getEntity('User', $userId);
        $entity = $this->getEntityManager()->getEntity('Preferences', $userId);
        if ($entity) {
            $entity->set($data);
            $this->getEntityManager()->saveEntity($entity);
            $entity->set('smtpEmailAddress', $user->get('emailAddress'));
            $entity->set('name', $user->get('name'));
            $entity->clear('smtpPassword');
            return $entity->toArray();
        }
        throw new Error();
    }

    /**
     * @return Crypt
     * @since 1.0
     */
    protected function getCrypt()
    {
        return $this->getContainer()->get('crypt');
    }

    public function actionRead($params)
    {
        /**
         * @var \Espo\Entities\Preferences $entity
         * @var User                       $user
         */
        $userId = $params['id'];
        $this->handleUserAccess($userId);
        $entity = $this->getEntityManager()->getEntity('Preferences', $userId);
        $user = $this->getEntityManager()->getEntity('User', $userId);
        $entity->set('smtpEmailAddress', $user->get('emailAddress'));
        $entity->set('name', $user->get('name'));
        $entity->clear('smtpPassword');
        if ($entity) {
            return $entity->toArray();
        }
        throw new NotFound();
    }

    /**
     * @return \Espo\Entities\Preferences
     * @since 1.0
     */
    protected function getPreferences()
    {
        return $this->getContainer()->get('preferences');
    }
}

