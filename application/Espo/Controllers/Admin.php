<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014  Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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
 ************************************************************************/
namespace Espo\Controllers;

use Espo\Core\Controllers\Base;
use Espo\Core\Cron\ScheduledJob;
use Espo\Core\DataManager;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\UpgradeManager;

class Admin extends
    Base
{

    public function actionRebuild($params, $data)
    {
        /**
         * @var DataManager $dataManager
         */
        $dataManager = $this->getContainer()->get('dataManager');
        $result = $dataManager->rebuild();
        return $result;
    }

    public function actionClearCache($params, $data)
    {
        /**
         * @var DataManager $dataManager
         */
        $dataManager = $this->getContainer()->get('dataManager');
        $result = $dataManager->clearCache();
        return $result;
    }

    public function actionJobs()
    {
        /**
         * @var ScheduledJob $scheduledJob
         */
        $scheduledJob = $this->getContainer()->get('scheduledJob');
        return $scheduledJob->getAllNamesOnly();
    }

    public function actionUploadUpgradePackage($params, $data)
    {
        $upgradeManager = new UpgradeManager($this->getContainer());
        $upgradeId = $upgradeManager->upload($data);
        $manifest = $upgradeManager->getManifest();
        return array(
            'id' => $upgradeId,
            'version' => $manifest['version'],
        );
    }

    public function actionRunUpgrade($params, $data)
    {
        $upgradeManager = new UpgradeManager($this->getContainer());
        $upgradeManager->install($data['id']);
        return true;
    }

    public function actionCronMessage($params, $data)
    {
        /**
         * @var ScheduledJob $scheduledJob
         */
        $scheduledJob = $this->getContainer()->get('scheduledJob');
        return $scheduledJob->getSetupMessage();
    }

    protected function checkControllerAccess()
    {
        if (!$this->getUser()->isAdmin()) {
            throw new Forbidden();
        }
    }
}

