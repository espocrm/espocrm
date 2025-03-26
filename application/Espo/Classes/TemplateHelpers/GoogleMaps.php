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

namespace Espo\Classes\TemplateHelpers;

use Espo\Core\Htmlizer\Helper;
use Espo\Core\Htmlizer\Helper\Data;
use Espo\Core\Htmlizer\Helper\Result;
use Espo\Core\Utils\Metadata;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\Log;
use const CURLOPT_FOLLOWLOCATION;
use const CURLOPT_HEADER;
use const CURLOPT_HTTPHEADER;
use const CURLOPT_RETURNTRANSFER;
use const CURLOPT_TIMEOUT;
use const CURLOPT_URL;
use const CURLOPT_USERAGENT;

class GoogleMaps implements Helper
{
    private const DEFAULT_SIZE = '400x400';

    public function __construct(
        private Metadata $metadata,
        private Config $config,
        private Log $log,
    ) {}

    public function render(Data $data): Result
    {
        $rootContext = $data->getRootContext();

        $entityType = $rootContext['__entityType'];

        $field = $data->getOption('field');
        $size = $data->getOption('size') ?? self::DEFAULT_SIZE;
        $zoom = $data->getOption('zoom');
        $language = $data->getOption('language') ?? $this->config->get('language');

        if (strpos($size, 'x') === false) {
            $size = $size . 'x' . $size;
        }

        if ($field && $this->metadata->get(['entityDefs', $entityType, 'fields', $field, 'type']) !== 'address') {
            $this->log->warning("Template helper _googleMapsImage: Specified field is not of address type.");

            return Result::createEmpty();
        }

        if (
            !$field &&
            !$data->hasOption('street') &&
            !$data->hasOption('city') &&
            !$data->hasOption('country') &&
            !$data->hasOption('state') &&
            !$data->hasOption('postalCode')
        ) {
            $field = ($entityType === 'Account') ? 'billingAddress' : 'address';
        }

        if ($field) {
            $street = $rootContext[$field . 'Street'] ?? null;
            $city = $rootContext[$field . 'City'] ?? null;
            $country = $rootContext[$field . 'Country'] ?? null;
            $state = $rootContext[$field . 'State'] ?? null;
            $postalCode = $rootContext[$field . 'postalCode'] ?? null;
        } else {
            $street = $data->getOption('street');
            $city = $data->getOption('city');
            $country = $data->getOption('country');
            $state = $data->getOption('state');
            $postalCode = $data->getOption('postalCode');
        }

        $address = '';

        if ($street) {
            $address .= $street;
        }

        if ($city) {
            if ($address != '') {
                $address .= ', ';
            }

            $address .= $city;
        }

        if ($state) {
            if ($address != '') {
                $address .= ', ';
            }

            $address .= $state;
        }

        if ($postalCode) {
            if ($state || $city) {
                $address .= ' ';
            }
            else  if ($address) {
                $address .= ', ';
            }

            $address .= $postalCode;
        }

        if ($country) {
            if ($address != '') {
                $address .= ', ';
            }

            $address .= $country;
        }

        $apiKey = $this->config->get('googleMapsApiKey');

        if (!$apiKey) {
            $this->log->error("Template helper _googleMapsImage: No Google Maps API key.");

            return Result::createEmpty();
        }

        $addressEncoded = urlencode($address);

        if (!$addressEncoded) {
            $this->log->debug("Template helper _googleMapsImage: No address to display.");

            return Result::createEmpty();
        }

        $format = 'jpg;';

        $url = "https://maps.googleapis.com/maps/api/staticmap?" .
            'center=' . $addressEncoded .
            '&format=' . $format .
            '&size=' . $size .
            '&key=' . $apiKey;

        if ($zoom) {
            $url .= '&zoom=' . $zoom;
        }

        if ($language) {
            $url .= '&language=' . $language;
        }

        $this->log->debug("Template helper _googleMapsImage: URL: {$url}.");

        $image = $this->getImage($url);

        if (!$image) {
            return Result::createEmpty();
        }

        list($width, $height) = explode('x', $size);

        $src = '@' . base64_encode($image); /** @phpstan-ignore-line */

        $tag = "<img src=\"{$src}\" width=\"{$width}\" height=\"{$height}\">";

        return Result::createSafeString($tag);
    }

    /**
     * @param non-empty-string $url
     * @return string|bool
     */
    private function getImage(string $url)
    {
        $headers = [
            'Accept: image/jpeg, image/pjpeg',
            'Connection: Keep-Alive',
        ];

        $agent = 'Mozilla/5.0';

        $c = curl_init();

        curl_setopt($c, CURLOPT_URL, $url);
        curl_setopt($c, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($c, CURLOPT_HEADER, false);
        curl_setopt($c, CURLOPT_USERAGENT, $agent);
        curl_setopt($c, CURLOPT_TIMEOUT, 10);
        curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($c, CURLOPT_FOLLOWLOCATION, true);

        $raw = curl_exec($c);

        curl_close($c);

        return $raw;
    }
}
