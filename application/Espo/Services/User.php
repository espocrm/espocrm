<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2024 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
 * Website: https://www.espocrm.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU Affero General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU Affero General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

namespace Espo\Services;

use Espo\Core\Authentication\Logins\Hmac;
use Espo\Core\Exceptions\Conflict;
use Espo\Core\Exceptions\NotFound;
use Espo\Core\Mail\Exceptions\SendingError;
use Espo\Entities\Team as TeamEntity;
use Espo\Entities\User as UserEntity;
use Espo\Modules\Crm\Entities\Contact;
use Espo\Core\Acl\Cache\Clearer as AclCacheClearer;
use Espo\Core\Di;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Error;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Record\CreateParams;
use Espo\Core\Record\DeleteParams;
use Espo\Core\Record\UpdateParams;
use Espo\Core\Utils\ApiKey as ApiKeyUtil;
use Espo\Core\Utils\PasswordHash;
use Espo\Core\Utils\Util;
use Espo\ORM\Entity;
use Espo\ORM\Query\SelectBuilder;
use Espo\Tools\UserSecurity\Password\Checker as PasswordChecker;
use Espo\Tools\UserSecurity\Password\Generator as PasswordGenerator;
use Espo\Tools\UserSecurity\Password\Sender as PasswordSender;
use Espo\Tools\UserSecurity\Password\Service as PasswordService;
use stdClass;
use Exception;

/**
 * @extends Record<UserEntity>
 */
class User extends Record implements

    Di\DataManagerAware
{
    use Di\DataManagerSetter;

    /** @var string[] */
    protected $mandatorySelectAttributeList = [
        'isActive',
        'userName',
        'type',
    ];

    /** @var string[] */
    private $allowedUserTypeList = [
        UserEntity::TYPE_REGULAR,
        UserEntity::TYPE_ADMIN,
        UserEntity::TYPE_PORTAL,
        UserEntity::TYPE_API,
    ];

    /**
     * @throws Forbidden
     */
    public function getEntity(string $id): ?Entity
    {
        /** @var ?UserEntity $entity */
        $entity = parent::getEntity($id);

        if (!$entity) {
            return null;
        }

        if ($entity->isSuperAdmin() && !$this->user->isSuperAdmin()) {
            throw new Forbidden();
        }

        if ($entity->isSystem()) {
            throw new Forbidden();
        }

        return $entity;
    }

    private function hashPassword(string $password): string
    {
        $passwordHash = $this->injectableFactory->create(PasswordHash::class);

        return $passwordHash->hash($password);
    }

    protected function filterInput($data)
    {
        parent::filterInput($data);

        if (!$this->user->isSuperAdmin()) {
            unset($data->isSuperAdmin);
        }

        if (!$this->user->isAdmin()) {
            if (!$this->acl->checkScope(TeamEntity::ENTITY_TYPE)) {
                unset($data->defaultTeamId);
            }
        }
    }

    /**
     * @throws BadRequest
     */
    private function fetchPassword(stdClass $data): ?string
    {
        $password = $data->password ?? null;

        if ($password === '') {
            $password = null;
        }

        if ($password !== null && !is_string($password)) {
            throw new BadRequest("Bad password value.");
        }

        return $password;
    }

    public function create(stdClass $data, CreateParams $params): Entity
    {
        $newPassword = $this->fetchPassword($data);

        $passwordSpecified = $newPassword !== null;

        if (
            $newPassword !== null &&
            !$this->createPasswordChecker()->checkStrength($newPassword)
        ) {
            throw new Forbidden("Password is weak.");
        }

        if (!$newPassword) {
            // Generate a password as authentication implementations may require user records
            // to have passwords for auth token mechanism functioning.
            $newPassword = $this->createPasswordGenerator()->generate();
        }

        $data->password = $this->hashPassword($newPassword);

        /** @var UserEntity $user */
        $user = parent::create($data, $params);

        $sendAccessInfo = !empty($data->sendAccessInfo);

        if (!$sendAccessInfo || !$user->isActive() || $user->isApi()) {
            return $user;
        }

        try {
            if ($passwordSpecified) {
                $this->sendPassword($user, $newPassword);

                return $user;
            }

            $this->getPasswordService()->sendAccessInfoForNewUser($user);
        }
        catch (Exception $e) {
            $this->log->error("Could not send user access info. " . $e->getMessage());
        }

        return $user;
    }

    public function update(string $id, stdClass $data, UpdateParams $params): Entity
    {
        $newPassword = null;

        if (property_exists($data, 'password')) {
            $newPassword = $data->password;

            if (!$this->createPasswordChecker()->checkStrength($newPassword)) {
                throw new Forbidden("Password is weak.");
            }

            $data->password = $this->hashPassword($data->password);
        }

        if ($id === $this->user->getId()) {
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
            catch (Exception) {}
        }

        return $user;
    }

    private function getPasswordService(): PasswordService
    {
        return $this->injectableFactory->create(PasswordService::class);
    }

    /**
     * @throws SendingError
     * @throws Error
     */
    private function sendPassword(UserEntity $user, string $password): void
    {
        $this->injectableFactory
            ->create(PasswordSender::class)
            ->sendPassword($user, $password);
    }

    public function prepareEntityForOutput(Entity $entity)
    {
        assert($entity instanceof UserEntity);

        parent::prepareEntityForOutput($entity);

        $entity->clear('sendAccessInfo');

        if ($entity->isApi()) {
            if ($this->user->isAdmin()) {
                if ($entity->getAuthMethod() === Hmac::NAME) {
                    $secretKey = $this->getSecretKeyForUserId($entity->getId());
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

    protected function getInternalUserCount(): int
    {
        return $this->entityManager
            ->getRDBRepository(UserEntity::ENTITY_TYPE)
            ->where([
                'isActive' => true,
                'type' => [
                    UserEntity::TYPE_ADMIN,
                    UserEntity::TYPE_REGULAR,
                ],
            ])
            ->count();
    }

    protected function getPortalUserCount(): int
    {
        return $this->entityManager
            ->getRDBRepositoryByClass(UserEntity::class)
            ->where([
                'isActive' => true,
                'type' => UserEntity::TYPE_PORTAL,
            ])
            ->count();
    }

    /**
     * @throws Conflict
     */
    private function processUserExistsChecking(UserEntity $user): void
    {
        $existing = $this->getRepository()
            ->select('id')
            ->where(['userName' => $user->getUserName()])
            ->findOne();

        if ($existing) {
            throw new Conflict('userNameExists');
        }
    }

    /**
     * @param UserEntity $entity
     * @throws Forbidden
     * @throws Conflict
     */
    protected function beforeCreateEntity(Entity $entity, $data)
    {
        $userLimit = $this->config->get('userLimit');

        if (
            $userLimit &&
            !$this->user->isSuperAdmin() &&
            !$entity->isPortal() && !$entity->isApi()
        ) {
            $userCount = $this->getInternalUserCount();

            if ($userCount >= $userLimit) {
                throw new Forbidden("User limit {$userLimit} is reached.");
            }
        }

        $portalUserLimit = $this->config->get('portalUserLimit');

        if (
            $portalUserLimit &&
            !$this->user->isSuperAdmin() &&
            $entity->isPortal()
        ) {
            $portalUserCount = $this->getPortalUserCount();

            if ($portalUserCount >= $portalUserLimit) {
                throw new Forbidden("Portal user limit {$portalUserLimit} is reached.");
            }
        }

        $this->processUserExistsChecking($entity);

        if ($entity->isApi()) {
            $entity->set('apiKey', Util::generateApiKey());

            if ($entity->getAuthMethod() === Hmac::NAME) {
                $secretKey = Util::generateSecretKey();

                $entity->set('secretKey', $secretKey);
            }
        }

        if (
            !$entity->isSuperAdmin() &&
            $entity->getType() &&
            !in_array($entity->getType(), $this->allowedUserTypeList)
        ) {
            throw new Forbidden();
        }
    }

    /**
     * @param UserEntity $entity
     * @throws Forbidden
     * @throws Conflict
     */
    protected function beforeUpdateEntity(Entity $entity, $data)
    {
        $userLimit = $this->config->get('userLimit');

        if (
            $userLimit &&
            !$this->user->isSuperAdmin() &&
            (
                (
                    $entity->isActive() &&
                    $entity->isAttributeChanged('isActive') &&
                    !$entity->isPortal() &&
                    !$entity->isApi()
                ) ||
                (
                    !$entity->isPortal() &&
                    !$entity->isApi() &&
                    $entity->isAttributeChanged('type') &&
                    (
                        $entity->isRegular() ||
                        $entity->isAdmin()
                    ) &&
                    (
                        $entity->getFetched('type') == UserEntity::TYPE_PORTAL ||
                        $entity->getFetched('type') == UserEntity::TYPE_API
                    )
                )
            )
        ) {
            $userCount = $this->getInternalUserCount();

            if ($userCount >= $userLimit) {
                throw new Forbidden("User limit {$userLimit} is reached.");
            }
        }

        $portalUserLimit = $this->config->get('portalUserLimit');

        if (
            $portalUserLimit &&
            !$this->user->isSuperAdmin() &&
            (
                (
                    $entity->isActive() &&
                    $entity->isAttributeChanged('isActive') &&
                    $entity->isPortal()
                ) ||
                (
                    $entity->isPortal() &&
                    $entity->isAttributeChanged('type')
                )
            )
        ) {
            $portalUserCount = $this->getPortalUserCount();

            if ($portalUserCount >= $portalUserLimit) {
                throw new Forbidden("Portal user limit {$portalUserLimit} is reached.");
            }
        }

        if ($entity->isAttributeChanged('userName')) {
            $this->processUserExistsChecking($entity);
        }

        if (
            $entity->isApi() &&
            $entity->isAttributeChanged('authMethod') &&
            $entity->getAuthMethod() === Hmac::NAME
        ) {
            $secretKey = Util::generateSecretKey();

            $entity->set('secretKey', $secretKey);
        }

        if (
            !$entity->isSuperAdmin() &&
            $entity->isAttributeChanged('type') &&
            $entity->getType() &&
            !in_array($entity->getType(), $this->allowedUserTypeList)
        ) {
            throw new Forbidden("Can't change type.");
        }
    }

    public function delete(string $id, DeleteParams $params): void
    {
        if ($id === $this->user->getId()) {
            throw new Forbidden("Can't delete own user.");
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
            $this->clearRoleCache($entity);
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
            $entity->isPortal() &&
            $entity->getContactId() &&
            (
                property_exists($data, 'firstName') ||
                property_exists($data, 'lastName') ||
                property_exists($data, 'salutationName')
            )
        ) {
            $contact = $this->entityManager->getEntityById(Contact::ENTITY_TYPE, $entity->getContactId());

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

                $this->entityManager->saveEntity($contact);
            }
        }
    }

    /**
     * @throws Forbidden
     * @throws NotFound
     * @throws Conflict
     */
    public function restoreDeleted(string $id): void
    {
        $entity = $this->getRepository()
            ->clone(
                SelectBuilder::create()
                    ->from(UserEntity::ENTITY_TYPE)
                    ->withDeleted()
                    ->build()
            )
            ->where(['id' => $id])
            ->findOne();

        if ($entity) {
            $this->processUserExistsChecking($entity);
        }

        parent::restoreDeleted($id);

        $entity = $this->getRepository()->getById($id);

        if ($entity) {
            $entity->set('deleteId', '0');

            $this->getRepository()->save($entity);
        }
    }

    protected function clearRoleCache(UserEntity $user): void
    {
        $this->createAclCacheClearer()->clearForUser($user);

        $this->dataManager->updateCacheTimestamp();
    }

    protected function clearPortalRolesCache(): void
    {
        $this->createAclCacheClearer()->clearForAllPortalUsers();

        $this->dataManager->updateCacheTimestamp();
    }

    private function createPasswordChecker(): PasswordChecker
    {
        return $this->injectableFactory->create(PasswordChecker::class);
    }

    private function createAclCacheClearer(): AclCacheClearer
    {
        return $this->injectableFactory->create(AclCacheClearer::class);
    }

    private function createPasswordGenerator(): PasswordGenerator
    {
        return $this->injectableFactory->create(PasswordGenerator::class);
    }
}
