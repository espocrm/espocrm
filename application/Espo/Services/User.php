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
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

namespace Espo\Services;

use \Espo\Core\Exceptions\Forbidden;
use \Espo\Core\Exceptions\Error;
use \Espo\Core\Exceptions\NotFound;
use Espo\Core\Utils\Util;

use \Espo\ORM\Entity;

class User extends Record
{
    const PASSWORD_CHANGE_REQUEST_LIFETIME = 360; // minutes

    protected function init()
    {
        parent::init();

        $this->addDependency('container');
    }

    protected $internalAttributeList = ['password'];

    protected function getMailSender()
    {
        return $this->getContainer()->get('mailSender');
    }

    protected function getLanguage()
    {
        return $this->getContainer()->get('language');
    }

    protected function getFileManager()
    {
        return $this->getContainer()->get('fileManager');
    }

    protected function getContainer()
    {
        return $this->injections['container'];
    }

    public function getEntity($id = null)
    {
        if (isset($id) && $id == 'system') {
            throw new Forbidden();
        }

        $entity = parent::getEntity($id);
        if ($entity && $entity->get('isSuperAdmin') && !$this->getUser()->get('isSuperAdmin')) {
            throw new Forbidden();
        }
        return $entity;
    }

    public function findEntities($params)
    {
        if (empty($params['where'])) {
            $params['where'] = array();
        }
        $params['where'][] = array(
            'type' => 'notEquals',
            'field' => 'id',
            'value' => 'system'
        );

        $result = parent::findEntities($params);
        return $result;
    }

    public function changePassword($userId, $password, $checkCurrentPassword = false, $currentPassword)
    {
        $user = $this->getEntityManager()->getEntity('User', $userId);
        if (!$user) {
            throw new NotFound();
        }

        if ($user->get('isSuperAdmin') && !$this->getUser()->get('isSuperAdmin')) {
            throw new Forbidden();
        }

        if (empty($password)) {
            throw new Error('Password can\'t be empty.');
        }

        if ($checkCurrentPassword) {
            $passwordHash = new \Espo\Core\Utils\PasswordHash($this->getConfig());
            $u = $this->getEntityManager()->getRepository('User')->where(array(
                'id' => $user->id,
                'password' => $passwordHash->hash($currentPassword)
            ))->findOne();
            if (!$u) {
                throw new Forbidden();
            }
        }

        $user->set('password', $this->hashPassword($password));

        $this->getEntityManager()->saveEntity($user);

        return true;
    }

    public function passwordChangeRequest($userName, $emailAddress, $url = null)
    {
        $user = $this->getEntityManager()->getRepository('User')->where(array(
            'userName' => $userName,
            'emailAddress' => $emailAddress
        ))->findOne();

        if (empty($user)) {
            throw new NotFound();
        }

        if (!$user->isActive()) {
            throw new NotFound();
        }

        $userId = $user->id;

        $passwordChangeRequest = $this->getEntityManager()->getRepository('PasswordChangeRequest')->where(array(
            'userId' => $userId
        ))->findOne();
        if ($passwordChangeRequest) {
            throw new Forbidden();
        }

        $requestId = Util::generateId();

        $passwordChangeRequest = $this->getEntityManager()->getEntity('PasswordChangeRequest');
        $passwordChangeRequest->set(array(
            'userId' => $userId,
            'requestId' => $requestId,
            'url' => $url
        ));

        $this->sendChangePasswordLink($requestId, $emailAddress);

        $this->getEntityManager()->saveEntity($passwordChangeRequest);

        if (!$passwordChangeRequest->id) {
            throw new Error();
        }

        $dt = new \DateTime();
        $dt->add(new \DateInterval('PT'. self::PASSWORD_CHANGE_REQUEST_LIFETIME . 'M'));

        $job = $this->getEntityManager()->getEntity('Job');

        $job->set(array(
            'serviceName' => 'User',
            'method' => 'removeChangePasswordRequestJob',
            'data' => json_encode(array(
                'id' => $passwordChangeRequest->id,
            )),
            'executeTime' => $dt->format('Y-m-d H:i:s') ,
        ));

        $this->getEntityManager()->saveEntity($job);

        return true;
    }

    public function removeChangePasswordRequestJob($data)
    {
        if (empty($data['id'])) {
            return;
        }
        $id = $data['id'];

        $p = $this->getEntityManager()->getEntity('PasswordChangeRequest', $id);
        if ($p) {
            $this->getEntityManager()->removeEntity($p);
        }
        return true;
    }

    protected function hashPassword($password)
    {
        $config = $this->getConfig();
        $passwordHash = new \Espo\Core\Utils\PasswordHash($config);

        return $passwordHash->hash($password);
    }

    public function createEntity($data)
    {
        $newPassword = null;
        if (array_key_exists('password', $data)) {
            $newPassword = $data['password'];
            $data['password'] = $this->hashPassword($data['password']);
        }
        if (!$this->getUser()->get('isSuperAdmin')) {
            unset($data['isSuperAdmin']);
        }

        $user = parent::createEntity($data);

        if (!is_null($newPassword) && !empty($data['sendAccessInfo'])) {
            if ($user->isActive()) {
                try {
                    $this->sendPassword($user, $newPassword);
                } catch (\Exception $e) {}
            }
        }

        return $user;
    }

    public function updateEntity($id, $data)
    {
        if ($id == 'system') {
            throw new Forbidden();
        }
        $newPassword = null;
        if (array_key_exists('password', $data)) {
            $newPassword = $data['password'];
            $data['password'] = $this->hashPassword($data['password']);
        }

        if ($id == $this->getUser()->id) {
            unset($data['isActive']);
            unset($data['isPortalUser']);
        }
        if (!$this->getUser()->get('isSuperAdmin')) {
            unset($data['isSuperAdmin']);
        }

        $user = parent::updateEntity($id, $data);

        if (!is_null($newPassword)) {
            try {
                if ($user->isActive() && !empty($data['sendAccessInfo'])) {
                    $this->sendPassword($user, $newPassword);
                }
            } catch (\Exception $e) {}
        }

        return $user;
    }

    protected function getInternalUserCount()
    {
        return $this->getEntityManager()->getRepository('User')->where(array(
            'isActive' => true,
            'isSuperAdmin' => false,
            'isPortalUser' => false,
            'id!=' => 'system'
        ))->count();
    }

    protected function getPortalUserCount()
    {
        return $this->getEntityManager()->getRepository('User')->where(array(
            'isActive' => true,
            'isSuperAdmin' => false,
            'isPortalUser' => true,
            'id!=' => 'system'
        ))->count();
    }

    protected function beforeCreate(Entity $entity, array $data = array())
    {
        if ($this->getConfig()->get('userLimit') && !$this->getUser()->get('isSuperAdmin')) {
            $userCount = $this->getInternalUserCount();
            if ($userCount >= $this->getConfig()->get('userLimit')) {
                throw new Forbidden('User limit '.$this->getConfig()->get('userLimit').' is reached.');
            }
        }
        if ($this->getConfig()->get('portalUserLimit') && !$this->getUser()->get('isSuperAdmin')) {
            $portalUserCount = $this->getPortalUserCount();
            if ($portalUserCount >= $this->getConfig()->get('portalUserLimit')) {
                throw new Forbidden('Portal user limit '.$this->getConfig()->get('portalUserLimit').' is reached.');
            }
        }
    }

    protected function beforeUpdate(Entity $user, array $data = array())
    {
        if ($this->getConfig()->get('userLimit') && !$this->getUser()->get('isSuperAdmin')) {
            if (
                ($user->get('isActive') && $user->isFieldChanged('isActive') && !$user->get('isPortalUser'))
                ||
                (!$user->get('isPortalUser') && $user->isFieldChanged('isPortalUser'))
            ) {
                $userCount = $this->getInternalUserCount();
                if ($userCount >= $this->getConfig()->get('userLimit')) {
                    throw new Forbidden('User limit '.$this->getConfig()->get('userLimit').' is reached.');
                }
            }
        }
        if ($this->getConfig()->get('portalUserLimit') && !$this->getUser()->get('isSuperAdmin')) {
            if (
                ($user->get('isActive') && $user->isFieldChanged('isActive') && $user->get('isPortalUser'))
                ||
                ($user->get('isPortalUser') && $user->isFieldChanged('isPortalUser'))
            ) {
                $portalUserCount = $this->getPortalUserCount();
                if ($portalUserCount >= $this->getConfig()->get('portalUserLimit')) {
                    throw new Forbidden('Portal user limit '.$this->getConfig()->get('portalUserLimit').' is reached.');
                }
            }
        }
    }

    protected function sendPassword(Entity $user, $password)
    {
        $emailAddress = $user->get('emailAddress');

        if (empty($emailAddress)) {
            return;
        }

        $email = $this->getEntityManager()->getEntity('Email');

        if (!$this->getConfig()->get('smtpServer')) {
            return;
        }

        $subject = $this->getLanguage()->translate('accountInfoEmailSubject', 'messages', 'User');
        $body = $this->getLanguage()->translate('accountInfoEmailBody', 'messages', 'User');

        $body = str_replace('{userName}', $user->get('userName'), $body);
        $body = str_replace('{password}', $password, $body);

        $siteUrl = $this->getConfig()->getSiteUrl() . '/';

        if ($user->get('isPortalUser')) {
            $urlList = [];
            $portalList = $this->getEntityManager()->getRepository('Portal')->distinct()->join('users')->where(array(
                'isActive' => true,
                'users.id' => $user->id
            ))->find();
            foreach ($portalList as $portal) {
                if ($portal->get('customUrl')) {
                    $urlList[] = $portal->get('customUrl');
                } else {
                    $url = $siteUrl . 'portal/';
                    if ($this->getConfig()->get('defaultPortalId') !== $portal->id) {
                        if ($portal->get('customId')) {
                            $url .= $portal->get('customId');
                        } else {
                            $url .= $portal->id;
                        }
                    }
                    $urlList[] = $url;
                }
            }
            if (!count($urlList)) {
                return;
            }
            $siteUrl = implode("\n", $urlList);
        }
        $body = str_replace('{siteUrl}', $siteUrl, $body);

        $email->set(array(
            'subject' => $subject,
            'body' => $body,
            'isHtml' => false,
            'to' => $emailAddress
        ));

        $this->getMailSender()->send($email);
    }

    protected function sendChangePasswordLink($requestId, $emailAddress, Entity $user = null)
    {
        if (empty($emailAddress)) {
            return;
        }

        $email = $this->getEntityManager()->getEntity('Email');

        if (!$this->getConfig()->get('smtpServer')) {
            throw new Error("SMTP settings is not setup.");
        }

        $subject = $this->getLanguage()->translate('passwordChangeLinkEmailSubject', 'messages', 'User');
        $body = $this->getLanguage()->translate('passwordChangeLinkEmailBody', 'messages', 'User');

        $link = $this->getConfig()->get('siteUrl') . '?entryPoint=changePassword&id=' . $requestId;

        $body = str_replace('{link}', $link, $body);

        $email->set(array(
            'subject' => $subject,
            'body' => $body,
            'isHtml' => false,
            'to' => $emailAddress
        ));

        $this->getMailSender()->send($email);
    }

    public function deleteEntity($id)
    {
        if ($id == 'system') {
            throw new Forbidden();
        }
        if ($id == $this->getUser()->id) {
            throw new Forbidden();
        }
        return parent::deleteEntity($id);
    }

    public function afterUpdate(Entity $entity, array $data = array())
    {
        parent::afterUpdate($entity, $data);
        if (array_key_exists('rolesIds', $data) || array_key_exists('teamsIds', $data) || array_key_exists('isAdmin', $data)) {
            $this->clearRoleCache($entity->id);
        }

        if ($entity->get('isPortalUser') && $entity->get('contactId')) {
            if (array_key_exists('firstName', $data) || array_key_exists('lastName', $data) || array_key_exists('salutationName', $data)) {
                $contact = $this->getEntityManager()->getEntity('Contact', $entity->get('contactId'));
                if (array_key_exists('firstName', $data)) {
                    $contact->set('firstName', $data['firstName']);
                }
                if (array_key_exists('lastName', $data)) {
                    $contact->set('lastName', $data['lastName']);
                }
                if (array_key_exists('salutationName', $data)) {
                    $contact->set('salutationName', $data['salutationName']);
                }
                $this->getEntityManager()->saveEntity($contact);
            }
        }
    }

    protected function clearRoleCache($id)
    {
        $this->getFileManager()->removeFile('data/cache/application/acl/' . $id . '.php');
    }

    protected function afterMassUpdate(array $idList, array $data = array())
    {
        parent::afterMassUpdate($idList, $data);

        if (array_key_exists('rolesIds', $data) || array_key_exists('teamsIds', $data) || array_key_exists('isAdmin', $data)) {
            foreach ($idList as $id) {
                $this->clearRoleCache($id);
            }
        }
    }
}

