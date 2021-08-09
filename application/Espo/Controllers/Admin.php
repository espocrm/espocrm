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

namespace Espo\Controllers;

use Espo\Core\Exceptions\Forbidden;

use Espo\Core\Container;
use Espo\Core\DataManager;
use Espo\Core\Api\Request;

use Espo\Core\Utils\Config;
use Espo\Core\Utils\AdminNotificationManager;
use Espo\Core\Utils\SystemRequirements;
use Espo\Core\Utils\ScheduledJob;

use Espo\Core\Upgrades\UpgradeManager;

use Espo\Entities\User;

class Admin
{
    private $container;

    private $config;

    private $user;

    private $adminNotificationManager;

    private $systemRequirements;

    private $scheduledJob;

    private $dataManager;

    public function __construct(
        Container $container,
        Config $config,
        User $user,
        AdminNotificationManager $adminNotificationManager,
        SystemRequirements $systemRequirements,
        ScheduledJob $scheduledJob,
        DataManager $dataManager
    ) {
        $this->container = $container;
        $this->config = $config;
        $this->user = $user;
        $this->adminNotificationManager = $adminNotificationManager;
        $this->systemRequirements = $systemRequirements;
        $this->scheduledJob = $scheduledJob;
        $this->dataManager = $dataManager;

        if (!$this->user->isAdmin()) {
            throw new Forbidden();
        }
    }

    public function postActionRebuild(): bool
    {
        $this->dataManager->rebuild();

        return true;
    }

    public function postActionClearCache(): bool
    {
        $this->dataManager->clearCache();

        return true;
    }

    public function getActionJobs(): array
    {
        return $this->scheduledJob->getAvailableList();
    }

    public function postActionUploadUpgradePackage($params, $data)
    {
        if ($this->config->get('restrictedMode')) {
            if (!$this->user->isSuperAdmin()) {
                throw new Forbidden();
            }
        }

        $upgradeManager = new UpgradeManager($this->container);

        $upgradeId = $upgradeManager->upload($data);
        $manifest = $upgradeManager->getManifest();

        return [
            'id' => $upgradeId,
            'version' => $manifest['version'],
        ];
    }

    public function postActionRunUpgrade(Request $request): bool
    {
        $data = $request->getParsedBody();

        if ($this->config->get('restrictedMode')) {
            if (!$this->user->isSuperAdmin()) {
                throw new Forbidden();
            }
        }

        $upgradeManager = new UpgradeManager($this->container);

        $upgradeManager->install(get_object_vars($data));

        return true;
    }

    public function actionCronMessage(): array
    {
        return $this->scheduledJob->getSetupMessage();
    }

    public function actionAdminNotificationList(): array
    {
        return $this->adminNotificationManager->getNotificationList();
    }

    public function actionSystemRequirementList(): array
    {
        return $this->systemRequirements->getAllRequiredList();
    }
}
