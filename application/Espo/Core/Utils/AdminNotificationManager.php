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

namespace Espo\Core\Utils;

use Espo\Core\{
    ORM\EntityManager,
    Utils\Config,
    Utils\Language,
    Utils\ScheduledJob,
    Utils\Util,
};

/**
 * Notifications on the admin panel.
 */
class AdminNotificationManager
{
    private $entityManager;

    private $config;

    private $language;

    private $scheduledJob;

    public function __construct(
        EntityManager $entityManager,
        Config $config,
        Language $language,
        ScheduledJob $scheduledJob
    ) {
        $this->entityManager = $entityManager;
        $this->config = $config;
        $this->language = $language;
        $this->scheduledJob = $scheduledJob;
    }

    public function getNotificationList(): array
    {
        $notificationList = [];

        if (!$this->config->get('adminNotifications')) {
            return [];
        }

        if ($this->config->get('adminNotificationsCronIsNotConfigured')) {
            if (!$this->isCronConfigured()) {
                $notificationList[] = [
                    'id' => 'cronIsNotConfigured',
                    'type' => 'cronIsNotConfigured',
                    'message' => $this->language->translate('cronIsNotConfigured', 'messages', 'Admin'),
                ];
            }
        }

        if ($this->config->get('adminNotificationsNewVersion')) {
            $instanceNeedingUpgrade = $this->getInstanceNeedingUpgrade();

            if (!empty($instanceNeedingUpgrade)) {
                $message = $this->language->translate('newVersionIsAvailable', 'messages', 'Admin');

                $notificationList[] = [
                    'id' => 'newVersionIsAvailable',
                    'type' => 'newVersionIsAvailable',
                    'message' => $this->prepareMessage($message, $instanceNeedingUpgrade),
                ];
            }
        }

        if ($this->config->get('adminNotificationsNewExtensionVersion')) {
            $extensionsNeedingUpgrade = $this->getExtensionsNeedingUpgrade();

            foreach ($extensionsNeedingUpgrade as $extensionName => $extensionDetails) {
                $label = 'new' . Util::toCamelCase($extensionName, ' ', true) . 'VersionIsAvailable';

                $message = $this->language->get(['Admin', 'messages', $label]);

                if (!$message) {
                    $message = $this->language
                        ->translate('newExtensionVersionIsAvailable', 'messages', 'Admin');
                }

                $notificationList[] = [
                    'id' => 'newExtensionVersionIsAvailable' . Util::toCamelCase($extensionName, ' ', true),
                    'type' => 'newExtensionVersionIsAvailable',
                    'message' => $this->prepareMessage($message, $extensionDetails)
                ];
            }
        }

        return $notificationList;
    }

    private function isCronConfigured(): bool
    {
        return $this->scheduledJob->isCronConfigured();
    }

    private function getInstanceNeedingUpgrade(): ?array
    {
        $latestVersion = $this->config->get('latestVersion');

        if (!isset($latestVersion)) {
            return null;
        }

        $currentVersion = $this->config->get('version');

        if ($currentVersion === 'dev') {
            return null;
        }

        if (version_compare($latestVersion, $currentVersion, '>')) {
            return [
                'currentVersion' => $currentVersion,
                'latestVersion' => $latestVersion,
            ];
        }

        return null;
    }

    private function getExtensionsNeedingUpgrade(): array
    {
        $extensions = [];

        $latestExtensionVersions = $this->config->get('latestExtensionVersions');

        if (empty($latestExtensionVersions) || !is_array($latestExtensionVersions)) {
            return [];
        }

        foreach ($latestExtensionVersions as $extensionName => $extensionLatestVersion) {
            $currentVersion = $this->getExtensionLatestInstalledVersion($extensionName);

            if (isset($currentVersion) && version_compare($extensionLatestVersion, $currentVersion, '>')) {
                $extensions[$extensionName] = [
                    'currentVersion' => $currentVersion,
                    'latestVersion' => $extensionLatestVersion,
                    'extensionName' => $extensionName,
                ];
            }
        }

        return $extensions;
    }

    private function getExtensionLatestInstalledVersion(string $extensionName): ?string
    {
        $extension = $this->entityManager
            ->getRDBRepository('Extension')
            ->select(['version'])
            ->where([
                'name' => $extensionName,
                'isInstalled' => true,
            ])
            ->order('createdAt', true)
            ->findOne();

        if (!$extension) {
            return null;
        }

        return $extension->get('version');
    }

    private function prepareMessage(string $message, array $data = []): string
    {
        foreach ($data as $name => $value) {
            $message = str_replace('{'.$name.'}', $value, $message);
        }

        return $message;
    }
}
