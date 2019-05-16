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
use Espo\Core\Utils\DateTime;
use Espo\Entities\WebhookQueueItem;
use Espo\Entities\Webhook;

class Queue
{
    const EVENT_PORTION_SIZE = 20;

    const PORTION_SIZE = 20;

    const BATCH_SIZE = 50;

    const MAX_ATTEMPT_NUMBER = 4;

    const FAIL_ATTEMPT_PERIOD = '10 minutes';

    protected $sender;
    protected $config;
    protected $entityManager;
    protected $aclManager;

    public function __construct(
        Sender $sender,
        \Espo\Core\Utils\Config $config,
        \Espo\ORM\EntityManager $entityManager,
        \Espo\Core\AclManager $aclManager
    ) {
        $this->sender = $sender;
        $this->config = $config;
        $this->entityManager = $entityManager;
        $this->aclManager = $aclManager;
    }

    public function process()
    {
        $this->processEvents();
        $this->processSending();
    }

    protected function processEvents()
    {
        $portionSize = $this->config->get('webhookQueueEventPortionSize', self::EVENT_PORTION_SIZE);

        $itemList = $this->entityManager->getRepository('WebhookEventQueueItem')->where([
            'isProcessed' => false,
        ])->order('number')->limit(0, $portionSize)->find();

        foreach ($itemList as $item) {
            $this->createQueueFromEvent($item);
            $item->set([
                'isProcessed' => true,
            ]);
            $this->entityManager->saveEntity($item);
        }
    }

    protected function createQueueFromEvent(\Espo\Entities\WebhookEventQueueItem $item)
    {
        $webhookList = $this->entityManager->getRepository('Webhook')->where([
            'event' => $item->get('event'),
            'isActive' => true,
        ])->order('createdAt')->find();

        foreach ($webhookList as $webhook) {
            $this->entityManager->createEntity('WebhookQueueItem', [
                'webhookId' => $webhook->id,
                'event' => $item->get('event'),
                'targetId' => $item->get('targetId'),
                'targetType' => $item->get('targetType'),
                'status' => 'Pending',
                'data' => $item->get('data'),
                'attempts' => 0,
            ]);
        }
    }

    protected function processSending()
    {
        $portionSize = $this->config->get('webhookQueuePortionSize', self::PORTION_SIZE);
        $batchSize = $this->config->get('webhookBatchSize', self::BATCH_SIZE);

        $groupedItemList = $this->entityManager->getRepository('WebhookQueueItem')->where([
            'status' => 'Pending',
            'OR' => [
                ['processAt' => null],
                ['processAt<=' => DateTime::getSystemNowString()],
            ],
        ])->order('number')->limit(0, $portionSize)->groupBy(['webhookId'])->find();

        foreach ($groupedItemList as $group) {
            $webhookId = $group->get('webhookId');

            $itemList = $this->entityManager->getRepository('WebhookQueueItem')->where([
                'webhookId' => $webhookId,
                'status' => 'Pending',
                'OR' => [
                    ['processAt' => null],
                    ['processAt<=' => DateTime::getSystemNowString()],
                ],
            ])->order('number')->limit(0, $batchSize)->find();

            $webhook = $this->entityManager->getEntity('Webhook', $webhookId);
            if (!$webhook || !$webhook->get('isActive')) {
                foreach ($itemList as $item) {
                    $this->deleteQueueItem($item);
                }
            }

            $forbiddenAttributeList = [];
            $user = null;
            if ($webhook->get('userId')) {
                $user = $this->entityManager->getEntity('User', $webhook->get('userId'));
                if (!$user) {
                    foreach ($itemList as $item) {
                        $this->deleteQueueItem($item);
                    }
                    continue;
                } else {
                    $forbiddenAttributeList = $this->aclManager->getScopeForbiddenAttributeList($user, $webhook->get('entityType'));
                }
            }

            $actualItemList = [];

            $dataList = [];
            foreach ($itemList as $item) {
                $targetType = $item->get('targetType');
                $target = null;
                if ($this->entityManager->hasRepository($targetType)) {
                    $target = $this->entityManager->getRepository($targetType)->where([
                        'id' => $item->get('targetId')
                    ])->findOne(['withDeleted' => true]);
                }
                if (!$target) {
                    $this->deleteQueueItem($item);
                    continue;
                }

                if ($user) {
                    if (!$this->aclManager->check($user, $target)) {
                        $this->deleteQueueItem($item);
                        continue;
                    }
                }

                $data = $item->get('data') ?? (object) [];
                $data = clone $data;

                foreach ($forbiddenAttributeList as $attribute) {
                    unset($data->$attribute);
                }

                $actualItemList[] = $item;
                $dataList[] = $data;
            }
            if (empty($dataList)) continue;

            $this->send($webhook, $dataList, $actualItemList);
        }
    }

    protected function send(Webhook $webhook, array $dataList, array $itemList)
    {
        try {
            $code = $this->sender->send($webhook, $dataList);
        } catch (\Exception $e) {
            $this->failQueueItemList($itemList, true);
            $GLOBALS['log']->error("Webhook Queue: Webhook {$webhook->id} sending failed. Error: " . $e->getMessage());
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

    protected function logSending(Webhook $webhook, int $code)
    {
        $GLOBALS['log']->debug("Webhook Queue: Webhook {$webhook->id} sent, response code: {$code}.");
    }

    protected function failQueueItemList(array $itemList, bool $force = false)
    {
        foreach ($itemList as $item) {
            $this->failQueueItem($item, $force);
        }
    }

    protected function succeedQueueItemList(array $itemList)
    {
        foreach ($itemList as $item) {
            $this->succeedQueueItem($item);
        }
    }

    protected function deleteQueueItem(WebhookQueueItem $item)
    {
        $this->entityManager->getRepository('WebhookQueueItem')->deleteFromDb($item->id);
    }

    protected function dropWebhook(Webhook $webhook)
    {
        $itemList = $this->entityManager->getRepository('WebhookQueueItem')->where([
            'status' => 'Pending',
            'webhookId' => $webhook->id,
        ])->order('number')->find();

        foreach ($itemList as $item) {
            $this->deleteQueueItem($item);
        }

        $this->entityManager->removeEntity($webhook);
    }

    protected function succeedQueueItem(WebhookQueueItem $item)
    {
        $item->set([
            'attempts' => $item->get('attempts') + 1,
            'status' => 'Success',
            'processedAt' => DateTime::getSystemNowString(),
        ]);

        $this->entityManager->saveEntity($item);
    }

    protected function failQueueItem(WebhookQueueItem $item, bool $force = false)
    {
        $attempts = $item->get('attempts') + 1;
        $maxAttemptsNumber = $this->config->get('webhookMaxAttemptNumber', self::MAX_ATTEMPT_NUMBER);
        $period = $this->config->get('webhookFailAttemptPeriod', self::FAIL_ATTEMPT_PERIOD);

        if ($force) $maxAttemptsNumber = 0;

        $dt = new \DateTime();
        $dt->modify($period);

        $item->set([
            'attempts' => $attempts,
            'processAt' => $dt->format(DateTime::$systemDateTimeFormat),
        ]);

        if ($attempts >= $maxAttemptsNumber) {
            $item->set('status', 'Failed');
            $item->set('processAt', null);
        }

        $this->entityManager->saveEntity($item);
    }
}
