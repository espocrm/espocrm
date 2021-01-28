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

namespace Espo\Core\Authentication\TwoFactor\User;

use Espo\Entities\UserData;

use Espo\Core\{
    Authentication\TwoFactor\Utils\Totp as TotpUtils,
    Utils\Config,
};

use StdClass;

class Totp implements CodeVerify
{
    protected $entityManager;
    protected $totp;

    public function __construct(TotpUtils $totp, Config $config)
    {
        $this->totp = $totp;
        $this->config = $config;
    }

    public function generateData(UserData $userData, StdClass $data, string $userName) : StdClass
    {
        $secret = $this->totp->createSecret();

        $label = rawurlencode($this->config->get('applicationName')) . ':' . rawurlencode($userName);

        return (object) [
            'auth2FATotpSecret' => $secret,
            'label' => $label,
        ];
    }

    public function verify(UserData $userData, string $code) : bool
    {
        if (!$code) {
            return false;
        }

        $codeModified = str_replace(' ', '', trim($code));

        $secret = $userData->get('auth2FATotpSecret');

        return $this->totp->verifyCode($secret, $codeModified);
    }
}
