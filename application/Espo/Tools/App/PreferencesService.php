<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

namespace Espo\Tools\App;

use Espo\Core\Name\Field;
use Espo\ORM\EntityManager;

use Espo\Repositories\Preferences as Repository;
use Espo\Entities\Preferences;
use Espo\Entities\User;

use Espo\Core\Acl;
use Espo\Core\Acl\Table;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\NotFound;
use Espo\Core\FieldValidation\FieldValidationManager;
use Espo\Core\Utils\Config;

use stdClass;

class PreferencesService
{
    private EntityManager $entityManager;
    private User $user;
    private Acl $acl;
    private Config $config;
    private FieldValidationManager $fieldValidationManager;

    public function __construct(
        EntityManager $entityManager,
        User $user,
        Acl $acl,
        Config $config,
        FieldValidationManager $fieldValidationManager
    ) {
        $this->entityManager = $entityManager;
        $this->user = $user;
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
    public function read(string $userId): Preferences
    {
        $this->processAccessCheck($userId);

        /** @var ?Preferences $entity */
        $entity = $this->entityManager->getEntityById(Preferences::ENTITY_TYPE, $userId);
        /** @var ?User $user */
        $user = $this->entityManager->getEntityById(User::ENTITY_TYPE, $userId);

        if (!$entity || !$user) {
            throw new NotFound();
        }

        $entity->set(Field::NAME, $user->getName());
        $entity->set('isPortalUser', $user->isPortal());

        // @todo Remove.
        $entity->clear('smtpPassword');

        $forbiddenAttributeList = $this->acl
            ->getScopeForbiddenAttributeList(Preferences::ENTITY_TYPE, Table::ACTION_READ);

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
    public function update(string $userId, stdClass $data): Preferences
    {
        $this->processAccessCheck($userId);

        if ($this->acl->getLevel(Preferences::ENTITY_TYPE, Table::ACTION_EDIT) === Table::LEVEL_NO) {
            throw new Forbidden();
        }

        $forbiddenAttributeList = $this->acl
            ->getScopeForbiddenAttributeList(Preferences::ENTITY_TYPE, Table::ACTION_EDIT);

        foreach ($forbiddenAttributeList as $attribute) {
            unset($data->$attribute);
        }

        /** @var ?User $user */
        $user = $this->entityManager->getEntityById(User::ENTITY_TYPE, $userId);

        /** @var ?Preferences $entity */
        $entity = $this->entityManager->getEntityById(Preferences::ENTITY_TYPE, $userId);

        if (!$entity || !$user) {
            throw new NotFound();
        }

        $entity->set($data);

        $this->fieldValidationManager->process($entity, $data);

        $this->entityManager->saveEntity($entity);

        $entity->set(Field::NAME, $user->getName());

        // @todo Remove.
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

        if ($this->acl->getLevel(Preferences::ENTITY_TYPE, Table::ACTION_EDIT) === Table::LEVEL_NO) {
            throw new Forbidden();
        }

        /** @var ?User $user */
        $user = $this->entityManager->getEntityById(User::ENTITY_TYPE, $userId);

        $preferences = $this->entityManager->getEntityById(Preferences::ENTITY_TYPE, $userId);

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
            ->getScopeForbiddenAttributeList(Preferences::ENTITY_TYPE, Table::ACTION_EDIT);

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
        return $this->entityManager->getRepository(Preferences::ENTITY_TYPE);
    }
}
