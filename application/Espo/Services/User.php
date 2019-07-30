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

    protected $mandatorySelectAttributeList = [
        'isPortalUser',
        'isActive',
        'userName',
        'isAdmin',
        'type'
    ];

    protected $linkSelectParams = [
        'targetLists' => [
            'additionalColumns' => [
                'optedOut' => 'isOptedOut'
            ]
        ]
    ];

    protected $validateSkipFieldList = ['name', "firstName", "lastName"];

    protected $allowedUserTypeList = ['regular', 'admin', 'portal', 'api'];

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

    protected function getNumber()
    {
        return $this->getContainer()->get('number');
    }

    protected function getDateTime()
    {
        return $this->getContainer()->get('dateTime');
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
        if ($entity && $entity->isSuperAdmin() && !$this->getUser()->isSuperAdmin()) {
            throw new Forbidden();
        }
        if ($entity && $entity->isSystem()) {
            throw new Forbidden();
        }
        return $entity;
    }

    public function changePassword($userId, $password, $checkCurrentPassword = false, $currentPassword = null)
    {
        $user = $this->getEntityManager()->getEntity('User', $userId);
        if (!$user) {
            throw new NotFound();
        }

        if ($user->isSuperAdmin() && !$this->getUser()->isSuperAdmin()) {
            throw new Forbidden();
        }

        if (!$user->isAdmin() && $this->getConfig()->get('authenticationMethod', 'Espo') !== 'Espo') {
            throw new Forbidden();
        }

        if (empty($password)) {
            throw new Error('Password can\'t be empty.');
        }

        if ($checkCurrentPassword) {
            $passwordHash = new \Espo\Core\Utils\PasswordHash($this->getConfig());
            $u = $this->getEntityManager()->getRepository('User')->where([
                'id' => $user->id,
                'password' => $passwordHash->hash($currentPassword)
            ])->findOne();
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
        $user = $this->getEntityManager()->getRepository('User')->where([
            'userName' => $userName,
            'emailAddress' => $emailAddress
        ])->findOne();

        if (empty($user)) {
            throw new NotFound();
        }

        if (!$user->isActive()) {
            throw new NotFound();
        }

        $userId = $user->id;

        $passwordChangeRequest = $this->getEntityManager()->getRepository('PasswordChangeRequest')->where([
            'userId' => $userId
        ])->findOne();
        if ($passwordChangeRequest) {
            throw new Forbidden(json_encode(['reason' => 'Already-Sent']));
        }

        $requestId = Util::generateId();

        $passwordChangeRequest = $this->getEntityManager()->getEntity('PasswordChangeRequest');
        $passwordChangeRequest->set([
            'userId' => $userId,
            'requestId' => $requestId,
            'url' => $url
        ]);

        if (!$user->isAdmin() && $this->getConfig()->get('authenticationMethod', 'Espo') !== 'Espo') {
            throw new Forbidden();
        }

        $this->sendChangePasswordLink($requestId, $emailAddress, $user);

        $this->getEntityManager()->saveEntity($passwordChangeRequest);

        if (!$passwordChangeRequest->id) {
            throw new Error();
        }

        $dt = new \DateTime();
        $dt->add(new \DateInterval('PT'. self::PASSWORD_CHANGE_REQUEST_LIFETIME . 'M'));

        $job = $this->getEntityManager()->getEntity('Job');

        $job->set([
            'serviceName' => 'User',
            'methodName' => 'removeChangePasswordRequestJob',
            'data' => [
                'id' => $passwordChangeRequest->id
            ],
            'executeTime' => $dt->format('Y-m-d H:i:s'),
            'queue' => 'q1'
        ]);

        $this->getEntityManager()->saveEntity($job);

        return true;
    }

    public function removeChangePasswordRequestJob($data)
    {
        if (empty($data->id)) {
            return;
        }
        $id = $data->id;

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

    protected function filterInput($data)
    {
        parent::filterInput($data);

        if (!$this->getUser()->isSuperAdmin()) {
            unset($data->isSuperAdmin);
        }

        if (!$this->getUser()->isAdmin()) {
            if (!$this->getAcl()->checkScope('Team')) {
                unset($data->defaultTeamId);
            }
        }
    }

    public function create($data)
    {
        $newPassword = null;
        if (property_exists($data, 'password')) {
            $newPassword = $data->password;
            $data->password = $this->hashPassword($data->password);
        }

        $user = parent::create($data);

        if (!is_null($newPassword) && !empty($data->sendAccessInfo)) {
            if ($user->isActive()) {
                try {
                    $this->sendPassword($user, $newPassword);
                } catch (\Exception $e) {}
            }
        }

        return $user;
    }

    public function update($id, $data)
    {
        if ($id == 'system') {
            throw new Forbidden();
        }
        $newPassword = null;
        if (property_exists($data, 'password')) {
            $newPassword = $data->password;
            $data->password = $this->hashPassword($data->password);
        }

        if ($id == $this->getUser()->id) {
            unset($data->isActive);
            unset($data->isPortalUser);
            unset($data->type);
        }

        $user = parent::update($id, $data);

        if (!is_null($newPassword)) {
            try {
                if ($user->isActive() && !empty($data->sendAccessInfo)) {
                    $this->sendPassword($user, $newPassword);
                }
            } catch (\Exception $e) {}
        }

        return $user;
    }

    public function prepareEntityForOutput(Entity $entity)
    {
        parent::prepareEntityForOutput($entity);

        if ($entity->isApi()) {
            if ($this->getUser()->isAdmin()) {
                if ($entity->get('authMethod') === 'Hmac') {
                    $secretKey = $this->getSecretKeyForUserId($entity->id);
                    $entity->set('secretKey', $secretKey);
                }
            } else {
                $entity->clear('apiKey');
                $entity->clear('secretKey');
            }
        }
    }

    protected function getSecretKeyForUserId($id)
    {
        $apiKeyUtil = new \Espo\Core\Utils\ApiKey($this->getConfig());
        return $apiKeyUtil->getSecretKeyForUserId($id);
    }

    public function generateNewApiKeyForEntity($id)
    {
        $entity = $this->getEntity($id);
        if (!$entity) throw new NotFound();

        if (!$this->getUser()->isAdmin()) throw new Forbidden();
        if (!$entity->isApi()) throw new Forbidden();

        $apiKey = \Espo\Core\Utils\Util::generateApiKey();
        $entity->set('apiKey', $apiKey);

        if ($entity->get('authMethod') === 'Hmac') {
            $secretKey = \Espo\Core\Utils\Util::generateKey();
            $entity->set('secretKey', $secretKey);
        }

        $this->getEntityManager()->saveEntity($entity);

        $this->prepareEntityForOutput($entity);

        return $entity;
    }

    protected function getInternalUserCount()
    {
        return $this->getEntityManager()->getRepository('User')->where([
            'isActive' => true,
            'type' => ['admin', 'regular'],
            'type!=' => 'system'
        ])->count();
    }

    protected function getPortalUserCount()
    {
        return $this->getEntityManager()->getRepository('User')->where([
            'isActive' => true,
            'type' => 'portal'
        ])->count();
    }

    protected function beforeCreateEntity(Entity $entity, $data)
    {
        if (
            $this->getConfig()->get('userLimit') && !$this->getUser()->isSuperAdmin() &&
            !$entity->isPortal() && !$entity->isApi()
        ) {
            $userCount = $this->getInternalUserCount();
            if ($userCount >= $this->getConfig()->get('userLimit')) {
                throw new Forbidden('User limit '.$this->getConfig()->get('userLimit').' is reached.');
            }
        }
        if ($this->getConfig()->get('portalUserLimit') && !$this->getUser()->isSuperAdmin() && $entity->isPortal()) {
            $portalUserCount = $this->getPortalUserCount();
            if ($portalUserCount >= $this->getConfig()->get('portalUserLimit')) {
                throw new Forbidden('Portal user limit '.$this->getConfig()->get('portalUserLimit').' is reached.');
            }
        }

        if ($entity->isApi()) {
            $apiKey = \Espo\Core\Utils\Util::generateApiKey();
            $entity->set('apiKey', $apiKey);

            if ($entity->get('authMethod') === 'Hmac') {
                $secretKey = \Espo\Core\Utils\Util::generateKey();
                $entity->set('secretKey', $secretKey);
            }
        }

        if (!$entity->isSuperAdmin()) {
            if (
                $entity->get('type') &&
                !in_array($entity->get('type'), $this->allowedUserTypeList)
            ) {
                throw new Forbidden();
            }
        }
    }

    protected function beforeUpdateEntity(Entity $entity, $data)
    {
        if ($this->getConfig()->get('userLimit') && !$this->getUser()->isSuperAdmin()) {
            if (
                (
                    $entity->get('isActive') && $entity->isAttributeChanged('isActive') &&
                    !$entity->isPortal() && !$entity->isApi()
                )
                ||
                (
                    !$entity->isPortal() && !$entity->isApi() && $entity->isAttributeChanged('type') &&
                    ($entity->isRegular() || $entity->isAdmin()) &&
                    ($entity->getFetched('type') == 'portal' || $entity->getFetched('type') == 'api')
                )
            ) {
                $userCount = $this->getInternalUserCount();
                if ($userCount >= $this->getConfig()->get('userLimit')) {
                    throw new Forbidden('User limit '.$this->getConfig()->get('userLimit').' is reached.');
                }
            }
        }
        if ($this->getConfig()->get('portalUserLimit') && !$this->getUser()->isSuperAdmin()) {
            if (
                ($entity->get('isActive') && $entity->isAttributeChanged('isActive') && $entity->isPortal())
                ||
                ($entity->isPortal() && $entity->isAttributeChanged('type'))
            ) {
                $portalUserCount = $this->getPortalUserCount();
                if ($portalUserCount >= $this->getConfig()->get('portalUserLimit')) {
                    throw new Forbidden('Portal user limit '.$this->getConfig()->get('portalUserLimit').' is reached.');
                }
            }
        }

        if ($entity->isApi()) {
            if ($entity->isAttributeChanged('authMethod') && $entity->get('authMethod') === 'Hmac') {
                $secretKey = \Espo\Core\Utils\Util::generateKey();
                $entity->set('secretKey', $secretKey);
            }
        }

        if (!$entity->isSuperAdmin()) {
            if (
                $entity->isAttributeChanged('type') &&
                $entity->get('type') &&
                !in_array($entity->get('type'), $this->allowedUserTypeList)
            ) {
                throw new Forbidden();
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

        if (!$this->getConfig()->get('smtpServer') && !$this->getConfig()->get('internalSmtpServer')) {
            return;
        }

        $templateFileManager = $this->getContainer()->get('templateFileManager');

        $siteUrl = $this->getConfig()->getSiteUrl() . '/';

        $data = [];

        if ($user->isPortal()) {
            $subjectTpl = $templateFileManager->getTemplate('accessInfoPortal', 'subject', 'User');
            $bodyTpl = $templateFileManager->getTemplate('accessInfoPortal', 'body', 'User');

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
            $data['siteUrlList'] = $urlList;
        } else {
            $subjectTpl = $templateFileManager->getTemplate('accessInfo', 'subject', 'User');
            $bodyTpl = $templateFileManager->getTemplate('accessInfo', 'body', 'User');

            $data['siteUrl'] = $siteUrl;
        }

        $data['password'] = $password;

        $htmlizer = new \Espo\Core\Htmlizer\Htmlizer($this->getFileManager(), $this->getDateTime(), $this->getNumber(), null);

        $subject = $htmlizer->render($user, $subjectTpl, null, $data, true);
        $body = $htmlizer->render($user, $bodyTpl, null, $data, true);

        $email->set([
            'subject' => $subject,
            'body' => $body,
            'to' => $emailAddress
        ]);

        if ($this->getConfig()->get('smtpServer')) {
            $this->getMailSender()->useGlobal();
        } else {
            $this->getMailSender()->useSmtp(array(
                'server' => $this->getConfig()->get('internalSmtpServer'),
                'port' => $this->getConfig()->get('internalSmtpPort'),
                'auth' => $this->getConfig()->get('internalSmtpAuth'),
                'username' => $this->getConfig()->get('internalSmtpUsername'),
                'password' => $this->getConfig()->get('internalSmtpPassword'),
                'security' => $this->getConfig()->get('internalSmtpSecurity'),
                'fromAddress' => $this->getConfig()->get('internalOutboundEmailFromAddress', $this->getConfig()->get('outboundEmailFromAddress'))
            ));
        }
        $this->getMailSender()->send($email);
    }

    protected function sendChangePasswordLink($requestId, $emailAddress, Entity $user)
    {
        if (empty($emailAddress)) {
            return;
        }

        $email = $this->getEntityManager()->getEntity('Email');

        if (!$this->getConfig()->get('smtpServer') && !$this->getConfig()->get('internalSmtpServer')) {
            throw new Error("SMTP credentials are not defined.");
        }

        $templateFileManager = $this->getContainer()->get('templateFileManager');

        $subjectTpl = $templateFileManager->getTemplate('passwordChangeLink', 'subject', 'User');
        $bodyTpl = $templateFileManager->getTemplate('passwordChangeLink', 'body', 'User');

        $data = [];
        $link = $this->getConfig()->getSiteUrl() . '?entryPoint=changePassword&id=' . $requestId;
        $data['link'] = $link;

        $htmlizer = new \Espo\Core\Htmlizer\Htmlizer($this->getFileManager(), $this->getDateTime(), $this->getNumber(), null);

        $subject = $htmlizer->render($user, $subjectTpl, null, $data, true);
        $body = $htmlizer->render($user, $bodyTpl, null, $data, true);

        $email->set([
            'subject' => $subject,
            'body' => $body,
            'to' => $emailAddress,
            'isSystem' => true
        ]);

        if ($this->getConfig()->get('smtpServer')) {
            $this->getMailSender()->useGlobal();
        } else {
            $this->getMailSender()->useSmtp([
                'server' => $this->getConfig()->get('internalSmtpServer'),
                'port' => $this->getConfig()->get('internalSmtpPort'),
                'auth' => $this->getConfig()->get('internalSmtpAuth'),
                'username' => $this->getConfig()->get('internalSmtpUsername'),
                'password' => $this->getConfig()->get('internalSmtpPassword'),
                'security' => $this->getConfig()->get('internalSmtpSecurity'),
                'fromAddress' => $this->getConfig()->get('internalOutboundEmailFromAddress', $this->getConfig()->get('outboundEmailFromAddress'))
            ]);
        }

        $this->getMailSender()->send($email);
    }

    public function delete($id)
    {
        if ($id == 'system') {
            throw new Forbidden();
        }
        if ($id == $this->getUser()->id) {
            throw new Forbidden();
        }
        return parent::delete($id);
    }

    protected function checkEntityForMassRemove(Entity $entity)
    {
        if ($entity->id == 'system') {
            return false;
        }
        if ($entity->id == $this->getUser()->id) {
            return false;
        }
        return true;
    }

    protected function checkEntityForMassUpdate(Entity $entity, $data)
    {
        if ($entity->id == 'system') {
            return false;
        }
        if ($entity->id == $this->getUser()->id) {
            if (property_exists($data, 'isActive')) {
                return false;
            }
            if (property_exists($data, 'type')) {
                return false;
            }
        }
        return true;
    }

    public function afterUpdateEntity(Entity $entity, $data)
    {
        parent::afterUpdateEntity($entity, $data);

        if (property_exists($data, 'rolesIds') || property_exists($data, 'teamsIds') || property_exists($data, 'type')) {
            $this->clearRoleCache($entity->id);
        }

        if ($entity->isPortal() && $entity->get('contactId')) {
            if (property_exists($data, 'firstName') || property_exists($data, 'lastName') || property_exists($data, 'salutationName')) {
                $contact = $this->getEntityManager()->getEntity('Contact', $entity->get('contactId'));
                if (property_exists($data, 'firstName')) {
                    $contact->set('firstName', $data->firstName);
                }
                if (array_key_exists('lastName', $data)) {
                    $contact->set('lastName', $data->lastName);
                }
                if (property_exists($data, 'salutationName')) {
                    $contact->set('salutationName', $data->salutationName);
                }
                $this->getEntityManager()->saveEntity($contact);
            }
        }
    }

    protected function clearRoleCache($id)
    {
        $this->getFileManager()->removeFile('data/cache/application/acl/' . $id . '.php');
    }

    public function massUpdate(array $params, $data)
    {
        unset($data->type);
        unset($data->isAdmin);
        unset($data->isSuperAdmin);
        unset($data->isPortalUser);
        unset($data->emailAddress);
        unset($data->password);
        return parent::massUpdate($params, $data);
    }

    protected function afterMassUpdate(array $idList, $data)
    {
        parent::afterMassUpdate($idList, $data);

        if (array_key_exists('rolesIds', $data) || array_key_exists('teamsIds', $data) || array_key_exists('type', $data)) {
            foreach ($idList as $id) {
                $this->clearRoleCache($id);
            }
        }
    }

    public function loadAdditionalFields(Entity $entity)
    {
        parent::loadAdditionalFields($entity);
        $this->loadLastAccessField($entity);
    }

    public function loadLastAccessField(Entity $entity)
    {
        $forbiddenFieldList = $this->getAcl()->getScopeForbiddenFieldList($this->entityType, 'edit');
        if (in_array('lastAccess', $forbiddenFieldList)) return;

        $authToken = $this->getEntityManager()->getRepository('AuthToken')->select(['id', 'lastAccess'])->where([
            'userId' => $entity->id
        ])->order('lastAccess', true)->findOne();

        $lastAccess = null;

        if ($authToken) {
            $lastAccess = $authToken->get('lastAccess');
        }

        $dt = null;

        if ($lastAccess) {
            try {
                $dt = new \DateTime($lastAccess);
            } catch (\Exception $e) {}
        }

        $where = [
            'userId' => $entity->id,
            'isDenied' => false
        ];

        if ($dt) {
            $where['requestTime>'] = $dt->format('U');
        }

        $authLogRecord = $this->getEntityManager()->getRepository('AuthLogRecord')
            ->select(['id', 'createdAt'])->where($where)->order('requestTime', true)->findOne();

        if ($authLogRecord) {
            $lastAccess = $authLogRecord->get('createdAt');
        }

        $entity->set('lastAccess', $lastAccess);
    }
}
