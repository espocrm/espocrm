<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2026 EspoCRM, Inc.
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

namespace Espo\Core\Job\Processing\RabbitMq;

use Espo\Core\Job\Processing\Publisher as PublisherInterface;
use Espo\Core\Job\Processing\Publisher\Params;
use Espo\Core\Utils\Json;
use Espo\Entities\Job;
use Espo\ORM\Name\Attribute;
use Exception;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use RuntimeException;

class Publisher implements PublisherInterface
{
    public const string QUEUE = 'espo.jobs';

    private ?AMQPStreamConnection $connection = null;
    private ?AMQPChannel $channel = null;
    private string $queue = self::QUEUE;

    public function __construct(
        private ConnectionFactory $connectionFactory,
    ) {}

    public function initialize(Params $params): void
    {
        $this->queue = Util::composeQueueName($params->queue);

        $this->connection = $this->connectionFactory->create();
        $this->channel = $this->prepareChannel($this->connection);
    }

    public function publish(Job $job): void
    {
        if (!$this->channel) {
            throw new RuntimeException("No channel.");
        }

        $message = $this->prepareMessage($job);

        $this->channel->basic_publish(
            msg: $message,
            routing_key: $this->queue,
        );
    }

    public function close(): void
    {
        $this->channel?->close();

        if ($this->connection) {
            $this->closeConnection($this->connection);
        }

        $this->channel = null;
        $this->connection = null;
    }

    private function prepareChannel(AMQPStreamConnection $connection): AMQPChannel
    {
        $channel = $connection->channel();

        $channel->queue_declare(
            queue: $this->queue,
            durable: true,
            auto_delete: false,
        );

        return $channel;
    }

    private function closeConnection(AMQPStreamConnection $connection): void
    {
        try {
            $connection->close();
        } catch (Exception $e) {
            throw new RuntimeException("Connection closing error.", previous: $e);
        }
    }

    private function prepareMessage(Job $job): AMQPMessage
    {
        $payload = Json::encode([Attribute::ID => $job->getId()]);

        return new AMQPMessage($payload, ['delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT]);
    }
}
