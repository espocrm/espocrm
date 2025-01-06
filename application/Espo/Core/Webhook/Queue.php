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

use Espo\Core\Field\DateTime;
use Espo\Core\Name\Field;
use Espo\Entities\User;
use Espo\Entities\Webhook;
use Espo\Entities\WebhookEventQueueItem;
use Espo\Entities\WebhookQueueItem;
use Espo\Core\AclManager;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\DateTime as DateTimeUtil;
use Espo\Core\Utils\Log;
use Espo\ORM\EntityManager;
use Espo\ORM\Name\Attribute;
use Espo\ORM\Query\Part\Condition as Cond;
use Espo\ORM\Query\SelectBuilder;
use Exception;
use stdClass;

/**
 * Groups occurred events into portions and sends them. A portion contains
 * multiple events of the same webhook.
 */
class Queue
{
    private const EVENT_PORTION_SIZE = 20;
    private const PORTION_SIZE = 20;
    private const BATCH_SIZE = 50;
    private const MAX_ATTEMPT_NUMBER = 4;
    private const FAIL_ATTEMPT_PERIOD = '10 minutes';

    public function __construct(
        private Sender $sender,
        private Config $config,
        private EntityManager $entityManager,
        private AclManager $aclManager,
        private Log $log
    ) {}

    public function process(): void
    {
        $this->processEvents();
        $this->processSending();
    }

    protected function processEvents(): void
    {
        $portionSize = $this->config->get('webhookQueueEventPortionSize', self::EVENT_PORTION_SIZE);

        /** @var iterable<WebhookEventQueueItem> $itemList */
        $itemList = $this->entityManager
            ->getRDBRepository(WebhookEventQueueItem::ENTITY_TYPE)
            ->where(['isProcessed' => false])
            ->order('number')
            ->limit(0, $portionSize)
            ->find();

        foreach ($itemList as $item) {
            $this->createQueueFromEvent($item);

            $item->setIsProcessed();
            $this->entityManager->saveEntity($item);
        }
    }

    protected function createQueueFromEvent(WebhookEventQueueItem $item): void
    {
        $webhookList = $this->entityManager
            ->getRDBRepository(Webhook::ENTITY_TYPE)
            ->where([
                'event' => $item->getEvent(),
                'isActive' => true,
            ])
            ->order(Field::CREATED_AT)
            ->find();

        foreach ($webhookList as $webhook) {
            $this->entityManager->createEntity(WebhookQueueItem::ENTITY_TYPE, [
                'webhookId' => $webhook->getId(),
                'event' => $item->getEvent(),
                'targetId' => $item->getTargetId(),
                'targetType' => $item->getTargetType(),
                'status' => WebhookQueueItem::STATUS_PENDING,
                'data' => $item->getData(),
                'attempts' => 0,
            ]);
        }
    }

    protected function processSending(): void
    {
        $portionSize = $this->config->get('webhookQueuePortionSize', self::PORTION_SIZE);

        $groupedItemList = $this->entityManager
            ->getRDBRepository(WebhookQueueItem::ENTITY_TYPE)
            ->select(['webhookId', 'number'])
            ->where(
                Cond::in(
                    Cond::column('number'),
                    $this->entityManager
                        ->getQueryBuilder()
                        ->select('MIN:(number)')
                        ->from(WebhookQueueItem::ENTITY_TYPE)
                        ->where([
                            'status' => WebhookQueueItem::STATUS_PENDING,
                            'OR' => [
                                ['processAt' => null],
                                ['processAt<=' => DateTimeUtil::getSystemNowString()],
                            ],
                        ])
                        ->group('webhookId')
                        ->build()
                )
            )
            ->limit(0, $portionSize)
            ->order('number')
            ->find();

        foreach ($groupedItemList as $groupItem) {
            $this->processSendingGroup($groupItem->get('webhookId'));
        }
    }

    private function processSendingGroup(string $webhookId): void
    {
        $batchSize = $this->config->get('webhookBatchSize', self::BATCH_SIZE);

        $itemList = $this->entityManager
            ->getRDBRepository(WebhookQueueItem::ENTITY_TYPE)
            ->where([
                'webhookId' => $webhookId,
                'status' => WebhookQueueItem::STATUS_PENDING,
                'OR' => [
                    ['processAt' => null],
                    ['processAt<=' => DateTimeUtil::getSystemNowString()],
                ],
            ])
            ->order('number')
            ->limit(0, $batchSize)
            ->find();

        $webhook = $this->entityManager->getRDBRepositoryByClass(Webhook::class)->getById($webhookId);

        if (!$webhook || !$webhook->isActive()) {
            foreach ($itemList as $item) {
                $this->deleteQueueItem($item);
            }

            return;
        }

        $forbiddenAttributeList = [];

        $user = null;

        if ($webhook->getUserId()) {
            $user = $this->entityManager->getRDBRepositoryByClass(User::class)->getById($webhook->getUserId());

            if (!$user) {
                foreach ($itemList as $item) {
                    $this->deleteQueueItem($item);
                }

                return;
            }

            $forbiddenAttributeList = $this->aclManager
                ->getScopeForbiddenAttributeList($user, $webhook->getTargetEntityType());
        }

        $actualItemList = [];

        $dataList = [];

        foreach ($itemList as $item) {
            $data = $this->prepareItemData($item, $user, $forbiddenAttributeList);

            if ($data === null) {
                continue;
            }

            $actualItemList[] = $item;

            $dataList[] = $data;
        }

        if (empty($dataList)) {
            return;
        }

        $this->send($webhook, $dataList, $actualItemList);
    }

    /**
     * @param string[] $forbiddenAttributeList
     */
    private function prepareItemData(WebhookQueueItem $item, ?User $user, array $forbiddenAttributeList): ?stdClass
    {
        $targetType = $item->getTargetType();

        if (!$targetType) {
            $this->deleteQueueItem($item);

            return null;
        }

        $target = null;

        if ($this->entityManager->hasRepository($targetType)) {
            $query = SelectBuilder::create()
                ->from($targetType)
                ->withDeleted()
                ->build();

            $target = $this->entityManager
                ->getRDBRepository($targetType)
                ->clone($query)
                ->where([Attribute::ID => $item->getTargetId()])
                ->findOne();
        }

        if (!$target) {
            $this->deleteQueueItem($item);

            return null;
        }

        if ($user) {
            if (!$this->aclManager->check($user, $target)) {
                $this->deleteQueueItem($item);

                return null;
            }
        }

        $data = $item->getData();

        foreach ($forbiddenAttributeList as $attribute) {
            unset($data->$attribute);
        }

        return $data;
    }

    /**
     * @param stdClass[] $dataList
     * @param WebhookQueueItem[] $itemList
     */
    private function send(Webhook $webhook, array $dataList, array $itemList): void
    {
        try {
            $code = $this->sender->send($webhook, $dataList);
        } catch (Exception $e) {
            $this->failQueueItemList($itemList, true);

            $this->log->error("Webhook Queue: Webhook '{$webhook->getId()}' sending failed. Error: {$e->getMessage()}");

            return;
        }

        if ($code >= 200 && $code < 400) {
            $this->succeedQueueItemList($itemList);
        } else if ($code === 410) {
            $this->dropWebhook($webhook);
        } else if (in_array($code, [0, 401, 403, 404, 405, 408, 500, 503])) {
            $this->failQueueItemList($itemList);
        } else if ($code >= 400 && $code < 500) {
            $this->failQueueItemList($itemList, true);
        } else {
            $this->failQueueItemList($itemList, true);
        }

        $this->logSending($webhook, $code);
    }

    protected function logSending(Webhook $webhook, int $code): void
    {
        $this->log->debug("Webhook Queue: Webhook '{$webhook->getId()}' sent, response code: $code.");
    }

    /**
     * @param WebhookQueueItem[] $itemList
     */
    protected function failQueueItemList(array $itemList, bool $force = false): void
    {
        foreach ($itemList as $item) {
            $this->failQueueItem($item, $force);
        }
    }

    /**
     * @param WebhookQueueItem[] $itemList
     */
    protected function succeedQueueItemList(array $itemList): void
    {
        foreach ($itemList as $item) {
            $this->succeedQueueItem($item);
        }
    }

    protected function deleteQueueItem(WebhookQueueItem $item): void
    {
        $this->entityManager
            ->getRDBRepository(WebhookQueueItem::ENTITY_TYPE)
            ->deleteFromDb($item->getId());
    }

    protected function dropWebhook(Webhook $webhook): void
    {
        $itemList = $this->entityManager
            ->getRDBRepository(WebhookQueueItem::ENTITY_TYPE)
            ->where([
                'status' => WebhookQueueItem::STATUS_PENDING,
                'webhookId' => $webhook->getId(),
            ])
            ->order('number')
            ->find();

        foreach ($itemList as $item) {
            $this->deleteQueueItem($item);
        }

        $this->entityManager->removeEntity($webhook);
    }

    protected function succeedQueueItem(WebhookQueueItem $item): void
    {

        $item
            ->setAttempts($item->getAttempts() + 1)
            ->setStatus(WebhookQueueItem::STATUS_SUCCESS)
            ->setProcessedAt(DateTime::createNow());

        $this->entityManager->saveEntity($item);
    }

    protected function failQueueItem(WebhookQueueItem $item, bool $force = false): void
    {
        $attempts = $item->getAttempts() + 1;

        $maxAttemptsNumber = $this->config->get('webhookMaxAttemptNumber', self::MAX_ATTEMPT_NUMBER);
        $period = $this->config->get('webhookFailAttemptPeriod', self::FAIL_ATTEMPT_PERIOD);

        if ($force) {
            $maxAttemptsNumber = 0;
        }

        $processAt = DateTime::createNow()->modify($period);

        $item->setAttempts($attempts);
        $item->setProcessAt($processAt);

        if ($attempts >= $maxAttemptsNumber) {
            $item->setStatus(WebhookQueueItem::STATUS_FAILED);
            $item->setProcessAt(null);
        }

        $this->entityManager->saveEntity($item);
    }
}
