<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2017 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

namespace Espo\Core\Utils;

use Espo\Core\Exceptions\NotFound;
use Espo\Core\Utils\Util;

class AdminNotificationManager
{
    private $container;

    private $isConfigChanged = false;

    public function __construct(\Espo\Core\Container $container)
    {
        $this->container = $container;
    }

    protected function getContainer()
    {
        return $this->container;
    }

    protected function getEntityManager()
    {
        return $this->getContainer()->get('entityManager');
    }

    protected function getConfig()
    {
        return $this->getContainer()->get('config');
    }

    protected function getLanaguage()
    {
        return $this->getContainer()->get('language');
    }

    public function getAll()
    {
        $notifications = [];

        //is cron configured
        if (!$this->isCronConfigured()) {
            $notifications[] = array(
                'id' => 'cronNotConfigured',
                'data' => array(
                    'id' => 'cronNotConfigured',
                    'message' => $this->getLanaguage()->translate('cronNotConfigured', 'messages', 'Admin'),
                ),
            );
        }

        //is need upgrade instance
        $neededUpgrade = $this->getInstanceNeededUpgrade();
        if (!empty($neededUpgrade)) {
            $message = $this->getLanaguage()->translate('upgradeInstance', 'messages', 'Admin');
            $notifications[] = array(
                'id' => 'upgradeInstance',
                'data' => array(
                    'id' => 'upgradeInstance',
                    'message' => $this->prepareMessage($message, $neededUpgrade),
                ),
            );
        }

        //are extensions needed upgrade
        $neededUpgrade = $this->getExtensionsNeededUpgrade();
        if (!empty($neededUpgrade)) {
            foreach ($neededUpgrade as $extensionName => $extensionDetails) {
                $message = $this->getLanaguage()->translate('upgradeExtension', 'messages', 'Admin');
                $notifications[] = array(
                    'id' => 'upgradeExtension' . Util::toCamelCase($extensionName, ' ', true),
                    'data' => array(
                        'id' => 'upgradeExtension',
                        'message' => $this->prepareMessage($message, $extensionDetails),
                    ),
                );
            }
        }

        return $notifications;
    }

    protected function isCronConfigured()
    {
        return $this->getContainer()->get('scheduledJob')->isCronConfigured();
    }

    protected function getInstanceNeededUpgrade()
    {
        $config = $this->getConfig();

        $latestVersion = $config->get('latestRelease');
        if (isset($latestVersion)) {
            $currentVersion = $config->get('version');

            if (version_compare($latestVersion, $currentVersion, '>')) {
                return array(
                    'currentVersion' => $currentVersion,
                    'latestVersion' => $latestVersion,
                );
            }
        }
    }

    protected function getExtensionsNeededUpgrade()
    {
        $config = $this->getConfig();

        $extensions = [];

        $latestExtensionReleases = $config->get('latestExtensionReleases');
        if (!empty($latestExtensionReleases) && is_array($latestExtensionReleases)) {
            foreach ($latestExtensionReleases as $extensionName => $extensionLatestVersion) {

                $currentVersion = $this->getExtensionLatestVersion($extensionName);
                if (isset($currentVersion) && version_compare($extensionLatestVersion, $currentVersion, '>')) {
                    $extensions[$extensionName] = array(
                        'currentVersion' => $currentVersion,
                        'latestVersion' => $extensionLatestVersion,
                        'extensionName' => $extensionName,
                    );
                }
            }
        }

        return $extensions;
    }

    protected function getExtensionLatestVersion($extensionName)
    {
        $pdo = $this->getEntityManager()->getPDO();

        $query = "
            SELECT version FROM extension
            WHERE name='". $extensionName ."'
            AND deleted=0
            AND is_installed = 1
            ORDER BY created_at DESC
        ";
        $sth = $pdo->prepare($query);
        $sth->execute();

        $row = $sth->fetch(\PDO::FETCH_ASSOC);
        if (isset($row['version'])) {
            return $row['version'];
        }
    }

    /**
     * Set EspoCRM latest release
     *
     * @param string $version
     */
    public function setRelease($version)
    {
        $config = $this->getConfig();
        $config->set('latestRelease', $version);
        $this->isConfigChanged = true;
    }

    /**
     * Set latest release of an extension
     *
     * @param string $extensionName
     * @param string $version
     */
    public function setExtensionRelease($extensionName, $version)
    {
        $config = $this->getConfig();

        $latestExtensionReleases = $config->get('latestExtensionReleases', []);
        $latestExtensionReleases[$extensionName] = $version;
        $config->set('latestExtensionReleases', $latestExtensionReleases);
        $this->isConfigChanged = true;
    }

    /**
     * Save releases after setRelease() and setExtensionRelease()
     *
     * @return boolean
     */
    public function saveReleases()
    {
        if ($this->isConfigChanged) {
            $this->isConfigChanged = false;
            return $this->getConfig()->save();
        }
    }

    /**
     * Create EspoCRM notification for a user
     *
     * @param  string $message
     * @param  string $userId
     *
     * @return void
     */
    public function createNotification($message, $userId = '1')
    {
        $notification = $this->getEntityManager()->getEntity('Notification');
        $notification->set(array(
            'type' => 'message',
            'data' => array(
                'userId' => $this->getUser()->id,
                'userName' => $this->getUser()->get('name')
            ),
            'userId' => $user->id,
            'message' => $actionData['messageTemplate']
        ));
        $this->getEntityManager()->saveEntity($notification);
    }

    /**
     * Replance variable with values
     *
     * @param  string $message
     * @param  array  $data
     *
     * @return string
     */
    protected function prepareMessage($message, array $data = array())
    {
        foreach ($data as $name => $value) {
            $message = str_replace('{'.$name.'}', $value, $message);
        }

        return $message;
    }
}