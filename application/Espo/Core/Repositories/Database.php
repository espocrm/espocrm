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

namespace Espo\Core\Repositories;

use Espo\Core\ORM\Repository\Option\SaveOption;
use Espo\Core\Utils\SystemUser;
use Espo\ORM\BaseEntity;
use Espo\ORM\Entity;
use Espo\ORM\Repository\RDBRepository;
use Espo\Core\ORM\EntityFactory;
use Espo\Core\ORM\EntityManager;
use Espo\Core\ORM\Repository\HookMediator;
use Espo\Core\ApplicationState;
use Espo\Core\HookManager;
use Espo\Core\Utils\DateTime as DateTimeUtil;
use Espo\Core\Utils\Id\RecordIdGenerator;
use Espo\Core\Utils\Metadata;

/**
 * A database repository. Extending is not recommended. Use hooks, field saver framework instead.
 *
 * @template TEntity of Entity
 * @extends RDBRepository<TEntity>
 */
class Database extends RDBRepository
{
    private const ATTR_ID = 'id';
    private const ATTR_CREATED_BY_ID = 'createdById';
    private const ATTR_MODIFIED_BY_ID = 'modifiedById';
    private const ATTR_MODIFIED_BY_NAME = 'modifiedByName';
    private const ATTR_CREATED_AT = 'createdAt';
    private const ATTR_MODIFIED_AT = 'modifiedAt';

    /**
     * Disables hook processing.
     * @var bool
     */
    protected $hooksDisabled = false;

    /** @var ?array<string, mixed> */
    private $restoreData = null;
    /** @var Metadata */
    protected $metadata;
    /** @var HookManager */
    protected $hookManager;
    /** @var ApplicationState  */
    protected $applicationState;
    /** @var RecordIdGenerator */
    protected $recordIdGenerator;

    public function __construct(
        string $entityType,
        EntityManager $entityManager,
        EntityFactory $entityFactory,
        Metadata $metadata,
        HookManager $hookManager,
        ApplicationState $applicationState,
        RecordIdGenerator $recordIdGenerator,
        private SystemUser $systemUser
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
    {}

    /**
     * @param TEntity $entity
     * @param array<string, mixed> $options
     */
    public function save(Entity $entity, array $options = []): void
    {
        if (
            $entity->isNew() &&
            !$entity->has(self::ATTR_ID) &&
            !$this->getAttributeParam($entity, self::ATTR_ID, 'autoincrement')
        ) {
            $entity->set(self::ATTR_ID, $this->recordIdGenerator->generate());
        }

        if (empty($options[SaveOption::SKIP_ALL])) {
            $this->processCreatedAndModifiedFieldsSave($entity, $options);
        }

        $this->restoreData = [];

        parent::save($entity, $options);
    }

    /**
     * @param TEntity $entity
     * @param array<string, mixed> $options
     * @return void
     */
    protected function beforeRemove(Entity $entity, array $options = [])
    {
        parent::beforeRemove($entity, $options);

        if (!$this->hooksDisabled && empty($options[SaveOption::SKIP_HOOKS])) {
            $this->hookManager->process($this->entityType, 'beforeRemove', $entity, $options);
        }

        $nowString = DateTimeUtil::getSystemNowString();

        if ($entity->hasAttribute(self::ATTR_MODIFIED_AT)) {
            $entity->set(self::ATTR_MODIFIED_AT, $nowString);
        }

        if ($entity->hasAttribute(self::ATTR_MODIFIED_BY_ID)) {
            $modifiedById = $options[SaveOption::MODIFIED_BY_ID] ?? null;

            if ($modifiedById === SystemUser::NAME) {
                // For bc.
                $modifiedById = $this->systemUser->getId();
            }

            if (!$modifiedById && $this->applicationState->hasUser()) {
                $modifiedById = $this->applicationState->getUser()->getId();
            }

            if ($modifiedById) {
                $entity->set(self::ATTR_MODIFIED_BY_ID, $modifiedById);
            }
        }
    }

    /**
     * @param TEntity $entity
     * @param array<string, mixed> $options
     * @return void
     */
    protected function afterRemove(Entity $entity, array $options = [])
    {
        parent::afterRemove($entity, $options);

        if (!$this->hooksDisabled && empty($options[SaveOption::SKIP_HOOKS])) {
            $this->hookManager->process($this->entityType, 'afterRemove', $entity, $options);
        }
    }

    /**
     * @param TEntity $entity
     * @param string $relationName
     * @param array<string, mixed> $params
     * @param array<string, mixed> $options
     * @return void
     */
    protected function afterMassRelate(Entity $entity, $relationName, array $params = [], array $options = [])
    {
        if ($this->hooksDisabled || !empty($options[SaveOption::SKIP_HOOKS])) {
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
     * @param \stdClass|array<string, mixed>|null $data
     * @param array<string, mixed> $options
     * @return void
     */
    protected function afterRelate(Entity $entity, $relationName, $foreign, $data = null, array $options = [])
    {
        parent::afterRelate($entity, $relationName, $foreign, $data, $options);

        if ($this->hooksDisabled || !empty($options[SaveOption::SKIP_HOOKS])) {
            return;
        }

        if (is_string($foreign)) {
            $foreignId = $foreign;

            $foreignEntityType = $this->getRelationParam($entity, $relationName, 'entity');

            if ($foreignEntityType) {
                $foreign = $this->entityManager->getNewEntity($foreignEntityType);

                $foreign->set(self::ATTR_ID, $foreignId);
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
     * @param array<string, mixed> $options
     * @return void
     */
    protected function afterUnrelate(Entity $entity, $relationName, $foreign, array $options = [])
    {
        parent::afterUnrelate($entity, $relationName, $foreign, $options);

        if ($this->hooksDisabled || !empty($options[SaveOption::SKIP_HOOKS])) {
            return;
        }

        if (is_string($foreign)) {
            $foreignId = $foreign;

            $foreignEntityType = $this->getRelationParam($entity, $relationName, 'entity');

            if ($foreignEntityType) {
                $foreign = $this->entityManager->getNewEntity($foreignEntityType);

                $foreign->set(self::ATTR_ID, $foreignId);
                $foreign->setAsFetched();
            }
        }

        if ($foreign instanceof Entity) {
            $this->hookMediator->afterUnrelate($entity, $relationName, $foreign, $options);
        }
    }

    /**
     * @param TEntity $entity
     * @param array<string, mixed> $options
     * @return void
     */
    protected function beforeSave(Entity $entity, array $options = [])
    {
        parent::beforeSave($entity, $options);

        if (!$this->hooksDisabled && empty($options[SaveOption::SKIP_HOOKS])) {
            $this->hookManager->process($this->entityType, 'beforeSave', $entity, $options);
        }
    }

    /**
     * @param TEntity $entity
     * @param array<string, mixed> $options
     * @return void
     */
    protected function afterSave(Entity $entity, array $options = [])
    {
        if (!empty($this->restoreData)) {
            $entity->set($this->restoreData);

            $this->restoreData = null;
        }

        parent::afterSave($entity, $options);

        if (!$this->hooksDisabled && empty($options[SaveOption::SKIP_HOOKS])) {
            $this->hookManager->process($this->entityType, 'afterSave', $entity, $options);
        }
    }

    /**
     * @param TEntity $entity
     * @param array<string, mixed> $options
     */
    private function processCreatedAndModifiedFieldsSave(Entity $entity, array $options): void
    {
        if ($entity->isNew()) {
            $this->processCreatedAndModifiedFieldsSaveNew($entity, $options);

            return;
        }

        $nowString = DateTimeUtil::getSystemNowString();

        if (
            !empty($options[SaveOption::SILENT]) ||
            !empty($options[SaveOption::SKIP_MODIFIED_BY])
        ) {
            return;
        }

        $isChanged = false;

        foreach ($entity->getAttributeList() as $attribute) {
            if ($entity->isAttributeChanged($attribute)) {
                $isChanged = true;

                break;
            }
        }

        if (!$isChanged && empty($options[SaveOption::MODIFIED_BY_ID])) {
            return;
        }

        if ($entity->hasAttribute(self::ATTR_MODIFIED_AT)) {
            $entity->set(self::ATTR_MODIFIED_AT, $nowString);
        }

        if ($entity->hasAttribute(self::ATTR_MODIFIED_BY_ID)) {
            $modifiedById = $options[SaveOption::MODIFIED_BY_ID] ?? null;

            if ($modifiedById === SystemUser::NAME) {
                // For bc.
                $modifiedById = $this->systemUser->getId();
            }

            if ($modifiedById) {
                $entity->set(self::ATTR_MODIFIED_BY_ID, $modifiedById);
            }
            else if ($this->applicationState->hasUser()) {
                $user = $this->applicationState->getUser();

                $entity->set(self::ATTR_MODIFIED_BY_ID, $user->getId());
                $entity->set(self::ATTR_MODIFIED_BY_NAME, $user->getName());
            }
        }
    }

    /**
     * @param TEntity $entity
     * @param array<string, mixed> $options
     */
    private function processCreatedAndModifiedFieldsSaveNew(Entity $entity, array $options): void
    {
        $nowString = DateTimeUtil::getSystemNowString();

        if (
            $entity->hasAttribute(self::ATTR_CREATED_AT) &&
            (empty($options[SaveOption::IMPORT]) || !$entity->has(self::ATTR_CREATED_AT))
        ) {
            $entity->set(self::ATTR_CREATED_AT, $nowString);
        }

        if ($entity->hasAttribute(self::ATTR_MODIFIED_AT)) {
            $entity->set(self::ATTR_MODIFIED_AT, $nowString);
        }

        if ($entity->hasAttribute(self::ATTR_CREATED_BY_ID)) {
            $createdById = $options[SaveOption::CREATED_BY_ID] ?? null;

            if ($createdById) {
                if ($createdById === SystemUser::NAME) {
                    // For bc.
                    $createdById = $this->systemUser->getId();
                }

                $entity->set(self::ATTR_CREATED_BY_ID, $createdById);
            }
            else if (
                empty($options[SaveOption::SKIP_CREATED_BY]) &&
                (empty($options[SaveOption::IMPORT]) || !$entity->has(self::ATTR_CREATED_BY_ID)) &&
                $this->applicationState->hasUser()
            ) {
                $entity->set(self::ATTR_CREATED_BY_ID, $this->applicationState->getUser()->getId());
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
