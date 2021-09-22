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

namespace Espo\Classes\TemplateHelpers;

use Espo\Core\Htmlizer\Helper;
use Espo\Core\Htmlizer\Helper\Data;
use Espo\Core\Htmlizer\Helper\Result;

use Espo\Core\Utils\Metadata;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\Log;

class GoogleMaps implements Helper
{
    private const DEFAULT_SIZE = '400x400';

    private $metadata;

    private $config;

    private $log;

    public function __construct(
        Metadata $metadata,
        Config $config,
        Log $log
    ) {
        $this->metadata = $metadata;
        $this->config = $config;
        $this->log = $log;
    }

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
        }
        else {
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
            'format=' . $format .
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

        $src = '@' . base64_encode($image);

        $tag = "<img src=\"{$src}\" width=\"{$width}\" height=\"{$height}\">";

        return Result::createSafeString($tag);
    }

    /**
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

        curl_setopt($c, \CURLOPT_URL, $url);
        curl_setopt($c, \CURLOPT_HTTPHEADER, $headers);
        curl_setopt($c, \CURLOPT_HEADER, 0);
        curl_setopt($c, \CURLOPT_USERAGENT, $agent);
        curl_setopt($c, \CURLOPT_TIMEOUT, 10);
        curl_setopt($c, \CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($c, \CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($c, \CURLOPT_BINARYTRANSFER, 1);

        $raw = curl_exec($c);

        curl_close($c);

        return $raw;
    }
}
