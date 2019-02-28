<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

namespace Espo\Services;

class AdminNotifications extends \Espo\Core\Services\Base
{
    /**
     * Job for checking a new version of EspoCRM
     *
     * @param  object $data
     *
     * @return boolean
     */
    public function jobCheckNewVersion($data)
    {
        $config = $this->getConfig();

        if (!$config->get('adminNotifications') || !$config->get('adminNotificationsNewVersion')) {
            return true;
        }

        $latestRelease = $this->getLatestRelease();
        if (empty($latestRelease['version'])) {
            $config->set('latestVersion', $latestRelease['version']);
            $config->save();
            return true;
        }

        if ($config->get('latestVersion') != $latestRelease['version']) {
            $config->set('latestVersion', $latestRelease['version']);

            if (!empty($latestRelease['notes'])) {
                //todo: create notification
            }

            $config->save();
            return true;
        }

        if (!empty($latestRelease['notes'])) {
            //todo: find and modify notification
        }

        return true;
    }

    /**
     * Job for cheking a new version of installed extensions
     *
     * @param  object $data
     *
     * @return boolean
     */
    public function jobCheckNewExtensionVersion($data)
    {
        $config = $this->getConfig();

        if (!$config->get('adminNotifications') || !$config->get('adminNotificationsNewExtensionVersion')) {
            return true;
        }

        $pdo = $this->getEntityManager()->getPDO();

        $query = "
            SELECT id, name, version, check_version_url as url
            FROM extension
            WHERE deleted = 0
            AND is_installed = 1
            ORDER BY created_at
        ";

        $sth = $pdo->prepare($query);
        $sth->execute();

        $rowList = $sth->fetchAll(\PDO::FETCH_ASSOC);

        $latestReleases = [];
        foreach ($rowList as $row) {

            $url = !empty($row['url']) ? $row['url'] : null;
            $extensionName = $row['name'];

            $latestRelease = $this->getLatestRelease($url, [
                'name' => $extensionName,
            ]);

            if (!empty($latestRelease) && !isset($latestRelease['error'])) {
                $latestReleases[$extensionName] = $latestRelease;
            }
        }

        $latestExtensionVersions = $config->get('latestExtensionVersions', []);

        $save = false;
        foreach ($latestReleases as $extensionName => $extensionData) {

            if (empty($latestExtensionVersions[$extensionName])) {
                $latestExtensionVersions[$extensionName] = $extensionData['version'];
                $save = true;
                continue;
            }

            if ($latestExtensionVersions[$extensionName] != $extensionData['version']) {
                $latestExtensionVersions[$extensionName] = $extensionData['version'];

                if (!empty($extensionData['notes'])) {
                    //todo: create notification
                }

                $save = true;
                continue;
            }

            if (!empty($extensionData['notes'])) {
                //todo: find and modify notification
            }
        }

        if ($save) {
            $config->set('latestExtensionVersions', $latestExtensionVersions);
            $config->save();
        }

        return true;
    }

    /**
     * Get latest version
     *
     * @param  string $url
     * @param  array  $requestData
     *
     * @return array|null
     */
    protected function getLatestRelease($url = null, array $requestData = [], $urlPath = 'release/latest')
    {
        if (function_exists('curl_version')) {
            $ch = curl_init();

            $requestUrl = $url ? trim($url) : base64_decode('aHR0cHM6Ly9zLmVzcG9jcm0uY29tLw==');
            $requestUrl = (substr($requestUrl, -1) == '/') ? $requestUrl : $requestUrl . '/';
            $requestUrl .= empty($requestData) ? $urlPath . '/' : $urlPath . '/?' . http_build_query($requestData);

            curl_setopt($ch, CURLOPT_URL, $requestUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);

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
