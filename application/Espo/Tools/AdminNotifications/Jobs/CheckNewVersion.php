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
use Espo\Tools\AdminNotifications\LatestReleaseDataRequester;

/**
 * Checking for a new EspoCRM version.
 */
class CheckNewVersion implements JobDataLess
{
    private Config $config;
    private ConfigWriter $configWriter;
    private LatestReleaseDataRequester $requester;

    public function __construct(
        Config $config,
        ConfigWriter $configWriter,
        LatestReleaseDataRequester $requester
    ) {
        $this->config = $config;
        $this->configWriter = $configWriter;
        $this->requester = $requester;
    }

    public function run(): void
    {
        $config = $this->config;

        if (
            !$config->get('adminNotifications') ||
            !$config->get('adminNotificationsNewVersion')
        ) {
            return;
        }

        $latestRelease = $this->requester->request();

        if ($latestRelease === null) {
            return;
        }

        if (empty($latestRelease['version'])) {
            // @todo Check the logic. WTF?
            $this->configWriter->set('latestVersion', $latestRelease['version']);

            $this->configWriter->save();

            return;
        }

        if ($config->get('latestVersion') != $latestRelease['version']) {
            $this->configWriter->set('latestVersion', $latestRelease['version']);

            if (!empty($latestRelease['notes'])) {
                // @todo Create a notification.
            }

            $this->configWriter->save();

            return;
        }

        if (!empty($latestRelease['notes'])) {
            // @todo Find and modify notification.
        }
    }
}
