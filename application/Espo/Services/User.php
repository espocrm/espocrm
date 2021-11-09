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

namespace Espo\Services;

use Espo\Entities\User as UserEntity;
use Espo\Entities\Email as EmailEntity;

use Espo\Core\{
    Exceptions\Forbidden,
    Exceptions\Error,
    Exceptions\NotFound,
    Utils\Util,
    Utils\PasswordHash,
    Utils\ApiKey as ApiKeyUtil,
    Di,
    Password\Recovery,
    Record\CreateParams,
    Record\UpdateParams,
    Record\DeleteParams,
};

use Espo\ORM\Entity;

use stdClass;
use Exception;

class User extends Record implements

    Di\TemplateFileManagerAware,
    Di\EmailSenderAware,
    Di\HtmlizerFactoryAware,
    Di\FileManagerAware,
    Di\DataManagerAware
{
    use Di\TemplateFileManagerSetter;
    use Di\EmailSenderSetter;
    use Di\HtmlizerFactorySetter;
    use Di\FileManagerSetter;
    use Di\DataManagerSetter;

    protected $mandatorySelectAttributeList = [
        'isActive',
        'userName',
        'type',
    ];

    protected $validateSkipFieldList = ['name', "firstName", "lastName"];

    protected $allowedUserTypeList = ['regular', 'admin', 'portal', 'api'];

    public function getEntity(?string $id = null): ?Entity
    {
        if (isset($id) && $id == 'system') {
            throw new Forbidden();
        }

        /** @var ?UserEntity $entity */
        $entity = parent::getEntity($id);

        if ($entity && $entity->isSuperAdmin() && !$this->getUser()->isSuperAdmin()) {
            throw new Forbidden();
        }

        if ($entity && $entity->isSystem()) {
            throw new Forbidden();
        }

        return $entity;
    }

    public function changePassword(
        string $userId,
        string $password,
        bool $checkCurrentPassword = false,
        ?string $currentPassword = null
    ): void {

        /** @var ?UserEntity $user */
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
            throw new Error("Password can't be empty.");
        }

        if ($checkCurrentPassword) {
            $passwordHash = new PasswordHash($this->getConfig());

            $u = $this->getEntityManager()
                ->getRDBRepository('User')
                ->where([
                    'id' => $user->getId(),
                    'password' => $passwordHash->hash($currentPassword),
                ])
                ->findOne();

            if (!$u) {
                throw new Forbidden("Wrong password.");
            }
        }

        if (!$this->checkPasswordStrength($password)) {
            throw new Forbidden("Change password: Password is weak.");
        }

        $user->set('password', $this->hashPassword($password));

        $this->getEntityManager()->saveEntity($user);
    }

    public function checkPasswordStrength(string $password): bool
    {
        $minLength = $this->getConfig()->get('passwordStrengthLength');

        if ($minLength) {
            if (mb_strlen($password) < $minLength) {
                return false;
            }
        }

        $requiredLetterCount = $this->getConfig()->get('passwordStrengthLetterCount');

        if ($requiredLetterCount) {
            $letterCount = 0;

            foreach (str_split($password) as $c) {
                if (ctype_alpha($c)) {
                    $letterCount++;
                }
            }

            if ($letterCount < $requiredLetterCount) {
                return false;
            }
        }

        $requiredNumberCount = $this->getConfig()->get('passwordStrengthNumberCount');

        if ($requiredNumberCount) {
            $numberCount = 0;

            foreach (str_split($password) as $c) {
                if (is_numeric($c)) {
                    $numberCount++;
                }
            }

            if ($numberCount < $requiredNumberCount) {
                return false;
            }
        }

        $bothCases = $this->getConfig()->get('passwordStrengthBothCases');

        if ($bothCases) {
            $ucCount = 0;
            $lcCount = 0;

            foreach (str_split($password) as $c) {
                if (ctype_alpha($c) && $c === mb_strtoupper($c)) {
                    $ucCount++;
                }

                if (ctype_alpha($c) && $c === mb_strtolower($c)) {
                    $lcCount++;
                }
            }
            if (!$ucCount || !$lcCount) {
                return false;
            }
        }

        return true;
    }

    public function passwordChangeRequest(string $userName, string $emailAddress, ?string $url = null): void
    {
        $recovery = $this->injectableFactory->create(Recovery::class);

        $recovery->request($emailAddress, $userName, $url);
    }

    public function changePasswordByRequest(string $requestId, string $password): stdClass
    {
        $recovery = $this->injectableFactory->create(Recovery::class);

        $request = $recovery->getRequest($requestId);

        $userId = $request->get('userId');

        if (!$userId) {
            throw new Error();
        }

        $this->changePassword($userId, $password);

        $recovery->removeRequest($requestId);

        return (object) [
            'url' => $request->get('url'),
        ];
    }

    public function removeChangePasswordRequestJob($data)
    {
        if (empty($data->id)) {
            return false;
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
        $passwordHash = $this->injectableFactory->create(PasswordHash::class);

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

    public function create(stdClass $data, CreateParams $params): Entity
    {
        $newPassword = null;

        if (property_exists($data, 'password')) {
            $newPassword = $data->password;

            if (!$this->checkPasswordStrength($newPassword)) {
                throw new Forbidden("Password is weak.");
            }

            $data->password = $this->hashPassword($data->password);
        }

        /** @var UserEntity $user */
        $user = parent::create($data, $params);

        if (!is_null($newPassword) && !empty($data->sendAccessInfo)) {
            if ($user->isActive()) {
                try {
                    $this->sendPassword($user, $newPassword);
                }
                catch (Exception $e) {}
            }
        }

        return $user;
    }

    public function update(string $id, stdClass $data, UpdateParams $params): Entity
    {
        if ($id == 'system') {
            throw new Forbidden();
        }

        $newPassword = null;

        if (property_exists($data, 'password')) {
            $newPassword = $data->password;

            if (!$this->checkPasswordStrength($newPassword)) {
                throw new Forbidden("Password is weak.");
            }

            $data->password = $this->hashPassword($data->password);
        }

        if ($id == $this->getUser()->id) {
            unset($data->isActive);
            unset($data->isPortalUser);
            unset($data->type);
        }

        /** @var UserEntity $user */
        $user = parent::update($id, $data, $params);

        if (!is_null($newPassword)) {
            try {
                if ($user->isActive() && !empty($data->sendAccessInfo)) {
                    $this->sendPassword($user, $newPassword);
                }
            }
            catch (Exception $e) {}
        }

        return $user;
    }

    public function prepareEntityForOutput(Entity $entity)
    {
        assert($entity instanceof UserEntity);

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

    protected function getSecretKeyForUserId(string $id): ?string
    {
        $apiKeyUtil = $this->injectableFactory->create(ApiKeyUtil::class);

        return $apiKeyUtil->getSecretKeyForUserId($id);
    }

    public function generateNewApiKeyForEntity(string $id): Entity
    {
        /** @var ?UserEntity $entity */
        $entity = $this->getEntity($id);

        if (!$entity) {
            throw new NotFound();
        }

        if (!$this->getUser()->isAdmin()) {
            throw new Forbidden();
        }

        if (!$entity->isApi()) {
            throw new Forbidden();
        }

        $apiKey = Util::generateApiKey();

        $entity->set('apiKey', $apiKey);

        if ($entity->get('authMethod') === 'Hmac') {
            $secretKey = Util::generateSecretKey();

            $entity->set('secretKey', $secretKey);
        }

        $this->getEntityManager()->saveEntity($entity);

        $this->prepareEntityForOutput($entity);

        return $entity;
    }

    public function generateNewPasswordForUser(string $id, bool $allowNonAdmin = false)
    {
        if (!$allowNonAdmin) {
            if (!$this->getUser()->isAdmin()) {
                throw new Forbidden();
            }
        }

        /** @var ?UserEntity $user */
        $user = $this->getEntity($id);

        if (!$user) {
            throw new NotFound();
        }

        if ($user->isApi()) {
            throw new Forbidden();
        }

        if ($user->isSuperAdmin()) {
            throw new Forbidden();
        }

        if ($user->isSystem()) {
            throw new Forbidden();
        }

        if (!$user->get('emailAddress')) {
            throw new Forbidden(
                "Generate new password: Can't process because user doesn't have email address."
            );
        }

        if (!$this->emailSender->hasSystemSmtp() && !$this->getConfig()->get('internalSmtpServer')) {
            throw new Forbidden(
                "Generate new password: Can't process because SMTP is not configured."
            );
        }

        $length = $this->getConfig()->get('passwordStrengthLength');
        $letterCount = $this->getConfig()->get('passwordStrengthLetterCount');
        $numberCount = $this->getConfig()->get('passwordStrengthNumberCount');

        $generateLength = $this->getConfig()->get('passwordGenerateLength', 10);
        $generateLetterCount = $this->getConfig()->get('passwordGenerateLetterCount', 4);
        $generateNumberCount = $this->getConfig()->get('passwordGenerateNumberCount', 2);

        $length = is_null($length) ? $generateLength : $length;
        $letterCount = is_null($letterCount) ? $generateLetterCount : $letterCount;
        $numberCount = is_null($letterCount) ? $generateNumberCount : $numberCount;

        if ($length < $generateLength) {
            $length = $generateLength;
        }

        if ($letterCount < $generateLetterCount) {
            $letterCount = $generateLetterCount;
        }

        if ($numberCount < $generateNumberCount) {
            $numberCount = $generateNumberCount;
        }

        $password = Util::generatePassword($length, $letterCount, $numberCount, true);

        $this->sendPassword($user, $password);

        $passwordHash = new PasswordHash($this->getConfig());

        $user->set('password', $passwordHash->hash($password));

        $this->getEntityManager()->saveEntity($user);
    }

    protected function getInternalUserCount()
    {
        return $this->getEntityManager()
            ->getRDBRepository('User')
            ->where([
                'isActive' => true,
                'type' => ['admin', 'regular'],
                'type!=' => 'system',
            ])
            ->count();
    }

    protected function getPortalUserCount()
    {
        return $this->getEntityManager()
            ->getRDBRepository('User')
            ->where([
                'isActive' => true,
                'type' => 'portal',
            ])
            ->count();
    }

    protected function beforeCreateEntity(Entity $entity, $data)
    {
        /** @var UserEntity $entity */

        if (
            $this->getConfig()->get('userLimit') &&
            !$this->getUser()->isSuperAdmin() &&
            !$entity->isPortal() && !$entity->isApi()
        ) {
            $userCount = $this->getInternalUserCount();

            if ($userCount >= $this->getConfig()->get('userLimit')) {
                throw new Forbidden(
                    'User limit '.$this->getConfig()->get('userLimit').' is reached.'
                );
            }
        }
        if (
            $this->getConfig()->get('portalUserLimit') &&
            !$this->getUser()->isSuperAdmin() &&
            $entity->isPortal()
        ) {
            $portalUserCount = $this->getPortalUserCount();

            if ($portalUserCount >= $this->getConfig()->get('portalUserLimit')) {
                throw new Forbidden(
                    'Portal user limit ' . $this->getConfig()->get('portalUserLimit').' is reached.'
                );
            }
        }

        if ($entity->isApi()) {
            $apiKey = Util::generateApiKey();

            $entity->set('apiKey', $apiKey);

            if ($entity->get('authMethod') === 'Hmac') {
                $secretKey = Util::generateSecretKey();

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
        /** @var UserEntity $entity */

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
                    throw new Forbidden(
                        'Portal user limit '. $this->getConfig()->get('portalUserLimit').' is reached.'
                    );
                }
            }
        }

        if ($entity->isApi()) {
            if (
                $entity->isAttributeChanged('authMethod') &&
                $entity->get('authMethod') === 'Hmac'
            ) {
                $secretKey = Util::generateSecretKey();

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

    protected function sendPassword(UserEntity $user, $password)
    {
        $emailAddress = $user->get('emailAddress');

        if (empty($emailAddress)) {
            return;
        }

        /** @var EmailEntity $email */
        $email = $this->getEntityManager()->getEntity('Email');

        if (!$this->emailSender->hasSystemSmtp() && !$this->getConfig()->get('internalSmtpServer')) {
            return;
        }

        $templateFileManager = $this->templateFileManager;

        $siteUrl = $this->getConfig()->getSiteUrl() . '/';

        $data = [];

        if ($user->isPortal()) {
            $subjectTpl = $templateFileManager->getTemplate('accessInfoPortal', 'subject', 'User');
            $bodyTpl = $templateFileManager->getTemplate('accessInfoPortal', 'body', 'User');

            $urlList = [];

            $portalList = $this->entityManager
                ->getRDBRepository('Portal')
                ->distinct()
                ->join('users')
                ->where([
                    'isActive' => true,
                    'users.id' => $user->getId(),
                ])
                ->find();

            foreach ($portalList as $portal) {
                if ($portal->get('customUrl')) {
                    $urlList[] = $portal->get('customUrl');
                }
                else {
                    $url = $siteUrl . 'portal/';

                    if ($this->getConfig()->get('defaultPortalId') !== $portal->getId()) {
                        if ($portal->get('customId')) {
                            $url .= $portal->get('customId');
                        }
                        else {
                            $url .= $portal->getId();
                        }
                    }

                    $urlList[] = $url;
                }
            }

            if (!count($urlList)) {
                return;
            }

            $data['siteUrlList'] = $urlList;
        }
        else {
            $subjectTpl = $templateFileManager->getTemplate('accessInfo', 'subject', 'User');
            $bodyTpl = $templateFileManager->getTemplate('accessInfo', 'body', 'User');

            $data['siteUrl'] = $siteUrl;
        }

        $data['password'] = $password;

        $htmlizer = $this->htmlizerFactory->create(true);

        $subject = $htmlizer->render($user, $subjectTpl, null, $data, true);
        $body = $htmlizer->render($user, $bodyTpl, null, $data, true);

        $email->set([
            'subject' => $subject,
            'body' => $body,
            'to' => $emailAddress,
        ]);

        $sender = $this->emailSender->create();

        if (!$this->emailSender->hasSystemSmtp()) {
            $sender->withSmtpParams([
                'server' => $this->getConfig()->get('internalSmtpServer'),
                'port' => $this->getConfig()->get('internalSmtpPort'),
                'auth' => $this->getConfig()->get('internalSmtpAuth'),
                'username' => $this->getConfig()->get('internalSmtpUsername'),
                'password' => $this->getConfig()->get('internalSmtpPassword'),
                'security' => $this->getConfig()->get('internalSmtpSecurity'),
                'fromAddress' => $this->getConfig()->get(
                    'internalOutboundEmailFromAddress',
                    $this->getConfig()->get('outboundEmailFromAddress')
                ),
            ]);
        }

        $sender->send($email);
    }

    public function delete(string $id, DeleteParams $params): void
    {
        if ($id == 'system') {
            throw new Forbidden();
        }

        if ($id == $this->getUser()->id) {
            throw new Forbidden();
        }

        parent::delete($id, $params);
    }

    public function afterUpdateEntity(Entity $entity, $data)
    {
        assert($entity instanceof UserEntity);

        parent::afterUpdateEntity($entity, $data);

        if (
            property_exists($data, 'rolesIds') ||
            property_exists($data, 'teamsIds') ||
            property_exists($data, 'type') ||
            property_exists($data, 'portalRolesIds') ||
            property_exists($data, 'portalsIds')
        ) {
            $this->clearRoleCache($entity->getId());
        }

        if (
            property_exists($data, 'portalRolesIds') ||
            property_exists($data, 'portalsIds') ||
            property_exists($data, 'contactId') ||
            property_exists($data, 'accountsIds')
        ) {
            $this->clearPortalRolesCache();
        }

        if (
            $entity->isPortal() && $entity->get('contactId') &&
            (
                property_exists($data, 'firstName') ||
                property_exists($data, 'lastName') ||
                property_exists($data, 'salutationName')
            )
        ) {
            $contact = $this->getEntityManager()->getEntity('Contact', $entity->get('contactId'));

            if ($contact) {
                if (property_exists($data, 'firstName')) {
                    $contact->set('firstName', $data->firstName);
                }

                if (property_exists($data, 'lastName')) {
                    $contact->set('lastName', $data->lastName);
                }

                if (property_exists($data, 'salutationName')) {
                    $contact->set('salutationName', $data->salutationName);
                }

                $this->getEntityManager()->saveEntity($contact);
            }
        }
    }

    protected function clearRoleCache(string $id)
    {
        $this->fileManager->removeFile('data/cache/application/acl/' . $id . '.php');
        $this->fileManager->removeFile('data/cache/application/aclMap/' . $id . '.php');

        $this->dataManager->updateCacheTimestamp();
    }

    protected function clearPortalRolesCache()
    {
        $this->fileManager->removeInDir('data/cache/application/aclPortal');
        $this->fileManager->removeInDir('data/cache/application/aclPortalMap');

        $this->dataManager->updateCacheTimestamp();
    }
}
