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

use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\NotFound;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\Config\ConfigWriter;
use Espo\Entities\User;
use Espo\ORM\Entity;
use Espo\ORM\EntityManager;

use stdClass;

class Integration
{
    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var User
     */
    protected $user;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var ConfigWriter
     */
    protected $configWriter;

    public function __construct(
        EntityManager $entityManager,
        User $user,
        Config $config,
        ConfigWriter $configWriter
    ) {
        $this->entityManager = $entityManager;
        $this->user = $user;
        $this->config = $config;
        $this->configWriter = $configWriter;
    }

    /**
     * @return void
     * @throws Forbidden
     */
    protected function processAccessCheck()
    {
        if (!$this->user->isAdmin()) {
            throw new Forbidden();
        }
    }

    /**
     * @throws Forbidden
     * @throws NotFound
     */
    public function read(string $id): Entity
    {
        $this->processAccessCheck();

        $entity = $this->entityManager->getEntity('Integration', $id);

        if (!$entity) {
            throw new NotFound();
        }

        return $entity;
    }

    /**
     * @throws Forbidden
     * @throws NotFound
     */
    public function update(string $id, stdClass $data): Entity
    {
        $this->processAccessCheck();

        $entity = $this->entityManager->getEntity('Integration', $id);

        if (!$entity) {
            throw new NotFound();
        }

        $entity->set($data);

        $this->entityManager->saveEntity($entity);

        $integrationsConfigData = $this->config->get('integrations') ?? (object) [];

        if (!($integrationsConfigData instanceof stdClass)) {
            $integrationsConfigData = (object) [];
        }

        $integrationName = $id;

        $integrationsConfigData->$integrationName = $entity->get('enabled');

        $this->configWriter->set('integrations', $integrationsConfigData);

        $this->configWriter->save();

        return $entity;
    }
}
