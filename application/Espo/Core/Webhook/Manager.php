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

namespace Espo\Core\Webhook;

use Espo\Core\Name\Field;
use Espo\Core\ORM\Entity;
use Espo\Core\ORM\EntityManager;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\Config\SystemConfig;
use Espo\Core\Utils\DataCache;
use Espo\Core\Utils\FieldUtil;
use Espo\Core\Utils\Log;
use Espo\Entities\Webhook;
use Espo\Entities\WebhookEventQueueItem;

use Espo\ORM\Name\Attribute;
use RuntimeException;
use stdClass;

/**
 * Processes events. Holds an information about existing events.
 */
class Manager
{
    private string $cacheKey = 'webhooks';

    /** @var string[] */
    protected $skipAttributeList = [
        Field::IS_FOLLOWED,
        Field::IS_STARRED,
        Field::FOLLOWERS . 'Ids',
        Field::FOLLOWERS . 'Names',
        Field::MODIFIED_AT,
        Field::MODIFIED_BY,
        Field::STREAM_UPDATED_AT,
        Field::VERSION_NUMBER,
    ];

    /** @var ?array<string, bool> */
    private $data = null;

    public function __construct(
        private Config $config,
        private DataCache $dataCache,
        private EntityManager $entityManager,
        private FieldUtil $fieldUtil,
        private Log $log,
        private SystemConfig $systemConfig,
    ) {
        $this->loadData();
    }

    private function loadData(): void
    {
        if ($this->systemConfig->useCache() && $this->dataCache->has($this->cacheKey)) {
            /** @var array<string, bool> $data */
            $data = $this->dataCache->get($this->cacheKey);

            $this->data = $data;
        }

        if (is_null($this->data)) {
            $this->data = $this->buildData();

            if ($this->systemConfig->useCache()) {
                $this->storeDataToCache();
            }
        }
    }

    private function storeDataToCache(): void
    {
        if ($this->data === null) {
            throw new RuntimeException("No data to store.");
        }

        $this->dataCache->store($this->cacheKey, $this->data);
    }

    /**
     * @return array<string, bool>
     */
    private function buildData(): array
    {
        $data = [];

        $list = $this->entityManager
            ->getRDBRepositoryByClass(Webhook::class)
            ->select(['event'])
            ->group(['event'])
            ->where([
                'isActive' => true,
                'event!=' => null,
            ])
            ->find();

        foreach ($list as $webhook) {
            $event = $webhook->getEvent();

            $data[$event] = true;
        }

        return $data;
    }

    /**
     * Add an event. To cache the information that at least one webhook for this event exists.
     */
    public function addEvent(string $event): void
    {
        $this->data[$event] = true;

        if ($this->systemConfig->useCache()) {
            $this->storeDataToCache();
        }
    }

    /**
     * Remove an event. If no webhooks with this event left, then it will be removed from the cache.
     */
    public function removeEvent(string $event): void
    {
        $one = !$this->entityManager
            ->getRDBRepositoryByClass(Webhook::class)
            ->select([Attribute::ID])
            ->where([
                'event' => $event,
                'isActive' => true,
            ])
            ->findOne();

        if (!$one) {
            return;
        }

        unset($this->data[$event]);

        if ($this->systemConfig->useCache()) {
            $this->storeDataToCache();
        }
    }

    private function eventExists(string $event): bool
    {
        return isset($this->data[$event]);
    }

    private function logDebugEvent(string $event, Entity $entity): void
    {
        $this->log->debug("Webhook: {event} on record {id}.", [
            'id' => $entity->getId(),
            'event' => $event,
        ]);
    }

    /**
     * Process 'create' event.
     */
    public function processCreate(Entity $entity, Options $options): void
    {
        $event = "{$entity->getEntityType()}.create";

        if (!$this->eventExists($event)) {
            return;
        }

        $item = $this->entityManager->getRDBRepositoryByClass(WebhookEventQueueItem::class)->getNew();

        $item
            ->setEvent($event)
            ->setTarget($entity)
            ->setData($entity->getValueMap())
            ->setUserId($options->userId);

        $this->entityManager->saveEntity($item);

        $this->logDebugEvent($event, $entity);
    }

    /**
     * Process 'delete' event.
     */
    public function processDelete(Entity $entity, Options $options): void
    {
        $event = "{$entity->getEntityType()}.delete";

        if (!$this->eventExists($event)) {
            return;
        }

        $item = $this->entityManager->getRDBRepositoryByClass(WebhookEventQueueItem::class)->getNew();

        $item
            ->setEvent($event)
            ->setTarget($entity)
            ->setData(['id' => $entity->getId()])
            ->setUserId($options->userId);

        $this->entityManager->saveEntity($item);

        $this->logDebugEvent($event, $entity);
    }

    /**
     * Process 'update' event.
     */
    public function processUpdate(Entity $entity, Options $options): void
    {
        $event = "{$entity->getEntityType()}.update";

        $data = (object) [];

        foreach ($entity->getAttributeList() as $attribute) {
            if (in_array($attribute, $this->skipAttributeList)) {
                continue;
            }

            if ($entity->isAttributeChanged($attribute)) {
                $data->$attribute = $entity->get($attribute);
            }
        }

        if (!count(get_object_vars($data))) {
            return;
        }

        $data->id = $entity->getId();

        if ($this->eventExists($event)) {
            $item = $this->entityManager->getRDBRepositoryByClass(WebhookEventQueueItem::class)->getNew();

            $item
                ->setEvent($event)
                ->setTarget($entity)
                ->setData($data)
                ->setUserId($options->userId);

            $this->entityManager->saveEntity($item);

            $this->logDebugEvent($event, $entity);
        }

        $this->processUpdateFields($entity, $data, $options);
    }

    private function processUpdateFields(Entity $entity, stdClass $data, Options $options): void
    {
        foreach ($this->fieldUtil->getEntityTypeFieldList($entity->getEntityType()) as $field) {
            $itemEvent = "{$entity->getEntityType()}.fieldUpdate.$field";

            if (
                !$this->eventExists($itemEvent) ||
                !$this->isFieldChanged($entity, $field, $data)
            ) {
                continue;
            }

            $this->processUpdateField($entity, $field, $itemEvent, $options);
        }
    }

    private function isFieldChanged(Entity $entity, string $field, stdClass $data): bool
    {
        $attributes = $this->fieldUtil->getActualAttributeList($entity->getEntityType(), $field);

        $isChanged = false;

        foreach ($attributes as $attribute) {
            if (in_array($attribute, $this->skipAttributeList)) {
                continue;
            }

            if (property_exists($data, $attribute)) {
                $isChanged = true;

                break;
            }
        }

        return $isChanged;
    }

    private function processUpdateField(Entity $entity, string $field, string $itemEvent, Options $options): void
    {
        $itemData = (object) [];

        $itemData->id = $entity->getId();

        $attributeList = $this->fieldUtil->getAttributeList($entity->getEntityType(), $field);

        foreach ($attributeList as $attribute) {
            if (in_array($attribute, $this->skipAttributeList)) {
                continue;
            }

            $itemData->$attribute = $entity->get($attribute);
        }

        $item = $this->entityManager->getRDBRepositoryByClass(WebhookEventQueueItem::class)->getNew();

        $item
            ->setEvent($itemEvent)
            ->setTarget($entity)
            ->setData($itemData)
            ->setUserId($options->userId);

        $this->entityManager->saveEntity($item);

        $this->logDebugEvent($itemEvent, $entity);
    }
}
