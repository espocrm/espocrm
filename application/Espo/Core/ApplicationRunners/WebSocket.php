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

namespace Espo\Core\ApplicationRunners;

use Espo\Core\{
    Utils\Config,
    Utils\Metadata,
    WebSocket\Pusher,
};

use React\{
    EventLoop\Factory as EventLoopFactory,
    ZMQ\Context as ZMQContext,
    Socket\Server as SocketServer,
    Socket\SecureServer as SocketSecureServer,
};

use Ratchet\{
    Server\IoServer,
    Http\HttpServer,
    WebSocket\WsServer,
    Wamp\WampServer,
};

use ZMQ;

/**
 * Runs WebSocket.
 */
class WebSocket implements ApplicationRunner
{
    use Cli;

    protected $categoriesData;
    protected $phpExecutablePath;
    protected $isDebugMode;
    protected $useSecureServer;
    protected $port;

    protected $config;
    protected $metadata;

    public function __construct(Config $config, Metadata $metadata)
    {
        $this->config = $config;
        $this->metadata = $metadata;

        $this->categoriesData = $metadata->get(['app', 'webSocket', 'categories'], []);

        $this->phpExecutablePath = $config->get('phpExecutablePath');
        $this->isDebugMode = (bool) $config->get('webSocketDebugMode');
        $this->useSecureServer = (bool) $config->get('webSocketUseSecureServer');

        $this->port = $this->config->get('webSocketPort');

        if (!$this->port) {
            $this->port = $this->useSecureServer ? '8443' : '8080';
        }
    }

    public function run() : void
    {
        $loop = EventLoopFactory::create();

        $pusher = new Pusher($this->categoriesData, $this->phpExecutablePath, $this->isDebugMode);

        $context = new ZMQContext($loop);

        $pull = $context->getSocket(ZMQ::SOCKET_PULL);

        $pull->bind('tcp://127.0.0.1:5555');
        $pull->on('message', [$pusher, 'onMessageReceive']);

        $socketServer = new SocketServer('0.0.0.0:' . $this->port, $loop);

        if ($this->useSecureServer) {
            $sslParams = $this->getSslParams();

            $socketServer = new SocketSecureServer($socketServer, $loop, $sslParams);
        }

        $webServer = new IoServer(
            new HttpServer(
                new WsServer(
                    new WampServer($pusher)
                )
            ),
            $socketServer
        );

        $loop->run();
    }

    protected function getSslParams() : array
    {
        $sslParams = [
            'local_cert' => $this->config->get('webSocketSslCertificateFile'),
            'allow_self_signed' => $this->config->get('webSocketSslAllowSelfSigned', false),
            'verify_peer' => false,
        ];

        if ($this->config->get('webSocketSslCertificatePassphrase')) {
            $sslParams['passphrase'] = $this->config->get('webSocketSslCertificatePassphrase');
        }

        if ($this->config->get('webSocketSslCertificateLocalPrivateKey')) {
            $sslParams['local_pk'] = $this->config->get('webSocketSslCertificateLocalPrivateKey');
        }

        return $sslParams;
    }
}
