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

namespace Espo\Core\Repositories;

use Espo\ORM\{
    Repository\RDBRepository,
    Entity,
};

use Espo\Core\ORM\{
    EntityManager,
    EntityFactory,
    Repository\HookMediator,
};

use Espo\Core\{
    Utils\Metadata,
    Utils\Util,
    HookManager,
    ApplicationState,
};

class Database extends RDBRepository
{
    protected $hooksDisabled = false;

    protected $processFieldsAfterSaveDisabled = false;

    protected $processFieldsAfterRemoveDisabled = false;

    private $restoreData = null;

    protected $metadata;

    protected $hookManager;

    protected $applicationState;

    public function __construct(
        string $entityType,
        EntityManager $entityManager,
        EntityFactory $entityFactory,
        Metadata $metadata,
        HookManager $hookManager,
        ApplicationState $applicationState
    ) {
        $this->metadata = $metadata;
        $this->hookManager = $hookManager;
        $this->applicationState = $applicationState;

        $hookMediator = null;

        if (!$this->hooksDisabled) {
            $hookMediator = new HookMediator($hookManager);
        }

        parent::__construct($entityType, $entityManager, $entityFactory, $hookMediator);
    }

    protected function getMetadata()
    {
        return $this->metadata;
    }

    /**
     * @deprecated Will be removed.
     */
    public function handleSelectParams(&$params)
    {
    }

    protected function beforeRemove(Entity $entity, array $options = [])
    {
        parent::beforeRemove($entity, $options);

        if (!$this->hooksDisabled && empty($options['skipHooks'])) {
            $this->hookManager->process($this->entityType, 'beforeRemove', $entity, $options);
        }

        $nowString = date('Y-m-d H:i:s', time());

        if ($entity->hasAttribute('modifiedAt')) {
            $entity->set('modifiedAt', $nowString);
        }

        if ($entity->hasAttribute('modifiedById')) {
            if ($this->applicationState->hasUser()) {
                $entity->set('modifiedById', $this->applicationState->getUser()->id);
            }
        }
    }

    protected function afterRemove(Entity $entity, array $options = [])
    {
        parent::afterRemove($entity, $options);

        if (!$this->processFieldsAfterRemoveDisabled) {

        }

        if (!$this->hooksDisabled && empty($options['skipHooks'])) {
            $this->hookManager->process($this->entityType, 'afterRemove', $entity, $options);
        }
    }

    protected function afterMassRelate(Entity $entity, $relationName, array $params = [], array $options = [])
    {
        if (!$this->hooksDisabled && empty($options['skipHooks'])) {
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
    }

    protected function afterRelate(Entity $entity, $relationName, $foreign, $data = null, array $options = [])
    {
        parent::afterRelate($entity, $relationName, $foreign, $data, $options);

        if (!$this->hooksDisabled && empty($options['skipHooks'])) {
            if (is_string($foreign)) {
                $foreignId = $foreign;

                $foreignEntityType = $entity->getRelationParam($relationName, 'entity');

                if ($foreignEntityType) {
                    $foreign = $this->getEntityManager()->getEntity($foreignEntityType);

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
    }

    protected function afterUnrelate(Entity $entity, $relationName, $foreign, array $options = [])
    {
        parent::afterUnrelate($entity, $relationName, $foreign, $options);

        if (!$this->hooksDisabled && empty($options['skipHooks'])) {
            if (is_string($foreign)) {
                $foreignId = $foreign;

                $foreignEntityType = $entity->getRelationParam($relationName, 'entity');

                if ($foreignEntityType) {
                    $foreign = $this->getEntityManager()->getEntity($foreignEntityType);
                    $foreign->id = $foreignId;
                    $foreign->setAsFetched();
                }
            }

            if ($foreign instanceof Entity) {
                $this->hookMediator->afterUnrelate($entity, $relationName, $foreign, $options);
            }
        }
    }

    protected function beforeSave(Entity $entity, array $options = [])
    {
        parent::beforeSave($entity, $options);

        if (!$this->hooksDisabled && empty($options['skipHooks'])) {
            $this->hookManager->process($this->entityType, 'beforeSave', $entity, $options);
        }
    }

    protected function afterSave(Entity $entity, array $options = [])
    {
        if (!empty($this->restoreData)) {
            $entity->set($this->restoreData);

            $this->restoreData = null;
        }

        parent::afterSave($entity, $options);

        if (!$this->processFieldsAfterSaveDisabled) {
            $this->processFileFieldsSave($entity);
            $this->processArrayFieldsSave($entity);
            $this->processWysiwygFieldsSave($entity);
        }

        if (!$this->hooksDisabled && empty($options['skipHooks'])) {
            $this->hookManager->process($this->entityType, 'afterSave', $entity, $options);
        }
    }

    public function save(Entity $entity, array $options = []): void
    {
        $nowString = date('Y-m-d H:i:s', time());

        $restoreData = [];

        if (
            $entity->isNew() &&
            !$entity->has('id') &&
            !$entity->getAttributeParam('id', 'autoincrement')
        ) {
            $entity->set('id', Util::generateId());
        }

        if (empty($options['skipAll'])) {
            if ($entity->isNew()) {
                if ($entity->hasAttribute('createdAt')) {
                    if (empty($options['import']) || !$entity->has('createdAt')) {
                        $entity->set('createdAt', $nowString);
                    }
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
                        (empty($options['import']) || !$entity->has('createdById'))
                    ) {
                        if ($this->applicationState->hasUser()) {
                            $entity->set('createdById', $this->applicationState->getUser()->id);
                        }
                    }
                }
            }
            else {
                if (empty($options['silent']) && empty($options['skipModifiedBy'])) {
                    if ($entity->hasAttribute('modifiedAt')) {
                        $entity->set('modifiedAt', $nowString);
                    }

                    if ($entity->hasAttribute('modifiedById')) {
                        if (!empty($options['modifiedById'])) {
                            $entity->set('modifiedById', $options['modifiedById']);
                        }
                        else if ($this->applicationState->hasUser()) {
                            $entity->set('modifiedById', $this->applicationState->getUser()->id);
                            $entity->set('modifiedByName', $this->applicationState->getUser()->get('name'));
                        }
                    }
                }
            }
        }

        $this->restoreData = $restoreData;

        parent::save($entity, $options);
    }

    protected function processFileFieldsSave(Entity $entity)
    {
        $entityDefs = $this->entityManager
            ->getDefs()
            ->getEntity($entity->getEntityType());

        foreach ($entity->getRelationList() as $name) {
            $defs = $entityDefs->getRelation($name);

            $type = $defs->getType();

            if (!$defs->hasForeignEntityType()) {
                continue;
            }

            $foreignEntityType = $defs->getForeignEntityType();

            if (!($type === $entity::BELONGS_TO && $foreignEntityType === 'Attachment')) {
                continue;
            }

            $attribute = $name . 'Id';

            if (!$entity->hasAttribute($attribute)) {
                continue;
            }

            if (!$entity->get($attribute)) {
                continue;
            }

            if (!$entity->isAttributeChanged($attribute)) {
                continue;
            }

            $attachment = $this->getEntityManager()->getEntity('Attachment', $entity->get($attribute));

            if (!$attachment) {
                continue;
            }

            $attachment->set([
                'relatedId' => $entity->id,
                'relatedType' => $entity->getEntityType(),
            ]);

            $this->getEntityManager()->saveEntity($attachment);
        }

        if ($entity->isNew()) {
            return;
        }

        foreach ($this->getMetadata()->get(['entityDefs', $entity->getEntityType(), 'fields']) as $name => $defs) {
            if (!empty($defs['type']) && in_array($defs['type'], ['file', 'image'])) {
                $attribute = $name . 'Id';

                if ($entity->isAttributeChanged($attribute)) {
                    $previousAttachmentId = $entity->getFetched($attribute);

                    if ($previousAttachmentId) {
                        $attachment = $this->getEntityManager()
                            ->getEntity('Attachment', $previousAttachmentId);

                        if ($attachment) {
                            $this->getEntityManager()->removeEntity($attachment);
                        }
                    }
                }
            }
        }
    }

    protected function processArrayFieldsSave(Entity $entity)
    {
        foreach ($entity->getAttributeList() as $attribute) {
            $type = $entity->getAttributeType($attribute);

            if ($type !== Entity::JSON_ARRAY) {
                continue;
            }

            if (!$entity->has($attribute)) {
                continue;
            }

            if (!$entity->isAttributeChanged($attribute)) {
                continue;
            }

            if (!$entity->getAttributeParam($attribute, 'storeArrayValues')) {
                continue;
            }

            if ($entity->getAttributeParam($attribute, 'notStorable')) {
                continue;
            }

            $this->getEntityManager()
                ->getRepository('ArrayValue')
                ->storeEntityAttribute($entity, $attribute);
        }
    }

    protected function processWysiwygFieldsSave(Entity $entity)
    {
        if (!$entity->isNew()) {
            return;
        }

        $fieldsDefs = $this->getMetadata()->get(['entityDefs', $entity->getEntityType(), 'fields'], []);

        foreach ($fieldsDefs as $field => $defs) {
            if (!empty($defs['type']) && $defs['type'] === 'wysiwyg') {
                $content = $entity->get($field);

                if (!$content) {
                    continue;
                }

                if (preg_match_all("/\?entryPoint=attachment&amp;id=([^&=\"']+)/", $content, $matches)) {
                    if (!empty($matches[1]) && is_array($matches[1])) {
                        foreach ($matches[1] as $id) {
                            $attachment = $this->getEntityManager()->getEntity('Attachment', $id);

                            if ($attachment) {
                                if (!$attachment->get('relatedId') && !$attachment->get('sourceId')) {
                                    $attachment->set([
                                        'relatedId' => $entity->id,
                                        'relatedType' => $entity->getEntityType()
                                    ]);

                                    $this->getEntityManager()->saveEntity($attachment);
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}
