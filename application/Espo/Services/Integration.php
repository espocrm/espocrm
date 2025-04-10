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

namespace Espo\Services;

use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\NotFound;
use Espo\Core\ORM\Type\FieldType;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\Config\ConfigWriter;
use Espo\Core\Utils\Metadata;
use Espo\Entities\Integration as IntegrationEntity;
use Espo\Entities\User;
use Espo\ORM\Entity;
use Espo\ORM\EntityManager;

use stdClass;

class Integration
{
    public function __construct(
        private EntityManager $entityManager,
        private User $user,
        private Config $config,
        private ConfigWriter $configWriter,
        private Metadata $metadata,
    ) {}

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

        /** @var ?IntegrationEntity $entity */
        $entity = $this->entityManager->getEntityById(IntegrationEntity::ENTITY_TYPE, $id);

        if (!$entity) {
            throw new NotFound();
        }

        $this->prepareEntity($entity);

        return $entity;
    }

    /**
     * @throws Forbidden
     * @throws NotFound
     */
    public function update(string $id, stdClass $data): Entity
    {
        $this->processAccessCheck();

        /** @var ?IntegrationEntity $entity */
        $entity = $this->entityManager->getEntityById(IntegrationEntity::ENTITY_TYPE, $id);

        if (!$entity) {
            throw new NotFound();
        }

        $entity->set($data);

        $this->entityManager->saveEntity($entity);

        $configData = $this->config->get('integrations') ?? (object) [];

        if (!$configData instanceof stdClass) {
            $configData = (object) [];
        }

        $configData->$id = $entity->get('enabled');

        $this->configWriter->set('integrations', $configData);
        $this->configWriter->save();

        $this->prepareEntity($entity);

        return $entity;
    }

    private function prepareEntity(IntegrationEntity $entity): void
    {
        /** @var array<string, array<string, mixed>> $fields */
        $fields = $this->metadata->get("integrations.{$entity->getId()}.fields") ?? [];

        foreach ($fields as $field => $fieldDefs) {
            $type = $fieldDefs['type'] ?? null;

            if ($type === FieldType::PASSWORD) {
                $entity->clear($field);
            }
        }
    }
}
