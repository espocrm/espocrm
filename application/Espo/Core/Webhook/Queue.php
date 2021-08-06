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

namespace Espo\Core\Webhook;

use Espo\Entities\{
    Webhook,
    WebhookQueueItem,
    WebhookEventQueueItem,
    User,
};

use Espo\Core\{
    AclManager,
    Utils\Config,
    Utils\DateTime as DateTimeUtil,
    Utils\Log,
};

use Espo\ORM\{
    EntityManager,
    Query\Part\Condition as Cond,
};

use Exception;
use DateTime;
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

    private $sender;

    private $config;

    private $entityManager;

    private $aclManager;

    private $log;

    public function __construct(
        Sender $sender,
        Config $config,
        EntityManager $entityManager,
        AclManager $aclManager,
        Log $log
    ) {
        $this->sender = $sender;
        $this->config = $config;
        $this->entityManager = $entityManager;
        $this->aclManager = $aclManager;
        $this->log = $log;
    }

    public function process(): void
    {
        $this->processEvents();
        $this->processSending();
    }

    protected function processEvents(): void
    {
        $portionSize = $this->config->get('webhookQueueEventPortionSize', self::EVENT_PORTION_SIZE);

        $itemList = $this->entityManager
            ->getRDBRepository(WebhookEventQueueItem::ENTITY_TYPE)
            ->where([
                'isProcessed' => false,
            ])
            ->order('number')
            ->limit(0, $portionSize)
            ->find();

        foreach ($itemList as $item) {
            $this->createQueueFromEvent($item);

            $item->set([
                'isProcessed' => true,
            ]);

            $this->entityManager->saveEntity($item);
        }
    }

    protected function createQueueFromEvent(WebhookEventQueueItem $item): void
    {
        $webhookList = $this->entityManager
            ->getRDBRepository(Webhook::ENTITY_TYPE)
            ->where([
                'event' => $item->get('event'),
                'isActive' => true,
            ])
            ->order('createdAt')
            ->find();

        foreach ($webhookList as $webhook) {
            $this->entityManager->createEntity(WebhookQueueItem::ENTITY_TYPE, [
                'webhookId' => $webhook->getId(),
                'event' => $item->get('event'),
                'targetId' => $item->get('targetId'),
                'targetType' => $item->get('targetType'),
                'status' => 'Pending',
                'data' => $item->get('data'),
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
                            'status' => 'Pending',
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
                'status' => 'Pending',
                'OR' => [
                    ['processAt' => null],
                    ['processAt<=' => DateTimeUtil::getSystemNowString()],
                ],
            ])
            ->order('number')
            ->limit(0, $batchSize)
            ->find();

        $webhook = $this->entityManager->getEntity(Webhook::ENTITY_TYPE, $webhookId);

        if (!$webhook || !$webhook->get('isActive')) {
            foreach ($itemList as $item) {
                $this->deleteQueueItem($item);
            }
        }

        $forbiddenAttributeList = [];

        $user = null;

        if ($webhook->get('userId')) {
            $user = $this->entityManager->getEntity(User::ENTITY_TYPE, $webhook->get('userId'));

            if (!$user) {
                foreach ($itemList as $item) {
                    $this->deleteQueueItem($item);
                }

                return;
            }

            $forbiddenAttributeList = $this->aclManager
                ->getScopeForbiddenAttributeList($user, $webhook->get('entityType'));
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

    private function prepareItemData(WebhookQueueItem $item, ?User $user, array $forbiddenAttributeList): ?stdClass
    {
        $targetType = $item->get('targetType');
        $target = null;

        if ($this->entityManager->hasRepository($targetType)) {
            $target = $this->entityManager
                ->getRDBRepository($targetType)
                ->where([
                    'id' => $item->get('targetId')
                ])
                ->findOne(['withDeleted' => true]);
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

        $data = $item->get('data') ?? (object) [];

        foreach ($forbiddenAttributeList as $attribute) {
            unset($data->$attribute);
        }

        return $data;
    }

    private function send(Webhook $webhook, array $dataList, array $itemList): void
    {
        try {
            $code = $this->sender->send($webhook, $dataList);
        }
        catch (Exception $e) {
            $this->failQueueItemList($itemList, true);

            $this->log->error(
                "Webhook Queue: Webhook '" . $webhook->getId() . "' sending failed. Error: " . $e->getMessage()
            );

            return;
        }

        if ($code >= 200 && $code < 400) {
            $this->succeedQueueItemList($itemList);
        }
        else if ($code === 410) {
            $this->dropWebhook($webhook);
        }
        else if (in_array($code, [0, 401, 403, 404, 405, 408, 500, 503])) {
            $this->failQueueItemList($itemList);
        }
        else if ($code >= 400 && $code < 500) {
            $this->failQueueItemList($itemList, true);
        }
        else {
            $this->failQueueItemList($itemList, true);
        }

        $this->logSending($webhook, $code);
    }

    protected function logSending(Webhook $webhook, int $code): void
    {
        $this->log->debug("Webhook Queue: Webhook '" . $webhook->getId()  . "' sent, response code: {$code}.");
    }

    protected function failQueueItemList(array $itemList, bool $force = false): void
    {
        foreach ($itemList as $item) {
            $this->failQueueItem($item, $force);
        }
    }

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
                'status' => 'Pending',
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
        $item->set([
            'attempts' => $item->get('attempts') + 1,
            'status' => 'Success',
            'processedAt' => DateTimeUtil::getSystemNowString(),
        ]);

        $this->entityManager->saveEntity($item);
    }

    protected function failQueueItem(WebhookQueueItem $item, bool $force = false): void
    {
        $attempts = $item->get('attempts') + 1;

        $maxAttemptsNumber = $this->config->get('webhookMaxAttemptNumber', self::MAX_ATTEMPT_NUMBER);
        $period = $this->config->get('webhookFailAttemptPeriod', self::FAIL_ATTEMPT_PERIOD);

        if ($force) {
            $maxAttemptsNumber = 0;
        }

        $dt = new DateTime();

        $dt->modify($period);

        $item->set([
            'attempts' => $attempts,
            'processAt' => $dt->format(DateTimeUtil::SYSTEM_DATE_TIME_FORMAT),
        ]);

        if ($attempts >= $maxAttemptsNumber) {
            $item->set('status', 'Failed');
            $item->set('processAt', null);
        }

        $this->entityManager->saveEntity($item);
    }
}
