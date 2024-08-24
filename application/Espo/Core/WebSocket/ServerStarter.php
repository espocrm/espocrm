<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2024 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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
use Espo\Core\Utils\Metadata;

use React\EventLoop\Factory as EventLoopFactory;
use React\Socket\Server as SocketServer;
use React\Socket\SecureServer as SocketSecureServer;

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use Ratchet\Wamp\WampServer;

/**
 * Starts a web-socket server.
 */
class ServerStarter
{
    /** @var array<string, array<string, mixed>> */
    private array $categoriesData;
    private ?string $phpExecutablePath;
    private bool $isDebugMode;
    private bool $useSecureServer;
    private string $port;

    public function __construct(
        private Subscriber $subscriber,
        private Config $config,
        Metadata $metadata
    ) {
        $this->categoriesData = $metadata->get(['app', 'webSocket', 'categories'], []);
        $this->phpExecutablePath = $config->get('phpExecutablePath');
        $this->isDebugMode = (bool) $config->get('webSocketDebugMode');
        $this->useSecureServer = (bool) $config->get('webSocketUseSecureServer');

        $port = $this->config->get('webSocketPort');

        if (!$port) {
            $port = $this->useSecureServer ? '8443' : '8080';
        }

        $this->port = $port;
    }

    /**
     * Start a web-socket server.
     */
    public function start(): void
    {
        $loop = EventLoopFactory::create();

        $pusher = new Pusher($this->categoriesData, $this->phpExecutablePath, $this->isDebugMode);

        $this->subscriber->subscribe($pusher, $loop);

        $socketServer = new SocketServer('0.0.0.0:' . $this->port, $loop);

        if ($this->useSecureServer) {
            $sslParams = $this->getSslParams();

            $socketServer = new SocketSecureServer($socketServer, $loop, $sslParams);
        }

        $wsServer = new WsServer(new WampServer($pusher));
        $wsServer->enableKeepAlive($loop, 60);

        new IoServer(
            new HttpServer($wsServer),
            $socketServer
        );

        $loop->run();
    }

    /**
     * @return array<string, mixed>
     */
    protected function getSslParams(): array
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
