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

namespace Espo\Core\HttpClient;

use Espo\Core\HttpClient\Exceptions\ConnectException;
use Espo\Core\HttpClient\Exceptions\NotAllowedInternalHost;
use Espo\Core\HttpClient\Exceptions\TooManyRedirectsException;
use Espo\Core\Utils\Security\UrlCheck;
use GuzzleHttp;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

use const CURLE_OPERATION_TIMEDOUT;

class Client
{
    private const int MAX_REDIRECT_NUMBER = 5;

    /**
     * To be instantiated with the ClientFactory.
     *
     * @internal
     */
    public function __construct(
        private Options $options,
        private UrlCheck $urlCheck,
    ) {}

    /**
     * Send a request. Does not throw exceptions on error responses.
     *
     * @throws TooManyRedirectsException
     * @throws ConnectException
     */
    public function send(RequestInterface $request): ResponseInterface
    {
        $options = [
            'protocols' => array_map(
                fn (Protocol $protocol) => $protocol->value,
                $this->options->redirect->protocols
            ),
            'allow_redirects' => false,
            'http_errors' => false,
        ];

        if ($this->options->redirect->allow) {
            $options['allow_redirects'] = [
                'max' => $this->options->redirect->maxNumber ?? self::MAX_REDIRECT_NUMBER,
                'strict' => $this->options->redirect->strict,
                'protocols' => array_map(
                    fn (Protocol $protocol) => $protocol->value,
                    $this->options->redirect->protocols
                ),
            ];
        }

        if ($this->options->timeout !== null) {
            $options['timeout'] = $this->options->timeout;
        }

        if ($this->options->connectTimeout !== null) {
            $options['connect_timeout'] = $this->options->connectTimeout;
        }

        if ($this->options->internalHostRestriction->restrict) {
            $stack = GuzzleHttp\HandlerStack::create();

            $stack->push(
                GuzzleHttp\Middleware::mapRequest(function (RequestInterface $request) {
                    $url = (string) $request->getUri();

                    $this->checkUrl($url, $this->options->internalHostRestriction->allowed);

                    return $request;
                })
            );

            $options['handler'] = $stack;
        }

        $client = new GuzzleHttp\Client($options);

        try {
            return $client->send($request);
        } catch (GuzzleHttp\Exception\ConnectException $e) {
            $context = $e->getHandlerContext();

            $reason = null;

            if (($context['errno'] ?? 0) === CURLE_OPERATION_TIMEDOUT) {
                $reason = ConnectErrorReason::Timeout;
            }

            throw ConnectException::create(previous: $e, reason: $reason);
        } catch (GuzzleHttp\Exception\TooManyRedirectsException $e) {
            throw new TooManyRedirectsException(previous: $e);
        } catch (GuzzleHttp\Exception\GuzzleException $e) {
            throw new RuntimeException(previous: $e);
        }
    }

    /**
     * @param string[] $allowed
     * @throws NotAllowedInternalHost
     */
    private function checkUrl(string $url, array $allowed): void
    {
        if (
            !Util::matchUrlToAddressList($url, $allowed) &&
            !$this->urlCheck->isNotInternalUrl($url)
        ) {
            throw new NotAllowedInternalHost("Not allowed internal host in '$url'.");
        }
    }
}
