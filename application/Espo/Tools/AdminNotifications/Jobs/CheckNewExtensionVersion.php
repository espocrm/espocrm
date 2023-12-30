<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2024 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

namespace Espo\Tools\AdminNotifications\Jobs;

use Espo\Core\Job\JobDataLess;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\Config\ConfigWriter;
use Espo\Entities\Extension;
use Espo\ORM\EntityManager;
use Espo\Tools\AdminNotifications\LatestReleaseDataRequester;

/**
 * Checking for new extension versions.
 */
class CheckNewExtensionVersion implements JobDataLess
{
    private Config $config;
    private ConfigWriter $configWriter;
    private EntityManager $entityManager;
    private LatestReleaseDataRequester $requester;

    public function __construct(
        Config $config,
        ConfigWriter $configWriter,
        EntityManager $entityManager,
        LatestReleaseDataRequester $requester
    ) {
        $this->config = $config;
        $this->configWriter = $configWriter;
        $this->entityManager = $entityManager;
        $this->requester = $requester;
    }

    public function run(): void
    {
        $config = $this->config;

        if (
            !$config->get('adminNotifications') ||
            !$config->get('adminNotificationsNewExtensionVersion')
        ) {
            return;
        }

        $query = $this->entityManager
            ->getQueryBuilder()
            ->select()
            ->from(Extension::ENTITY_TYPE)
            ->select(['id', 'name', 'version', 'checkVersionUrl'])
            ->where([
                'deleted' => false,
                'isInstalled' => true,
            ])
            ->order(['createdAt'])
            ->build();

        $sth = $this->entityManager->getQueryExecutor()->execute($query);

        $latestReleases = [];

        while ($row = $sth->fetch()) {
            $url = !empty($row['checkVersionUrl']) ? $row['checkVersionUrl'] : null;

            $extensionName = $row['name'];

            $latestRelease = $this->requester->request($url, [
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
            $this->configWriter->set('latestExtensionVersions', $latestExtensionVersions);

            $this->configWriter->save();
        }
    }
}
