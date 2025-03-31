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

namespace Espo\Core\Mail\Sender;

use Espo\Core\Mail\SmtpParams;
use Espo\Core\Utils\Config;
use RuntimeException;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mailer\Transport\Smtp\Auth\CramMd5Authenticator;
use Symfony\Component\Mailer\Transport\Smtp\Auth\LoginAuthenticator;
use Symfony\Component\Mailer\Transport\Smtp\Auth\PlainAuthenticator;
use Symfony\Component\Mailer\Transport\Smtp\Auth\XOAuth2Authenticator;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransportFactory;
use Symfony\Component\Mailer\Transport\TransportInterface;

class DefaultTransportPreparator implements TransportPreparator
{
    public function __construct(
        private Config $config,
    ) {}

    public function prepare(SmtpParams $smtpParams): TransportInterface
    {
        $localHostName = $this->config->get('smtpLocalHostName', gethostname());

        // 'SSL' is treated as implicit SSL/TLS. 'TLS' is treated as STARTTLS.
        // STARTTLS is the most common method.
        $scheme = $smtpParams->getSecurity() === 'SSL' ? 'smtps' : 'smtp';

        if ($smtpParams->getSecurity() === 'TLS' && !defined('OPENSSL_VERSION_NUMBER')) {
            throw new RuntimeException("OpenSSL is not available.");
        }

        // @todo Use `auto_tls=false` if no security when Symfony v7.1 is installed.
        // @todo If starttls, it should be enforced.

        $transport = (new EsmtpTransportFactory())
            ->create(
                new Dsn(
                    scheme: $scheme,
                    host: $smtpParams->getServer(),
                    port: $smtpParams->getPort(),
                )
            );

        if (!$transport instanceof EsmtpTransport) {
            throw new RuntimeException();
        }

        $transport->setLocalDomain($localHostName);

        $authMechanism = null;

        // @todo For xoauth, set authMechanism, username, password in handlers.
        $connectionOptions = $smtpParams->getConnectionOptions() ?? [];
        $authString = $connectionOptions['authString'] ?? null;

        if ($authString) {
            $decodedAuthString = base64_decode($authString);

            /** @noinspection RegExpRedundantEscape */
            if (preg_match("/user=(.*?)\\\1auth=Bearer (.*?)\\\1\\\1/", $decodedAuthString, $matches) !== false) {
                $username = $matches[1];
                $token = $matches[2];

                $transport->setUsername($username);
                $transport->setPassword($token);
            }

            $authMechanism = SmtpParams::AUTH_MECHANISM_XOAUTH;
        } else if ($smtpParams->useAuth()) {
            $authMechanism = $smtpParams->getAuthMechanism() ?: SmtpParams::AUTH_MECHANISM_LOGIN;

            $transport->setUsername($smtpParams->getUsername() ?? '');
            $transport->setPassword($smtpParams->getPassword() ?? '');
        }

        if ($authMechanism === SmtpParams::AUTH_MECHANISM_LOGIN) {
            $transport->setAuthenticators([new LoginAuthenticator()]);
        } else if ($authMechanism === SmtpParams::AUTH_MECHANISM_CRAMMD5) {
            $transport->setAuthenticators([new CramMd5Authenticator()]);
        } else if ($authMechanism === SmtpParams::AUTH_MECHANISM_PLAIN) {
            $transport->setAuthenticators([new PlainAuthenticator()]);
        } else if ($authMechanism === SmtpParams::AUTH_MECHANISM_XOAUTH) {
            $transport->setAuthenticators([new XOAuth2Authenticator()]);
        }

        return $transport;
    }
}
