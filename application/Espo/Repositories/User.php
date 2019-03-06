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

namespace Espo\Repositories;

use \Espo\ORM\Entity;

use \Espo\Core\Exceptions\Error;
use \Espo\Core\Exceptions\Conflict;

class User extends \Espo\Core\ORM\Repositories\RDB
{
    protected function beforeSave(Entity $entity, array $options = [])
    {
        if ($entity->isNew() && !$entity->has('type')) {
            if ($entity->get('isPortalUser') && $entity->isAttributeChanged('isPortalUser')) {
                $entity->set('type', 'portal');
            }
        }

        if ($entity->has('type') && !$entity->get('type')) {
            $entity->set('type', 'regular');
        }

        $entity->clear('isAdmin');
        $entity->clear('isPortalUser');
        $entity->clear('isSuperAdmin');

        if ($entity->isAttributeChanged('type')) {
            $type = $entity->get('type');

            if (in_array($type, ['regular', 'admin', 'portal'])) {
                $entity->set('isAdmin', false);
                $entity->set('isPortalUser', false);
                $entity->set('isSuperAdmin', false);

                if ($type === 'portal') {
                    $entity->set('isPortalUser', true);
                } else if ($type === 'admin') {
                    $entity->set('isAdmin', true);
                } else if ($type === 'super-admin') {
                    $entity->set('isSuperAdmin', true);
                }
            }
        }

        if ($entity->isApi()) {
            if ($entity->isAttributeChanged('userName')) {
                $entity->set('lastName', $entity->get('userName'));
            }
            if ($entity->has('authMethod') && $entity->get('authMethod') !== 'Hmac') {
                $entity->clear('secretKey');
            }
        } else {
            if ($entity->isAttributeChanged('type')) {
                $entity->set('authMethod', null);
            }
        }

        parent::beforeSave($entity, $options);

        if ($entity->isNew()) {
            $userName = $entity->get('userName');
            if (empty($userName)) {
                throw new Error();
            }

            $user = $this->where([
                'userName' => $userName
            ])->findOne();

            if ($user) {
                throw new Conflict(json_encode(['reason' => 'userNameExists']));
            }
        } else {
            if ($entity->isAttributeChanged('userName')) {
                $userName = $entity->get('userName');
                if (empty($userName)) {
                    throw new Error();
                }

                $user = $this->where(array(
                    'userName' => $userName,
                    'id!=' => $entity->id
                ))->findOne();
                if ($user) {
                    throw new Conflict(json_encode(['reason' => 'userNameExists']));
                }
            }
        }

        if ($entity->has('type') && !$entity->isPortal()) {
            $entity->set('portalRolesIds', []);
            $entity->set('portalRolesNames', (object)[]);
            $entity->set('portalsIds', []);
            $entity->set('portalsNames', (object)[]);
        }

        if ($entity->has('type') && $entity->isPortal()) {
            $entity->set('rolesIds', []);
            $entity->set('rolesNames', (object)[]);
            $entity->set('teamsIds', []);
            $entity->set('teamsNames', (object)[]);
            $entity->set('defaultTeamId', null);
            $entity->set('defaultTeamName', null);
        }
    }

    protected function afterSave(Entity $entity, array $options = [])
    {
        parent::afterSave($entity, $options);

        if ($entity->isApi()) {
            if (
                $entity->get('apiKey') && $entity->get('secretKey') &&
                (
                    $entity->isAttributeChanged('apiKey') || $entity->isAttributeChanged('authMethod')
                )
            ) {
                $apiKeyUtil = new \Espo\Core\Utils\ApiKey($this->getConfig());
                $apiKeyUtil->storeSecretKeyForUserId($entity->id, $entity->get('secretKey'));
            }

            if ($entity->isAttributeChanged('authMethod') && $entity->get('authMethod') !== 'Hmac') {
                $apiKeyUtil = new \Espo\Core\Utils\ApiKey($this->getConfig());
                $apiKeyUtil->removeSecretKeyForUserId($entity->id);
            }
        }
    }

    protected function afterRemove(Entity $entity, array $options = [])
    {
        parent::afterRemove($entity, $options);

        if ($entity->isApi() && $entity->get('authMethod') === 'Hmac') {
            $apiKeyUtil = new \Espo\Core\Utils\ApiKey($this->getConfig());
            $apiKeyUtil->removeSecretKeyForUserId($entity->id);
        }

        $userData = $this->getEntityManager()->getRepository('UserData')->getByUserId($entity->id);
        if ($userData) {
            $this->getEntityManager()->removeEntity($userData);
        }
    }

    public function checkBelongsToAnyOfTeams($userId, array $teamIds)
    {
        if (empty($teamIds)) {
            return false;
        }

        $pdo = $this->getEntityManager()->getPDO();

        $arr = [];
        foreach ($teamIds as $teamId) {
            $arr[] = $pdo->quote($teamId);
        }

        $sql = "SELECT * FROM team_user WHERE deleted = 0 AND user_id = :userId AND team_id IN (".implode(", ", $arr).")";

        $sth = $pdo->prepare($sql);
        $sth->execute(array(
            ':userId' => $userId
        ));
        if ($row = $sth->fetch()) {
            return true;
        }
        return false;
    }

    public function handleSelectParams(&$params)
    {
        parent::handleSelectParams($params);
        if (array_key_exists('select', $params)) {
            if (in_array('name', $params['select'])) {
                $additionalAttributeList = ['userName'];
                foreach ($additionalAttributeList as $attribute) {
                    if (!in_array($attribute, $params['select'])) {
                        $params['select'][] = $attribute;
                    }
                }
            }
        }
    }
}
