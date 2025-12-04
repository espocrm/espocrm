<?php

namespace Espo\Core\Mail\Account;

use Espo\Core\InjectableFactory;
use Espo\Core\Mail\Account\Storage\Handler;
use Espo\Core\Mail\Account\Storage\LaminasStorage;
use Espo\Core\Mail\Account\Storage\Params;
use Espo\Core\Mail\Exceptions\ImapError;
use Espo\Core\Mail\Mail\Storage\Imap;
use Espo\Core\Utils\Log;
use Laminas\Mail\Protocol\Exception\ExceptionInterface;
use Laminas\Mail\Protocol\Exception\RuntimeException as ProtocolRuntimeException;
use Laminas\Mail\Protocol\Imap as ImapProtocol;
use Laminas\Mail\Storage\Exception\InvalidArgumentException;
use Laminas\Mail\Storage\Exception\RuntimeException as LaminasRuntimeException;
use RuntimeException;

/**
 * @since 9.3.0
 */
class CommonStorageFactory
{
    public function __construct(
        private InjectableFactory $injectableFactory,
        private Log $log,
    ) {}

    /**
     * @throws ImapError
     */
    public function create(Params $params): LaminasStorage
    {
        $handlerClassName = $params->getImapHandlerClassName();
        $handler = null;
        $isHandled = false;

        if ($handlerClassName && $params->getId()) {
            $handler = $this->injectableFactory->create($handlerClassName);

            if ($handler instanceof Handler || method_exists($handler, 'handle')) {
                $params = $handler->handle($params, $params->getId());

                $isHandled = true;
            }
        }

        if ($params->getAuthMechanism() === Params::AUTH_MECHANISM_XOAUTH) {
            $imapParams = $this->prepareXoauthProtocol($params);
        } else {
            $rawParams = [
                'host' => $params->getHost(),
                'port' => $params->getPort(),
                'username' => $params->getUsername(),
                'password' => $params->getPassword(),
                'id' => $params->getId(),
            ];

            if ($params->getSecurity()) {
                $rawParams['security'] = $params->getSecurity();
            }

            $imapParams = null;

            // For bc.
            if (!$isHandled && $handler && $params->getId() && method_exists($handler, 'prepareProtocol')) {
                $imapParams = $handler->prepareProtocol($params->getId(), $rawParams);
            }

            if (!$imapParams) {
                $imapParams = [
                    'host' => $rawParams['host'],
                    'port' => $rawParams['port'],
                    'user' => $rawParams['username'],
                    'password' => $rawParams['password'],
                ];

                if (!empty($rawParams['security'])) {
                    $imapParams['ssl'] = $rawParams['security'];
                }
            }
        }

        try {
            $storage = new Imap($imapParams);
        } catch (LaminasRuntimeException|InvalidArgumentException|ProtocolRuntimeException $e) {
            throw new ImapError($e->getMessage(), 0, $e);
        }

        return new LaminasStorage($storage);
    }

    private function prepareXoauthProtocol(Params $params): ?ImapProtocol
    {
        $username = $params->getUsername();
        $accessToken = $params->getPassword();
        $host = $params->getHost() ?? throw new RuntimeException("No IMAP host.");
        $port = $params->getPort();
        $ssl = $params->getSecurity() ?: false;

        try {
            $protocol = new ImapProtocol($host, $port, $ssl);
        } catch (ExceptionInterface $e) {
            throw new RuntimeException($e->getMessage(), previous: $e);
        }

        $authString = base64_encode("user=$username\1auth=Bearer $accessToken\1\1");

        $authenticateParams = ['XOAUTH2', $authString];
        $protocol->sendRequest('AUTHENTICATE', $authenticateParams);

        $i = 0;

        while (true) {
            if ($i === 10) {
                return null;
            }

            $response = '';
            $isPlus = $protocol->readLine($response, '+', true);

            if ($isPlus) {
                $this->log->warning("Imap XOauth: Extra server challenge: " . var_export($response, true));

                $protocol->sendRequest('');
            } else {
                if (
                    is_string($response) &&
                    (preg_match('/^NO /i', $response) || preg_match('/^BAD /i', $response))
                ) {
                    $this->log->error("Imap XOauth: Failure: " . var_export($response, true));

                    return null;
                }

                if (is_string($response) && preg_match("/^OK /i", $response)) {
                    break;
                }
            }

            $i++;
        }

        return $protocol;
    }
}
