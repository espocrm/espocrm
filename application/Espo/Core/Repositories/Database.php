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

namespace Espo\Core\Repositories;

use Espo\ORM\{
    Repository\RDBRepository,
    Entity,
    BaseEntity,
};

use Espo\Core\ORM\{
    EntityManager,
    EntityFactory,
    Repository\HookMediator,
};

use Espo\Core\{
    Utils\Metadata,
    HookManager,
    ApplicationState,
    Utils\DateTime as DateTimeUtil,
    Utils\Id\RecordIdGenerator,
};

/**
 * @template TEntity of \Espo\ORM\Entity
 * @extends RDBRepository<TEntity>
 */
class Database extends RDBRepository
{
    /**
     * @var bool
     */
    protected $hooksDisabled = false;

    /**
     * @deprecated
     * @todo Remove all usage.
     * @var bool
     */
    protected $processFieldsAfterSaveDisabled = false;

    /**
     * @deprecated
     * @todo Remove all usage.
     * @var bool
     */
    protected $processFieldsAfterRemoveDisabled = false;

    /**
     * @var ?array<string,mixed>
     */
    private $restoreData = null;

    /**
     * @var Metadata
     */
    protected $metadata;

    /**
     * @var HookManager
     */
    protected $hookManager;

    /**
     * @var ApplicationState
     */
    protected $applicationState;

    /**
     * @var RecordIdGenerator
     */
    protected $recordIdGenerator;

    public function __construct(
        string $entityType,
        EntityManager $entityManager,
        EntityFactory $entityFactory,
        Metadata $metadata,
        HookManager $hookManager,
        ApplicationState $applicationState,
        RecordIdGenerator $recordIdGenerator
    ) {
        $this->metadata = $metadata;
        $this->hookManager = $hookManager;
        $this->applicationState = $applicationState;
        $this->recordIdGenerator = $recordIdGenerator;

        $hookMediator = null;

        if (!$this->hooksDisabled) {
            $hookMediator = new HookMediator($hookManager);
        }

        parent::__construct($entityType, $entityManager, $entityFactory, $hookMediator);
    }

    /**
     * @deprecated Use `$this->metadata`.
     */
    protected function getMetadata() /** @phpstan-ignore-line */
    {
        return $this->metadata;
    }

    /**
     * @deprecated Will be removed.
     */
    public function handleSelectParams(&$params) /** @phpstan-ignore-line */
    {
    }

    /**
     * @param TEntity $entity
     * @param array<string,mixed> $options
     */
    public function save(Entity $entity, array $options = []): void
    {
        if (
            $entity->isNew() &&
            !$entity->has('id') &&
            !$this->getAttributeParam($entity, 'id', 'autoincrement')
        ) {
            $entity->set('id', $this->recordIdGenerator->generate());
        }

        if (empty($options['skipAll'])) {
            $this->processCreatedAndModifiedFieldsSave($entity, $options);
        }

        $this->restoreData = [];

        parent::save($entity, $options);
    }

    /**
     * @param TEntity $entity
     * @param array<string,mixed> $options
     * @return void
     */
    protected function beforeRemove(Entity $entity, array $options = [])
    {
        parent::beforeRemove($entity, $options);

        if (!$this->hooksDisabled && empty($options['skipHooks'])) {
            $this->hookManager->process($this->entityType, 'beforeRemove', $entity, $options);
        }

        $nowString = DateTimeUtil::getSystemNowString();

        if ($entity->hasAttribute('modifiedAt')) {
            $entity->set('modifiedAt', $nowString);
        }

        if ($entity->hasAttribute('modifiedById')) {
            $modifiedById = $options['modifiedById'] ?? null;

            if (!$modifiedById && $this->applicationState->hasUser()) {
                $modifiedById = $this->applicationState->getUser()->getId();
            }

            if ($modifiedById) {
                $entity->set('modifiedById', $modifiedById);
            }
        }
    }

    /**
     * @param TEntity $entity
     * @param array<string,mixed> $options
     * @return void
     */
    protected function afterRemove(Entity $entity, array $options = [])
    {
        parent::afterRemove($entity, $options);

        if (!$this->hooksDisabled && empty($options['skipHooks'])) {
            $this->hookManager->process($this->entityType, 'afterRemove', $entity, $options);
        }
    }

    /**
     * @param TEntity $entity
     * @param string $relationName
     * @param array<string,mixed> $params
     * @param array<string,mixed> $options
     * @return void
     */
    protected function afterMassRelate(Entity $entity, $relationName, array $params = [], array $options = [])
    {
        if ($this->hooksDisabled || !empty($options['skipHooks'])) {
            return;
        }

        $hookData = [
            'relationName' => $relationName,
            'relationParams' => $params,
        ];

        $this->hookManager->process(
            $this->entityType,
            'afterMassRelate',
            $entity,
            $options,
            $hookData
        );
    }

    /**
     * @param TEntity $entity
     * @param string $relationName
     * @param Entity|string $foreign
     * @param \stdClass|array<string,mixed>|null $data
     * @param array<string,mixed> $options
     * @return void
     */
    protected function afterRelate(Entity $entity, $relationName, $foreign, $data = null, array $options = [])
    {
        parent::afterRelate($entity, $relationName, $foreign, $data, $options);

        if ($this->hooksDisabled || !empty($options['skipHooks'])) {
            return;
        }

        if (is_string($foreign)) {
            $foreignId = $foreign;

            $foreignEntityType = $this->getRelationParam($entity, $relationName, 'entity');

            if ($foreignEntityType) {
                $foreign = $this->entityManager->getNewEntity($foreignEntityType);

                $foreign->set('id', $foreignId);
                $foreign->setAsFetched();
            }
        }

        if ($foreign instanceof Entity) {
            if (is_object($data)) {
                $data = (array) $data;
            }

            $this->hookMediator->afterRelate($entity, $relationName, $foreign, $data, $options);
        }
    }

    /**
     * @param TEntity $entity
     * @param string $relationName
     * @param Entity|string $foreign
     * @param array<string,mixed> $options
     * @return void
     */
    protected function afterUnrelate(Entity $entity, $relationName, $foreign, array $options = [])
    {
        parent::afterUnrelate($entity, $relationName, $foreign, $options);

        if ($this->hooksDisabled || !empty($options['skipHooks'])) {
            return;
        }

        if (is_string($foreign)) {
            $foreignId = $foreign;

            $foreignEntityType = $this->getRelationParam($entity, $relationName, 'entity');

            if ($foreignEntityType) {
                $foreign = $this->entityManager->getNewEntity($foreignEntityType);

                $foreign->set('id', $foreignId);
                $foreign->setAsFetched();
            }
        }

        if ($foreign instanceof Entity) {
            $this->hookMediator->afterUnrelate($entity, $relationName, $foreign, $options);
        }
    }

    /**
     * @param TEntity $entity
     * @param array<string,mixed> $options
     * @return void
     */
    protected function beforeSave(Entity $entity, array $options = [])
    {
        parent::beforeSave($entity, $options);

        if (!$this->hooksDisabled && empty($options['skipHooks'])) {
            $this->hookManager->process($this->entityType, 'beforeSave', $entity, $options);
        }
    }

    /**
     * @param TEntity $entity
     * @param array<string,mixed> $options
     * @return void
     */
    protected function afterSave(Entity $entity, array $options = [])
    {
        if (!empty($this->restoreData)) {
            $entity->set($this->restoreData);

            $this->restoreData = null;
        }

        parent::afterSave($entity, $options);

        if (!$this->hooksDisabled && empty($options['skipHooks'])) {
            $this->hookManager->process($this->entityType, 'afterSave', $entity, $options);
        }
    }

    /**
     * @param TEntity $entity
     * @param array<string,mixed> $options
     */
    private function processCreatedAndModifiedFieldsSave(Entity $entity, array $options): void
    {
        if ($entity->isNew()) {
            $this->processCreatedAndModifiedFieldsSaveNew($entity, $options);

            return;
        }

        $nowString = DateTimeUtil::getSystemNowString();

        if (!empty($options['silent']) || !empty($options['skipModifiedBy'])) {
            return;
        }

        if ($entity->hasAttribute('modifiedAt')) {
            $entity->set('modifiedAt', $nowString);
        }

        if ($entity->hasAttribute('modifiedById')) {
            if (!empty($options['modifiedById'])) {
                $entity->set('modifiedById', $options['modifiedById']);
            }
            else if ($this->applicationState->hasUser()) {
                $entity->set('modifiedById', $this->applicationState->getUser()->getId());
                $entity->set('modifiedByName', $this->applicationState->getUser()->get('name'));
            }
        }
    }

    /**
     * @param TEntity $entity
     * @param array<string,mixed> $options
     */
    private function processCreatedAndModifiedFieldsSaveNew(Entity $entity, array $options): void
    {
        $nowString = DateTimeUtil::getSystemNowString();

        if (
            $entity->hasAttribute('createdAt') &&
            (empty($options['import']) || !$entity->has('createdAt'))
        ) {
            $entity->set('createdAt', $nowString);
        }

        if ($entity->hasAttribute('modifiedAt')) {
            $entity->set('modifiedAt', $nowString);
        }

        if ($entity->hasAttribute('createdById')) {
            if (!empty($options['createdById'])) {
                $entity->set('createdById', $options['createdById']);
            }
            else if (
                empty($options['skipCreatedBy']) &&
                (empty($options['import']) || !$entity->has('createdById')) &&
                $this->applicationState->hasUser()
            ) {
                $entity->set('createdById', $this->applicationState->getUser()->getId());
            }
        }
    }

    /**
     * @pparam TEntity $entity
     * @return mixed
     */
    private function getAttributeParam(Entity $entity, string $attribute, string $param)
    {
        if ($entity instanceof BaseEntity) {
            return $entity->getAttributeParam($attribute, $param);
        }

        $entityDefs = $this->entityManager
            ->getDefs()
            ->getEntity($entity->getEntityType());

        if (!$entityDefs->hasAttribute($attribute)) {
            return null;
        }

        return $entityDefs->getAttribute($attribute)->getParam($param);
    }

    /**
     * @param TEntity $entity
     * @return mixed
     */
    private function getRelationParam(Entity $entity, string $relation, string $param)
    {
        if ($entity instanceof BaseEntity) {
            return $entity->getRelationParam($relation, $param);
        }

        $entityDefs = $this->entityManager
            ->getDefs()
            ->getEntity($entity->getEntityType());

        if (!$entityDefs->hasRelation($relation)) {
            return null;
        }

        return $entityDefs->getRelation($relation)->getParam($param);
    }
}
