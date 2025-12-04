<?php

namespace Espo\Core\Mail\Account;

use DirectoryTree\ImapEngine\Mailbox;
use Espo\Core\InjectableFactory;
use Espo\Core\Mail\Account\Storage\DirectoryTreeStorage;
use Espo\Core\Mail\Account\Storage\Handler;
use Espo\Core\Mail\Account\Storage\Params;

/**
 * @since 9.3.0
 */
class CommonStorageFactory
{
    public function __construct(
        private InjectableFactory $injectableFactory,
    ) {}

    public function create(Params $params): DirectoryTreeStorage
    {
        $handlerClassName = $params->getImapHandlerClassName();

        if ($handlerClassName && $params->getId()) {
            $handler = $this->injectableFactory->create($handlerClassName);

            if ($handler instanceof Handler || method_exists($handler, 'handle')) {
                $params = $handler->handle($params, $params->getId());
            }
        }

        $encryption = match ($params->getSecurity()) {
            Params::SECURITY_SSL => 'ssl',
            Params::SECURITY_START_TLS => 'starttls',
            default => null,
        };

        $authentication = $params->getAuthMechanism() === Params::AUTH_MECHANISM_XOAUTH ?
            'oauth' : 'plain';

        $config = [
            'host' => $params->getHost(),
            'port' => $params->getPort(),
            'username' => $params->getUsername(),
            'password' => $params->getPassword(),
            'encryption' => $encryption,
            'authentication' => $authentication,
        ];

        $mailbox = new Mailbox($config);

        return new DirectoryTreeStorage($mailbox);
    }
}
