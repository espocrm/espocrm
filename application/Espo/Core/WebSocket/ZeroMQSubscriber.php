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

use Espo\Core\Utils\Config;

use React\EventLoop\LoopInterface;
use React\ZMQ\Context as ZMQContext;
use Evenement\EventEmitter;
use React\ZMQ\SocketWrapper;

use ZMQ;

class ZeroMQSubscriber implements Subscriber
{
    private const DSN = 'tcp://127.0.0.1:5555';

    public function __construct(private Config $config)
    {}

    public function subscribe(Pusher $pusher, LoopInterface $loop): void
    {
        $dsn = $this->config->get('webSocketZeroMQSubscriberDsn') ?? self::DSN;

        $context = new ZMQContext($loop);

        /** @var EventEmitter $pull */
        /** @var SocketWrapper $pull */
        $pull = $context->getSocket(ZMQ::SOCKET_PULL);

        $pull->bind($dsn);
        $pull->on('message', [$pusher, 'onMessageReceive']);
    }
}
