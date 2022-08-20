<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2022 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

use Espo\ORM\EntityManager;

use Espo\Repositories\Preferences as Repository;

use Espo\Entities\{
    User,
    Preferences as PreferencesEntity,
};

use Espo\Core\{
    Exceptions\BadRequest,
    Exceptions\Forbidden,
    Exceptions\NotFound,
    FieldValidation\FieldValidationManager,
    Utils\Crypt,
    Acl\Table,
    Acl,
    Utils\Config,
};

use stdClass;

class Preferences
{
    private EntityManager $entityManager;
    private User $user;
    private Crypt $crypt;
    private Acl $acl;
    private Config $config;
    private FieldValidationManager $fieldValidationManager;

    public function __construct(
        EntityManager $entityManager,
        User $user,
        Crypt $crypt,
        Acl $acl,
        Config $config,
        FieldValidationManager $fieldValidationManager
    ) {
        $this->entityManager = $entityManager;
        $this->user = $user;
        $this->crypt = $crypt;
        $this->acl = $acl;
        $this->config = $config;
        $this->fieldValidationManager = $fieldValidationManager;
    }

    /**
     * @throws Forbidden
     */
    protected function processAccessCheck(string $userId): void
    {
        if (!$this->user->isAdmin()) {
            if ($this->user->getId() !== $userId) {
                throw new Forbidden();
            }
        }
    }

    /**
     * @throws Forbidden
     * @throws NotFound
     */
    public function read(string $userId): PreferencesEntity
    {
        $this->processAccessCheck($userId);

        /** @var ?PreferencesEntity $entity */
        $entity = $this->entityManager->getEntityById(PreferencesEntity::ENTITY_TYPE, $userId);
        /** @var ?User $user */
        $user = $this->entityManager->getEntityById(User::ENTITY_TYPE, $userId);

        if (!$entity || !$user) {
            throw new NotFound();
        }

        $entity->set('smtpEmailAddress', $user->getEmailAddress());
        $entity->set('name', $user->getName());
        $entity->set('isPortalUser', $user->isPortal());

        $entity->clear('smtpPassword');

        $forbiddenAttributeList = $this->acl
            ->getScopeForbiddenAttributeList(PreferencesEntity::ENTITY_TYPE, Table::ACTION_READ);

        foreach ($forbiddenAttributeList as $attribute) {
            $entity->clear($attribute);
        }

        return $entity;
    }

    /**
     * @throws Forbidden
     * @throws NotFound
     * @throws BadRequest
     */
    public function update(string $userId, stdClass $data): PreferencesEntity
    {
        $this->processAccessCheck($userId);

        if ($this->acl->getLevel(PreferencesEntity::ENTITY_TYPE, Table::ACTION_EDIT) === Table::LEVEL_NO) {
            throw new Forbidden();
        }

        $forbiddenAttributeList = $this->acl
            ->getScopeForbiddenAttributeList(PreferencesEntity::ENTITY_TYPE, Table::ACTION_EDIT);

        foreach ($forbiddenAttributeList as $attribute) {
            unset($data->$attribute);
        }

        /** @var ?User $user */
        $user = $this->entityManager->getEntityById(User::ENTITY_TYPE, $userId);

        /** @var ?PreferencesEntity $entity */
        $entity = $this->entityManager->getEntityById(PreferencesEntity::ENTITY_TYPE, $userId);

        if (!$entity || !$user) {
            throw new NotFound();
        }

        if (property_exists($data, 'smtpPassword')) {
            $data->smtpPassword = $this->crypt->encrypt($data->smtpPassword);
        }

        $entity->set($data);

        $this->fieldValidationManager->process($entity, $data);

        $this->entityManager->saveEntity($entity);

        $entity->set('smtpEmailAddress', $user->getEmailAddress());
        $entity->set('name', $user->getName());

        $entity->clear('smtpPassword');

        return $entity;
    }

    /**
     * @throws Forbidden
     * @throws NotFound
     */
    public function resetToDefaults(string $userId): void
    {
        $this->processAccessCheck($userId);

        $result = $this->getRepository()->resetToDefaults($userId);

        if (!$result) {
            throw new NotFound();
        }
    }

    /**
     * @throws Forbidden
     * @throws NotFound
     */
    public function resetDashboard(string $userId): stdClass
    {
        $this->processAccessCheck($userId);

        if ($this->acl->getLevel(PreferencesEntity::ENTITY_TYPE, Table::ACTION_EDIT) === Table::LEVEL_NO) {
            throw new Forbidden();
        }

        /** @var ?User $user */
        $user = $this->entityManager->getEntityById(User::ENTITY_TYPE, $userId);

        $preferences = $this->entityManager->getEntityById(PreferencesEntity::ENTITY_TYPE, $userId);

        if (!$user) {
            throw new NotFound();
        }

        if (!$preferences) {
            throw new NotFound();
        }

        if ($user->isPortal()) {
            throw new Forbidden();
        }

        $forbiddenAttributeList = $this->acl
            ->getScopeForbiddenAttributeList(PreferencesEntity::ENTITY_TYPE, Table::ACTION_EDIT);

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

    private function getRepository(): Repository
    {
        /** @var Repository */
        return $this->entityManager->getRepository(PreferencesEntity::ENTITY_TYPE);
    }
}
