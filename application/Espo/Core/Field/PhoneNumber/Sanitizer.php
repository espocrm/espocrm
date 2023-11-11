<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2023 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

namespace Espo\Core\Field\PhoneNumber;

use Brick\PhoneNumber\PhoneNumber;
use Brick\PhoneNumber\PhoneNumberParseException;
use Espo\Core\Utils\Config;

class Sanitizer
{
    public function __construct(
        private Config $config
    ) {}

    public function sanitize(string $value, ?string $countryCode): string
    {
        $value = trim($value);

        if (str_starts_with($value, '+')) {
            if ($this->config->get('phoneNumberInternational')) {
                return $this->parsePhoneNumber($value, null);
            }

            return $value;
        }

        if (!$countryCode) {
            return $value;
        }

        $code = strtoupper($countryCode);

        return $this->parsePhoneNumber($value, $code);
    }

    private function parsePhoneNumber(string $value, ?string $countryCode): string
    {
        try {
            $number = PhoneNumber::parse($value, $countryCode);

            return (string) $number;
        }
        catch (PhoneNumberParseException) {
            return $value;
        }
    }
}
