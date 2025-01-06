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

namespace Espo\Classes\AddressFormatters;

use Espo\Core\Field\Address;
use Espo\Core\Field\Address\AddressFormatter;

class Formatter2 implements AddressFormatter
{
    public function format(Address $address): string
    {
        $result = '';

        $street = $address->getStreet();
        $city = $address->getCity();
        $country = $address->getCountry();
        $state = $address->getState();
        $postalCode = $address->getPostalCode();

        if ($street) {
            $result .= $street;
        }

        if ($city || $postalCode) {
            if ($result) {
                $result .= "\n";
            }

            if ($postalCode) {
                $result .= $postalCode;
            }

            if ($postalCode && $city) {
                $result .= ' ';
            }

            if ($city) {
                $result .= $city;
            }
        }

        if ($state || $country) {
            if ($result) {
                $result .= "\n";
            }

            if ($state) {
                $result .= $state;
            }

            if ($state && $country) {
                $result .= ' ';
            }

            if ($country) {
                $result .= $country;
            }
        }

        return $result;
    }
}
