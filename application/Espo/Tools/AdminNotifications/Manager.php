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

namespace Espo\Tools\AdminNotifications;

use Espo\Core\Name\Field;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\Language;
use Espo\Core\Utils\ScheduledJob;
use Espo\Core\Utils\Util;
use Espo\Entities\Extension;
use Espo\ORM\EntityManager;

/**
 * Notifications on the admin panel.
 */
class Manager
{
    public function __construct(
        private EntityManager $entityManager,
        private Config $config,
        private Language $language,
        private ScheduledJob $scheduledJob,
        private Config\SystemConfig $systemConfig,
    ) {}

    /**
     * @return array<int, array{id: string, type: string, message: string}>
     */
    public function getNotificationList(): array
    {
        $notificationList = [];

        if (!$this->config->get('adminNotifications')) {
            return [];
        }

        if (!$this->systemConfig->useCache()) {
            $notificationList[] = [
                'id' => 'cacheIsDisabled',
                'type' => 'cacheIsDisabled',
                'message' => $this->language->translateLabel('cacheIsDisabled', 'messages', 'Admin'),
            ];
        }

        if ($this->config->get('adminNotificationsCronIsNotConfigured')) {
            if ($this->config->get('cronDisabled')) {
                $notificationList[] = [
                    'id' => 'cronIsDisabled',
                    'type' => 'cronIsDisabled',
                    'message' => $this->language->translateLabel('cronIsDisabled', 'messages', 'Admin'),
                ];
            }

            if (!$this->isCronConfigured()) {
                $notificationList[] = [
                    'id' => 'cronIsNotConfigured',
                    'type' => 'cronIsNotConfigured',
                    'message' => $this->language->translateLabel('cronIsNotConfigured', 'messages', 'Admin'),
                ];
            }
        }

        if ($this->config->get('adminNotificationsNewVersion')) {
            $instanceNeedingUpgrade = $this->getInstanceNeedingUpgrade();

            if (!empty($instanceNeedingUpgrade)) {
                $message = $this->language->translateLabel('newVersionIsAvailable', 'messages', 'Admin');

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

        if (!$this->config->get('adminNotificationsExtensionLicenseDisabled')) {
            $notificationList = array_merge(
                $notificationList,
                $this->getExtensionLicenseNotificationList()
            );
        }

        return $notificationList;
    }

    private function isCronConfigured(): bool
    {
        return $this->scheduledJob->isCronConfigured();
    }

    /**
     * @return ?array{currentVersion:string,latestVersion:string}
     */
    private function getInstanceNeedingUpgrade(): ?array
    {
        $latestVersion = $this->config->get('latestVersion');

        if (!isset($latestVersion)) {
            return null;
        }

        $currentVersion = $this->systemConfig->getVersion();

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

    /**
     *
     * @return array<string, array{currentVersion: string, latestVersion: string, extensionName: string}>
     */
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
            ->getRDBRepository(Extension::ENTITY_TYPE)
            ->select(['version'])
            ->where([
                'name' => $extensionName,
                'isInstalled' => true,
            ])
            ->order(Field::CREATED_AT, true)
            ->findOne();

        if (!$extension) {
            return null;
        }

        return $extension->get('version');
    }

    /**
     * @param array<string, string> $data
     */
    private function prepareMessage(string $message, array $data = []): string
    {
        foreach ($data as $name => $value) {
            $message = str_replace('{'.$name.'}', $value, $message);
        }

        return $message;
    }

    /**
     * @return array<int, array{id: string, type: string, message: string}>
     */
    private function getExtensionLicenseNotificationList(): array
    {
        $extensionList = $this->entityManager
            ->getRDBRepositoryByClass(Extension::class)
            ->where([
                'licenseStatus' => [
                    Extension::LICENSE_STATUS_INVALID,
                    Extension::LICENSE_STATUS_EXPIRED,
                    Extension::LICENSE_STATUS_SOFT_EXPIRED,
                ],
            ])
            ->find();

        $list = [];

        foreach ($extensionList as $extension) {
            $message =
                $extension->getLicenseStatusMessage() ??
                $this->getExtensionLicenseMessageLabel($extension);

            if (!$message) {
                continue;
            }

            $message = $this->language->translateLabel($message, 'messages');

            $name = $extension->getName();

            $list[] = [
                'id' => 'newExtensionVersionIsAvailable' . Util::toCamelCase($name, ' ', true),
                'type' => 'newExtensionVersionIsAvailable',
                'message' => $this->prepareMessage($message, ['name' => $name]),
            ];
        }

        return $list;
    }

    private function getExtensionLicenseMessageLabel(Extension $extension): ?string
    {
        $status = $extension->getLicenseStatus();

        if (!$status) {
            return null;
        }

        return 'extensionLicense' . ucfirst(Util::hyphenToCamelCase($status));
    }
}
