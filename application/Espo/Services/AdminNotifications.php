<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2018 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
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

namespace Espo\Services;

class AdminNotifications extends \Espo\Core\Services\Base
{
    public function jobCheckNewVersion($data)
    {
        $config = $this->getConfig();

        if (!$config->get('adminNotifications') || !$config->get('adminNotificationsNewVersion')) {
            return true;
        }

        $latestRelease = $this->getLatestRelease();
        if (!empty($latestRelease['version']) && $config->get('latestVersion') != $latestRelease['version']) {
            $config->set('latestVersion', $latestRelease['version']);
            $config->save();
        }

        return true;
    }

    protected function getLatestRelease($url = null, array $requestData = ['action' => 'latestRelease'])
    {
        if (function_exists('curl_version')) {
            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, $url ? $url : base64_decode('aHR0cHM6Ly9zLmVzcG9jcm0uY29tLw=='));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($requestData));

            $result = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200) {
                $data = json_decode($result, true);
                if (is_array($data)) {
                    return $data;
                }
            }
        }
    }
}
