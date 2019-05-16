<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

namespace Espo\Core\Webhook;

use Espo\ORM\Entity;

class Manager
{
    protected $config;
    protected $fileManager;
    protected $entityManager;
    protected $fieldManager;

    private $cacheFile = 'data/cache/application/webhooks.php';

    protected $skipAttributeList = ['isFollowed', 'modifiedAt', 'modifiedBy'];

    private $data = null;

    public function __construct(
        \Espo\Core\Utils\Config $config,
        \Espo\Core\Utils\File\Manager $fileManager,
        \Espo\ORM\EntityManager $entityManager,
        \Espo\Core\Utils\FieldManagerUtil $fieldManager
    ) {
        $this->config = $config;
        $this->fileManager = $fileManager;
        $this->entityManager = $entityManager;
        $this->fieldManager = $fieldManager;

        $this->loadData();
    }

    private function loadData()
    {
        if ($this->config->get('useCache')) {
            if ($this->fileManager->isFile($this->cacheFile)) {
                if (file_exists($this->cacheFile)) {
                    $this->data = $this->fileManager->getPhpContents($this->cacheFile);
                }
            }
        }

        if (is_null($this->data)) {
            $this->data = $this->buildData();
        }

        if ($this->config->get('useCache')) {
            $this->storeDataToCache();
        }
    }

    private function storeDataToCache()
    {
        $this->fileManager->putPhpContents($this->cacheFile, $this->data);
    }

    private function buildData()
    {
        $data = [];

        $list = $this->entityManager->getRepository('Webhook')
            ->select(['event'])
            ->groupBy(['event'])
            ->where([
                'isActive' => true,
                'event!=' => null,
            ])
            ->find();

        foreach ($list as $e) {
            $data[$e->get('event')] = true;
        }

        return $data;
    }

    public function addEvent(string $event)
    {
        $this->data[$event] = true;

        if ($this->config->get('useCache')) {
            $this->storeDataToCache();
        }
    }

    public function removeEvent(string $event)
    {
        $notExists = !$this->entityManager->getRepository('Webhook')->select(['id'])->where([
            'event' => $event,
            'isActive' => true,
        ])->findOne();

        if ($notExists) {
            unset($this->data[$event]);
            if ($this->config->get('useCache')) {
                $this->storeDataToCache();
            }
        }
    }

    protected function eventExists(string $event) : bool
    {
        return isset($this->data[$event]);
    }

    protected function logDebugEvent(string $event, Entity $entity)
    {
        $GLOBALS['log']->debug("Webhook: {$event} on record {$entity->id}.");
    }

    public function processCreate(Entity $entity)
    {
        $event = $entity->getEntityType() . '.create';

        if (!$this->eventExists($event)) return;

        $this->entityManager->createEntity('WebhookEventQueueItem', [
            'event' => $event,
            'targetType' => $entity->getEntityType(),
            'targetId' => $entity->id,
            'data' => $entity->getValueMap(),
        ]);

        $this->logDebugEvent($event, $entity);
    }

    public function processDelete(Entity $entity)
    {
        $event = $entity->getEntityType() . '.delete';

        if (!$this->eventExists($event)) return;

        $this->entityManager->createEntity('WebhookEventQueueItem', [
            'event' => $event,
            'targetType' => $entity->getEntityType(),
            'targetId' => $entity->id,
            'data' => (object) [
                'id' => $entity->id,
            ],
        ]);

        $this->logDebugEvent($event, $entity);
    }

    public function processUpdate(Entity $entity)
    {
        $event = $entity->getEntityType() . '.update';

        $data = (object) [];
        foreach ($entity->getAttributeList() as $attribute) {
            if (in_array($attribute, $this->skipAttributeList)) continue;
            if ($entity->isAttributeChanged($attribute)) {
                $data->$attribute = $entity->get($attribute);
            }
        }

        if (!count(get_object_vars($data))) return;

        $data->id = $entity->id;

        if ($this->eventExists($event)) {
            $this->entityManager->createEntity('WebhookEventQueueItem', [
                'event' => $event,
                'targetType' => $entity->getEntityType(),
                'targetId' => $entity->id,
                'data' => $data,
            ]);
            $this->logDebugEvent($event, $entity);
        }

        foreach ($this->fieldManager->getEntityTypeFieldList($entity->getEntityType()) as $field) {
            $itemEvent = $entity->getEntityType() . '.fieldUpdate.' . $field;
            if (!$this->eventExists($itemEvent)) continue;

            $attributeList = $this->fieldManager->getActualAttributeList($entity->getEntityType(), $field);
            $isChanged = false;
            foreach ($attributeList as $attribute) {
                if (in_array($attribute, $this->skipAttributeList)) continue;
                if (property_exists($data, $attribute)) {
                    $isChanged = true;
                    break;
                }
            }

            if ($isChanged) {
                $itemData = (object) [];
                $itemData->id = $entity->id;
                $attributeList = $this->fieldManager->getAttributeList($entity->getEntityType(), $field);
                foreach ($attributeList as $attribute) {
                    if (in_array($attribute, $this->skipAttributeList)) continue;
                    $itemData->$attribute = $entity->get($attribute);
                }

                $this->entityManager->createEntity('WebhookEventQueueItem', [
                    'event' => $itemEvent,
                    'targetType' => $entity->getEntityType(),
                    'targetId' => $entity->id,
                    'data' => $itemData,
                ]);

                $this->logDebugEvent($itemEvent, $entity);
            }
        }
    }
}
