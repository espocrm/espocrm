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

use Espo\Core\Job\JobRunner;
use Espo\Core\Job\Processing\JobProvider;
use Espo\Core\Job\Processing\Consumer as ConsumerInterface;
use Espo\Core\Job\Processing\Consumer\Params;
use Espo\Core\Job\Processing\Util\ExitPolicy;
use Espo\Core\Utils\Log;
use Exception;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exception\AMQPTimeoutException;
use PhpAmqpLib\Message\AMQPMessage;
use RuntimeException;
use Throwable;

class Consumer implements ConsumerInterface
{
    private bool $stopped = false;

    private const float ITERATION_TIMEOUT = 1.0;

    public function __construct(
        private ConnectionFactory $connectionFactory,
        private JobRunner $jobRunner,
        private Log $log,
        private JobProvider $jobProvider,
        private ExitPolicy $exitPolicy,
    ) {}

    public function start(Params $params): void
    {
        if ($this->stopped) {
            $this->stopped = false;

            return;
        }

        $queue = Util::composeQueueName($params->queue);

        $connection = $this->connectionFactory->create();
        $channel = $this->prepareChannel($connection, $queue);

        $this->setupConsume($channel, $queue);

        $count = 0;

        while ($channel->is_consuming()) {
            try {
                $channel->wait(timeout: self::ITERATION_TIMEOUT);
            } catch (AMQPTimeoutException) {
                if ($this->toStop($params, $count)) {
                    break;
                }

                continue;
            }

            $count ++;

            if ($this->toStop($params, $count)) {
                break;
            }
        }

        $channel->close();
        $this->closeConnection($connection);

        $this->stopped = false;
    }

    public function stop(): void
    {
        $this->stopped = true;
    }

    private function prepareChannel(AMQPStreamConnection $connection, string $queue): AMQPChannel
    {
        $channel = $connection->channel();

        $channel->queue_declare(
            queue: $queue,
            durable: true,
            auto_delete: false,
        );

        $channel->basic_qos(
            prefetch_size: 0,
            prefetch_count: 1,
            a_global: false,
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

    private function getJobId(AMQPMessage $message): string
    {
        $payload = json_decode($message->getBody());

        if (!is_object($payload)) {
            throw new RuntimeException("Bad payload.");
        }

        $id = $payload->id ?? null;

        if (!is_string($id)) {
            throw new RuntimeException("No string ID.");
        }

        return $id;
    }

    private function nack(AMQPMessage $message): void
    {
        $message->nack(false, true);
    }

    private function setupConsume(AMQPChannel $channel, string $queue): void
    {
        $channel->basic_consume(
            queue: $queue,
            callback: function (AMQPMessage $message) {
                try {
                    $id = $this->getJobId($message);
                } catch (Throwable $e) {
                    $this->log->error("Worker: Could not get job ID.", ['exception' => $e]);

                    $this->nack($message);

                    return;
                }

                try {
                    $job = $this->jobProvider->get($id);

                    $this->jobRunner->run($job);
                } catch (Throwable $e) {
                    $this->log->error("Worker: Job {id} failed.", [
                        'exception' => $e,
                        'id' => $id,
                    ]);

                    $this->nack($message);

                    return;
                }

                $message->ack();
            },
        );
    }

    private function toStop(Params $params, int $count): bool
    {
        return
            $this->stopped ||
            $params->limit && $count >= $params->limit ||
            $this->exitPolicy->toExit();
    }
}
