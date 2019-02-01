<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2018 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
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

namespace Espo\Core\WebSocket;

use Ratchet\ConnectionInterface;
use Ratchet\Wamp\WampServerInterface;

class Pusher implements WampServerInterface
{
    private $categoryList;

    protected $connectionIdUserIdMap = [];

    protected $userIdConnectionIdListMap = [];

    protected $connectionIdTopicIdListMap = [];

    protected $connections = [];

    public function __construct(array $categoryList = [])
    {
        $this->categoryList = $categoryList;
    }

    public function onSubscribe(ConnectionInterface $connection, $topic)
    {
        $topicId = $topic->getId();
        if (!$topicId) return;

        if (!$this->isCategoryAllowed($topicId)) return;

        $connectionId = $connection->resourceId;

        $userId = $this->getUserIdByConnection($connection);
        if (!$userId) return;

        if (!isset($this->connectionIdTopicIdListMap[$connectionId])) $this->connectionIdTopicIdListMap[$connectionId] = [];

        if (!in_array($topicId, $this->connectionIdTopicIdListMap[$connectionId])) {
            echo "add topic {$topicId} for user {$userId}\n";
            $this->connectionIdTopicIdListMap[$connectionId][] = $topicId;
        }
    }

    public function onUnSubscribe(ConnectionInterface $connection, $topic)
    {
        $topicId = $topic->getId();
        if (!$topicId) return;

        if (!$this->isCategoryAllowed($topicId)) return;

        $connectionId = $connection->resourceId;

        $userId = $this->getUserIdByConnection($connection);
        if (!$userId) return;

        if (isset($this->connectionIdTopicIdListMap[$connectionId])) {
            $index = array_search($topicId, $this->connectionIdTopicIdListMap[$connectionId]);
            if ($index !== false) {
                echo "remove topic {$topicId} for user {$userId}\n";
                $this->connectionIdTopicIdListMap[$connectionId] = array_splice($this->connectionIdTopicIdListMap[$connectionId], $index, 1);
            }
        }
    }

    protected function isCategoryAllowed($category)
    {
        return in_array($category, $this->categoryList);
    }

    protected function getConnectionIdListByUserId($userId)
    {
        if (!isset($this->userIdConnectionIdListMap[$userId])) return [];
        return $this->userIdConnectionIdListMap[$userId];
    }

    protected function getUserIdByConnection(ConnectionInterface $connection)
    {
        if (!isset($this->connectionIdUserIdMap[$connection->resourceId])) return;
        return $this->connectionIdUserIdMap[$connection->resourceId];
    }

    protected function subscribeUser(ConnectionInterface $connection, $userId)
    {
        $resourceId = $connection->resourceId;

        $this->connectionIdUserIdMap[$resourceId] = $userId;

        if (!isset($this->userIdConnectionIdListMap[$userId])) $this->userIdConnectionIdListMap[$userId] = [];

        if (!in_array($resourceId, $this->userIdConnectionIdListMap[$userId])) {
            $this->userIdConnectionIdListMap[$userId][] = $resourceId;
        }

        $this->connections[$resourceId] = $connection;

        echo "{$userId} subscribed\n";
    }

    protected function unsubscribeUser(ConnectionInterface $connection, $userId)
    {
        $resourceId = $connection->resourceId;

        unset($this->connectionIdUserIdMap[$resourceId]);

        if (isset($this->userIdConnectionIdListMap[$userId])) {
            $index = array_search($resourceId, $this->userIdConnectionIdListMap[$userId]);
            if ($index !== false) {
                $this->userIdConnectionIdListMap[$userId] = array_splice($this->userIdConnectionIdListMap[$userId], $index, 1);
            }
        }

        echo "{$userId} unsubscribed\n";
    }

    public function onOpen(ConnectionInterface $connection)
    {
        echo "onOpen {$connection->resourceId}\n";

        $query = $connection->httpRequest->getUri()->getQuery();
        $params = \GuzzleHttp\Psr7\parse_query($query ?: '');
        if (empty($params['userId']) || empty($params['authToken'])) {
            $this->closeConnection($connection);
            return;
        }

        $authToken = preg_replace('/[^a-zA-Z0-9]+/', '', $params['authToken']);
        $userId = $params['userId'];

        $result = shell_exec("php auth_token_check.php " . $authToken);
        if (empty($result)) {
            $this->closeConnection($connection);
            return;
        }

        if ($result !== $userId) {
            $this->closeConnection($connection);
            return;
        }

        $this->subscribeUser($connection, $userId);
    }

    protected function closeConnection(ConnectionInterface $connection)
    {
        $userId = $this->getUserIdByConnection($connection);
        if ($userId) {
            $this->unsubscribeUser($connection, $userId);
        }

        $connection->close();
    }

    public function onClose(ConnectionInterface $connection)
    {
        echo "onClose {$connection->resourceId}\n";

        $userId = $this->getUserIdByConnection($connection);

        if ($userId) {
            $this->unsubscribeUser($connection, $userId);
        }

        unset($this->connections[$connection->resourceId]);
    }

    public function onCall(ConnectionInterface $connection, $id, $topic, array $params)
    {
        $connection->callError($id, $topic, 'You are not allowed to make calls')->close();
    }

    public function onPublish(ConnectionInterface $connection, $topic, $event, array $exclude, array $eligible)
    {
        $topicId = $topic->getId();
        $connection->close();
    }

    public function onError(ConnectionInterface $connection, \Exception $e)
    {
    }

    public function onMessageReceive($dataString)
    {
        $data = json_decode($dataString);

        if (!property_exists($data, 'category')) return;
        if (!property_exists($data, 'userId')) return;

        $userId = $data->userId;
        $category = $data->category;

        if (!$userId || !$category) return;

        if (!in_array($category, $this->categoryList)) return;

        foreach ($this->getConnectionIdListByUserId($userId) as $connectionId) {
            if (!isset($this->connections[$connectionId])) continue;
            if (!isset($this->connectionIdTopicIdListMap[$connectionId])) continue;

            $connection = $this->connections[$connectionId];

            if (in_array($category, $this->connectionIdTopicIdListMap[$connectionId])) {
                echo "send {$category} for connection {$connectionId}\n";
                $connection->event($category, $data);
            }
        }

        echo "onMessage {$category} for {$userId}\n";
    }
}
