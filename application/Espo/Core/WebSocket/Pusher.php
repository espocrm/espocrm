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

namespace Espo\Core\WebSocket;

use GuzzleHttp\Psr7\Query;

use Psr\Http\Message\RequestInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Wamp\Topic;
use Ratchet\Wamp\WampConnection;
use Ratchet\Wamp\WampServerInterface;

use Symfony\Component\Process\PhpExecutableFinder;

use Exception;
use RuntimeException;

class Pusher implements WampServerInterface
{
    /** @var string[] */
    private $categoryList;
    /** @var array<string, array<string, mixed>> */
    protected $categoriesData;
    protected bool $isDebugMode = false;
    /** @var array<string, string> */
    protected $connectionIdUserIdMap = [];
    /** @var array<string, string[]> */
    protected $userIdConnectionIdListMap = [];
    /** @var array<string, string[]> */
    protected $connectionIdTopicIdListMap = [];
    /** @var array<string, ConnectionInterface> */
    protected $connections = [];
    /** @var array<string, Topic<object>> */
    protected $topicHash = [];
    private string $phpExecutablePath;

    /**
     * @param array<string, array<string, mixed>> $categoriesData
     */
    public function __construct(
        array $categoriesData = [],
        ?string $phpExecutablePath = null,
        bool $isDebugMode = false
    ) {
        $this->categoryList = array_keys($categoriesData);
        $this->categoriesData = $categoriesData;

        if (!$phpExecutablePath) {
            $phpExecutablePath = (new PhpExecutableFinder)->find() ?: null;
        }

        if (!$phpExecutablePath) {
            if ($isDebugMode) {
                $this->log("Error: No php-executable-path.");
            }

            throw new RuntimeException("No php-executable-path.");
        }

        $this->phpExecutablePath = $phpExecutablePath;
        $this->isDebugMode = $isDebugMode;
    }

    /**
     * @param Topic<object> $topic
     * @return void
     */
    public function onSubscribe(ConnectionInterface $conn, $topic)
    {
        $topicId = $topic->getId();

        if (!$topicId) {
            return;
        }

        if (!$this->isTopicAllowed($topicId)) {
            return;
        }

        /** @var string $connectionId */
        $connectionId = $conn->resourceId ?? throw new RuntimeException();

        $userId = $this->getUserIdByConnection($conn);

        if (!$userId) {
            return;
        }

        if (!isset($this->connectionIdTopicIdListMap[$connectionId])) {
            $this->connectionIdTopicIdListMap[$connectionId] = [];
        }

        $checkCommand = $this->getAccessCheckCommandForTopic($conn, $topic);

        if ($checkCommand) {
            $checkResult = shell_exec($checkCommand);

            if ($checkResult !== 'true') {
                if ($this->isDebugMode) {
                    $this->log("$connectionId: check access failed for topic $topicId for user $userId");
                }

                return;
            }

            if ($this->isDebugMode) {
                $this->log("$connectionId: check access succeed for topic $topicId for user $userId");
            }
        }

        if (!in_array($topicId, $this->connectionIdTopicIdListMap[$connectionId])) {
            if ($this->isDebugMode) {
                $this->log("$connectionId: add topic $topicId for user $userId");
            }

            $this->connectionIdTopicIdListMap[$connectionId][] = $topicId;
        }

        $this->topicHash[$topicId] = $topic;
    }

    /**
     * @param Topic<object> $topic
     * @return void
     */
    public function onUnSubscribe(ConnectionInterface $conn, $topic)
    {
        $topicId = $topic->getId();

        if (!$topicId) {
            return;
        }

        if (!$this->isTopicAllowed($topicId)) {
            return;
        }

        /** @var string $connectionId */
        $connectionId = $conn->resourceId ?? throw new RuntimeException();

        $userId = $this->getUserIdByConnection($conn);

        if (!$userId) {
            return;
        }

        if (!isset($this->connectionIdTopicIdListMap[$connectionId])) {
            return;
        }

        $index = array_search($topicId, $this->connectionIdTopicIdListMap[$connectionId]);

        if ($index === false) {
            return;
        }

        if ($this->isDebugMode) {
            $this->log("$connectionId: remove topic $topicId for user $userId");
        }

        unset($this->connectionIdTopicIdListMap[$connectionId][$index]);

        $this->connectionIdTopicIdListMap[$connectionId] =
            array_values($this->connectionIdTopicIdListMap[$connectionId]);
    }

    /**
     * @return array<string, mixed>
     */
    private function getCategoryData(string $topicId): array
    {
        $arr = explode('.', $topicId);

        $category = $arr[0];

        if (array_key_exists($category, $this->categoriesData)) {
            $data = $this->categoriesData[$category];
        } else if (array_key_exists($topicId, $this->categoriesData)) {
            $data = $this->categoriesData[$topicId];
        } else {
            $data = [];
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function getParamsFromTopicId(string $topicId): array
    {
        $arr = explode('.', $topicId);

        $data = $this->getCategoryData($topicId);

        $params = [];

        if (array_key_exists('paramList', $data)) {
            foreach ($data['paramList'] as $i => $item) {
                /** @var string $item */

                if (isset($arr[$i + 1])) {
                    $params[$item] = $arr[$i + 1];
                } else {
                    $params[$item] = '';
                }
            }
        }

        return $params;
    }

    /**
     * @param Topic<object> $topic
     */
    private function getAccessCheckCommandForTopic(ConnectionInterface $conn, $topic): ?string
    {
        $topicId = $topic->getId();

        $params = $this->getParamsFromTopicId($topicId);
        $params['userId'] = $this->getUserIdByConnection($conn);

        if (!$params['userId']) {
            $conn->close();

            return null;
        }

        $data = $this->getCategoryData($topic->getId());

        if (!array_key_exists('accessCheckCommand', $data)) {
            return null;
        }

        $command = $this->phpExecutablePath . " command.php " . $data['accessCheckCommand'];

        foreach ($params as $key => $value) {
            $command = str_replace(
                ':' . $key,
                escapeshellarg($value),
                $command
            );
        }

        return $command;
    }

    /**
     * @param string $topicId
     * @return bool
     */
    private function isTopicAllowed($topicId)
    {
        [$category] = explode('.', $topicId);

        return in_array($topicId, $this->categoryList) || in_array($category, $this->categoryList);
    }

    /**
     * @param string $userId
     * @return string[]
     */
    private function getConnectionIdListByUserId($userId)
    {
        if (!isset($this->userIdConnectionIdListMap[$userId])) {
            return [];
        }

        return $this->userIdConnectionIdListMap[$userId];
    }

    /**
     * @return ?string
     */
    private function getUserIdByConnection(ConnectionInterface $conn)
    {
        $connectionId = $conn->resourceId ?? '';

        if (!isset($this->connectionIdUserIdMap[$connectionId])) {
            return null;
        }

        return $this->connectionIdUserIdMap[$connectionId];
    }

    /**
     * @param string $userId
     * @return void
     */
    private function subscribeUser(ConnectionInterface $conn, $userId)
    {
        /** @var string $resourceId */
        $resourceId = $conn->resourceId ?? '';

        $this->connectionIdUserIdMap[$resourceId] = $userId;

        if (!isset($this->userIdConnectionIdListMap[$userId])) {
            $this->userIdConnectionIdListMap[$userId] = [];
        }

        if (!in_array($resourceId, $this->userIdConnectionIdListMap[$userId])) {
            $this->userIdConnectionIdListMap[$userId][] = $resourceId;
        }

        $this->connections[$resourceId] = $conn;

        if ($this->isDebugMode) {
            $this->log("$resourceId: user $userId subscribed");
        }
    }

    /**
     * @param string $userId
     * @return void
     */
    private function unsubscribeUser(ConnectionInterface $conn, $userId)
    {
        $resourceId = $conn->resourceId ?? '';

        unset($this->connectionIdUserIdMap[$resourceId]);

        if (isset($this->userIdConnectionIdListMap[$userId])) {
            $index = array_search($resourceId, $this->userIdConnectionIdListMap[$userId]);

            if ($index !== false) {
                unset($this->userIdConnectionIdListMap[$userId][$index]);
                $this->userIdConnectionIdListMap[$userId] = array_values($this->userIdConnectionIdListMap[$userId]);
            }
        }

        if ($this->isDebugMode) {
            $this->log("$resourceId: user $userId unsubscribed");
        }
    }

    /**
     * @return void
     */
    public function onOpen(ConnectionInterface $conn)
    {
        if ($this->isDebugMode) {
            $resourceId = $conn->resourceId ?? '';

            $this->log("$resourceId: open");
        }

        /** @var RequestInterface $httpRequest */
        $httpRequest = $conn->httpRequest ?? throw new RuntimeException();

        $query = $httpRequest->getUri()->getQuery();

        $params = Query::parse($query ?: '');

        if (empty($params['userId']) || empty($params['authToken'])) {
            $this->closeConnection($conn);

            return;
        }

        $authToken = preg_replace('/[^a-zA-Z0-9\-]+/', '', $params['authToken']);
        $userId = preg_replace('/[^a-zA-Z0-9\-]+/', '', $params['userId']);

        $result = $this->getUserIdByAuthToken($authToken);

        if (empty($result)) {
            $this->closeConnection($conn);

            return;
        }

        if ($result !== $userId) {
            $this->closeConnection($conn);

            return;
        }

        $this->subscribeUser($conn, $userId);
    }

    /**
     * @param string $authToken
     * @return string
     */
    private function getUserIdByAuthToken($authToken)
    {
        /** @var string|null|false $result */
        $result = shell_exec($this->phpExecutablePath . " command.php AuthTokenCheck " . $authToken);

        if ($result === null || $result === false) {
            return '';
        }

        return $result;
    }

    /**
     * @return void
     */
    private function closeConnection(ConnectionInterface $conn)
    {
        $userId = $this->getUserIdByConnection($conn);

        if ($userId) {
            $this->unsubscribeUser($conn, $userId);
        }

        $conn->close();
    }

    /**
     * @return void
     */
    public function onClose(ConnectionInterface $conn)
    {
        $connectionId = $conn->resourceId ?? '';

        if ($this->isDebugMode) {
            $this->log("$connectionId: close");
        }

        $userId = $this->getUserIdByConnection($conn);

        if ($userId) {
            $this->unsubscribeUser($conn, $userId);
        }

        unset($this->connections[$connectionId]);
    }

    /**
     * @param string $id
     * @param Topic<object> $topic
     * @param array<string, mixed> $params
     * @return void
     */
    public function onCall(ConnectionInterface $conn, $id, $topic, array $params)
    {
        if (!method_exists($conn, 'callError')) {
            return;
        }

        $conn->callError($id, $topic, 'You are not allowed to make calls')
            ->close();
    }

    /**
     * @param Topic<object> $topic
     * @param string $event
     * @param array<int|string, mixed> $exclude
     * @param array<int|string, mixed> $eligible
     * @return void
     */
    public function onPublish(ConnectionInterface $conn, $topic, $event, array $exclude, array $eligible)
    {
        $topicId = $topic->getId();

        if ($topicId === '') {
            return;
        }

        $conn->close();
    }

    /**
     * @return void
     */
    public function onError(ConnectionInterface $conn, Exception $e)
    {
    }

    public function onMessageReceive(string $message): void
    {
        $data = json_decode($message);

        if (!property_exists($data, 'topicId')) {
            return;
        }

        $userId = $data->userId ?? null;
        $topicId = $data->topicId ?? null;

        if (!$topicId) {
            return;
        }

        if (!$this->isTopicAllowed($topicId)) {
            return;
        }

        if ($userId) {
            foreach ($this->getConnectionIdListByUserId($userId) as $connectionId) {
                if (!isset($this->connections[$connectionId])) {
                    continue;
                }

                if (!isset($this->connectionIdTopicIdListMap[$connectionId])) {
                    continue;
                }

                /** @var WampConnection $connection */
                $connection = $this->connections[$connectionId];

                if (in_array($topicId, $this->connectionIdTopicIdListMap[$connectionId])) {
                    if ($this->isDebugMode) {
                        $this->log("send $topicId for connection $connectionId");
                    }

                    $connection->event($topicId, $data);
                }
            }

            if ($this->isDebugMode) {
                $this->log("message $topicId for user $userId");
            }

            return;
        }

        $topic = $this->topicHash[$topicId] ?? null;

        if ($topic) {
            $topic->broadcast($data);

            if ($this->isDebugMode) {
                $this->log("send $topicId to all");
            }
        }

        if ($this->isDebugMode) {
            $this->log("message $topicId for all");
        }
    }

    private function log(string $msg): void
    {
        echo "[" . date('Y-m-d H:i:s') . "] " . $msg . "\n";
    }
}
