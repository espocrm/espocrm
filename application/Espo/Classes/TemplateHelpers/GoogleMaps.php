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

class GoogleMaps
{
    public static function image()
    {
        $args = func_get_args();
        $context = $args[count($args) - 1];
        $hash = $context['hash'];
        $data = $context['data']['root'];

        $em = $data['__entityManager'];
        $metadata = $data['__metadata'];
        $config = $data['__config'];

        $entityType = $data['__entityType'];

        $field = $hash['field'] ?? null;

        $size = $hash['size'] ?? '400x400';
        $zoom = $hash['zoom'] ?? null;
        $language = $hash['language'] ?? $config->get('language');

        if (strpos($size, 'x') === false) {
            $size = $size .'x' . $size;
        }

        if ($field && $metadata->get(['entityDefs', $entityType, 'fields', $field, 'type']) !== 'address') {
            $GLOBALS['log']->warning("Template helper _googleMapsImage: Specified field is not of address type.");
            return null;
        }

        if (
            !$field &&
            !array_key_exists('street', $hash) &&
            !array_key_exists('city', $hash) &&
            !array_key_exists('country', $hash) &&
            !array_key_exists('state', $hash) &&
            !array_key_exists('postalCode', $hash)
        ) {
            $field = ($entityType === 'Account') ? 'billingAddress' : 'address';
        }

        if ($field) {
            $street = $data[$field . 'Street'] ?? null;
            $city = $data[$field . 'City'] ?? null;
            $country = $data[$field . 'Country'] ?? null;
            $state = $data[$field . 'State'] ?? null;
            $postalCode = $data[$field . 'postalCode'] ?? null;
        } else {
            $street = $hash['street'] ?? null;
            $city = $hash['city'] ?? null;
            $country = $hash['country'] ?? null;
            $state = $hash['state'] ?? null;
            $postalCode = $hash['postalCode'] ?? null;
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
            } else {
                if ($address) {
                    $address .= ', ';
                }
            }
            $address .= $postalCode;
        }
        if ($country) {
            if ($address != '') {
                $address .= ', ';
            }
            $address .= $country;
        }

        $address = urlencode($address);

        $apiKey = $config->get('googleMapsApiKey');

        if (!$apiKey) {
            $GLOBALS['log']->error("Template helper _googleMapsImage: No Google Maps API key.");
            return null;
        }

        if (!$address) {
            $GLOBALS['log']->debug("Template helper _googleMapsImage: No address to display.");
            return null;
        }

        $format = 'jpg;';

        $url = "https://maps.googleapis.com/maps/api/staticmap?" .
            'center=' . $address .
            'format=' . $format .
            '&size=' . $size .
            '&key=' . $apiKey;

        if ($zoom) {
            $url .= '&zoom=' . $zoom;
        }
        if ($language) {
            $url .= '&language=' . $language;
        }

        $GLOBALS['log']->debug("Template helper _googleMapsImage: URL: {$url}.");

        $image = \Espo\Classes\TemplateHelpers\GoogleMaps::getImage($url);

        if (!$image) {
            return null;
        }

        $filePath = tempnam(sys_get_temp_dir(), 'google_maps_image');
        file_put_contents($filePath, $image);

        list($width, $height) = explode('x', $size);

        $tag = "<img src=\"{$filePath}\" width=\"{$width}\" height=\"{$height}\">";

        return new LightnCandy\SafeString($tag);
    }

    public static function getImage(string $url)
    {
        $headers = [];
        $headers[] = 'Accept: image/jpeg, image/pjpeg';
        $headers[] = 'Connection: Keep-Alive';

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
