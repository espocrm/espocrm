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

if (substr(php_sapi_name(), 0, 3) != 'cli') die('WebSocket can be run only via CLI.');

include "bootstrap.php";

$app = new \Espo\Core\Application();

$categoriesData = $app->getContainer()->get('metadata')->get(['app', 'webSocket', 'categories'], []);

$phpExecutablePath = $app->getContainer()->get('config')->get('phpExecutablePath');

$loop = \React\EventLoop\Factory::create();
$pusher = new \Espo\Core\WebSocket\Pusher($categoriesData, $phpExecutablePath);

$context = new \React\ZMQ\Context($loop);
$pull = $context->getSocket(\ZMQ::SOCKET_PULL);
$pull->bind('tcp://127.0.0.1:5555');
$pull->on('message', [$pusher, 'onMessageReceive']);

$webSocket = new \React\Socket\Server('0.0.0.0:8080', $loop);
$webServer = new \Ratchet\Server\IoServer(
    new \Ratchet\Http\HttpServer(
        new \Ratchet\WebSocket\WsServer(
            new \Ratchet\Wamp\WampServer($pusher)
        )
    ),
    $webSocket
);

$loop->run();
