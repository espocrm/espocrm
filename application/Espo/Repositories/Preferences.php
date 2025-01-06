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

namespace Espo\Repositories;

use Espo\Entities\Autofollow;
use Espo\ORM\Defs\Params\FieldParam;
use Espo\ORM\Entity;
use Espo\ORM\EntityManager;
use Espo\ORM\EntityFactory;
use Espo\ORM\Name\Attribute;
use Espo\ORM\Repository\Repository;
use Espo\Core\Utils\Json;

use Espo\Entities\Preferences as PreferencesEntity;
use Espo\Entities\User;

use RuntimeException;
use stdClass;

use Espo\Core\Di;

/**
 * @implements Repository<PreferencesEntity>
 */
class Preferences implements Repository,

    Di\MetadataAware,
    Di\ConfigAware,
    Di\EntityManagerAware
{
    use Di\MetadataSetter;
    use Di\ConfigSetter;
    use Di\EntityManagerSetter;

    /**
     * @var EntityFactory
     */
    protected $entityFactory;

    public function __construct(
        EntityManager $entityManager,
        EntityFactory $entityFactory
    ) {
        $this->entityFactory = $entityFactory;
        $this->entityManager = $entityManager;
    }

    /**
     * @var string[]
     */
    protected $defaultAttributeListFromSettings = [
        'decimalMark',
        'thousandSeparator',
        'exportDelimiter',
        'followCreatedEntities',
    ];

    /**
     * @var array<string, array<string, mixed>>
     */
    private $data = [];

    public function getNew(): Entity
    {
        /** @var PreferencesEntity */
        return $this->entityFactory->create(PreferencesEntity::ENTITY_TYPE);
    }

    public function getById(string $id): ?Entity
    {
        /** @var PreferencesEntity $entity */
        $entity = $this->entityFactory->create(PreferencesEntity::ENTITY_TYPE);

        $entity->set('id', $id);

        if (!isset($this->data[$id])) {
            $this->loadData($id);
        }

        $entity->set($this->data[$id]);

        $this->fetchAutoFollowEntityTypeList($entity);

        $entity->setAsFetched();

        return $entity;
    }

    /**
     * @deprecated Use `getById`.
     * @todo Remove in v10.0.
     */
    public function get(?string $id = null): ?Entity
    {
        if ($id === null) {
            return $this->getNew();
        }

        return $this->getById($id);
    }

    protected function loadData(string $id): void
    {
        $data = null;

        $select = $this->entityManager->getQueryBuilder()
            ->select()
            ->from(PreferencesEntity::ENTITY_TYPE)
            ->select([Attribute::ID, 'data'])
            ->where([
                Attribute::ID => $id,
            ])
            ->limit(0, 1)
            ->build();

        $sth = $this->entityManager->getQueryExecutor()->execute($select);

        while ($row = $sth->fetch()) {
            $data = Json::decode($row['data']);

            break;
        }

        if ($data) {
            $this->data[$id] = get_object_vars($data);

            return;
        }

        /** @var array<string, array<string, mixed>> $fields */
        $fields = $this->metadata->get('entityDefs.Preferences.fields');

        $defaults = [];

        $dashboardLayout = $this->config->get('dashboardLayout');
        $dashletsOptions = null;

        if (!$dashboardLayout) {
            $dashboardLayout = $this->metadata->get('app.defaultDashboardLayouts.Standard');
            $dashletsOptions = $this->metadata->get('app.defaultDashboardOptions.Standard');
        }

        if ($dashletsOptions === null) {
            $dashletsOptions = $this->config->get('dashletsOptions', (object) []);
        }

        $defaults['dashboardLayout'] = $dashboardLayout;
        $defaults['dashletsOptions'] = $dashletsOptions;

        foreach ($fields as $field => $d) {
            if (array_key_exists('default', $d)) {
                $defaults[$field] = $d['default'];
            }
        }

        foreach ($this->defaultAttributeListFromSettings as $attr) {
            $defaults[$attr] = $this->config->get($attr);
        }

        $this->data[$id] = $defaults;
    }

    protected function fetchAutoFollowEntityTypeList(PreferencesEntity $entity): void
    {
        $id = $entity->getId();

        $autoFollowEntityTypeList = [];

        $autofollowList = $this->entityManager
            ->getRDBRepository(Autofollow::ENTITY_TYPE)
            ->select(['entityType'])
            ->where([
                'userId' => $id,
            ])
            ->find();

        foreach ($autofollowList as $autofollow) {
            $autoFollowEntityTypeList[] = $autofollow->get('entityType');
        }

        $this->data[$id]['autoFollowEntityTypeList'] = $autoFollowEntityTypeList;

        $entity->set('autoFollowEntityTypeList', $autoFollowEntityTypeList);
    }

    protected function storeAutoFollowEntityTypeList(Entity $entity): void
    {
        $id = $entity->getId();

        if (!$entity->isAttributeChanged('autoFollowEntityTypeList')) {
            return;
        }

        $entityTypeList = $entity->get('autoFollowEntityTypeList') ?? [];

        $delete = $this->entityManager
            ->getQueryBuilder()
            ->delete()
            ->from(Autofollow::ENTITY_TYPE)
            ->where([
                'userId' => $id,
            ])
            ->build();

        $this->entityManager->getQueryExecutor()->execute($delete);

        $entityTypeList = array_filter($entityTypeList, function ($item) {
            return (bool) $this->metadata->get(['scopes', $item, 'stream']);
        });

        foreach ($entityTypeList as $entityType) {
            $this->entityManager->createEntity(Autofollow::ENTITY_TYPE, [
                'userId' => $id,
                'entityType' => $entityType,
            ]);
        }
    }

    public function save(Entity $entity, array $options = []): void
    {
        if (!$entity->hasId()) {
            throw new RuntimeException("ID is not set.");
        }

        $this->data[$entity->getId()] = get_object_vars($entity->getValueMap());

        $fields = $this->metadata->get('entityDefs.Preferences.fields');

        $data = [];

        foreach ($this->data[$entity->getId()] as $field => $value) {
            if (empty($fields[$field][FieldParam::NOT_STORABLE])) {
                $data[$field] = $value;
            }
        }

        $dataString = Json::encode($data, \JSON_PRETTY_PRINT);

        $insert = $this->entityManager->getQueryBuilder()
            ->insert()
            ->into(PreferencesEntity::ENTITY_TYPE)
            ->columns([Attribute::ID, 'data'])
            ->values([
                Attribute::ID => $entity->getId(),
                'data' => $dataString,
            ])
            ->updateSet([
                'data' => $dataString,
            ])
            ->build();

        $this->entityManager->getQueryExecutor()->execute($insert);

        /** @var User|null $user */
        $user = $this->entityManager->getEntityById(User::ENTITY_TYPE, $entity->getId());

        if ($user && !$user->isPortal()) {
            $this->storeAutoFollowEntityTypeList($entity);
        }
    }

    public function deleteFromDb(string $id): void
    {
        $delete = $this->entityManager->getQueryBuilder()
            ->delete()
            ->from(PreferencesEntity::ENTITY_TYPE)
            ->where([
                'id' => $id,
            ])
            ->build();

        $this->entityManager->getQueryExecutor()->execute($delete);
    }

    public function remove(Entity $entity, array $options = []): void
    {
        if (!$entity->hasId()) {
            throw new RuntimeException("ID is not set.");
        }

        $this->deleteFromDb($entity->getId());

        if (isset($this->data[$entity->getId()])) {
            unset($this->data[$entity->getId()]);
        }
    }

    public function resetToDefaults(string $userId): ?stdClass
    {
        $this->deleteFromDb($userId);

        if (isset($this->data[$userId])) {
            unset($this->data[$userId]);
        }

        $entity = $this->getById($userId);

        if ($entity) {
            return $entity->getValueMap();
        }

        return null;
    }
}
