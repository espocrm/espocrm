<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2015 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

use \Espo\Core\Exceptions\NotFound;
use \Espo\Core\Exceptions\Error;
use \Espo\Core\Exceptions\Forbidden;
use \Espo\Core\Exceptions\BadRequest;

class Admin extends \Espo\Core\Controllers\Base
{
    protected function checkControllerAccess()
    {
        if (!$this->getUser()->isAdmin()) {
            throw new Forbidden();
        }
    }

    public function postActionRebuild($params, $data, $request)
    {
        if (!$request->isPost()) {
            throw new BadRequest();
        }
        $result = $this->getContainer()->get('dataManager')->rebuild();

        return $result;
    }

    public function postActionClearCache($params, $data)
    {
        $result = $this->getContainer()->get('dataManager')->clearCache();
        return $result;
    }

    public function actionJobs()
    {
        $scheduledJob = $this->getContainer()->get('scheduledJob');

        return $scheduledJob->getAllNamesOnly();
    }

    public function postActionUploadUpgradePackage($params, $data)
    {
        if ($this->getConfig()->get('restrictedMode')) {
            if (!$this->getUser()->get('isSuperAdmin')) {
                throw new Forbidden();
            }
        }
        $upgradeManager = new \Espo\Core\UpgradeManager($this->getContainer());

        $upgradeId = $upgradeManager->upload($data);
        $manifest = $upgradeManager->getManifest();

        return array(
            'id' => $upgradeId,
            'version' => $manifest['version'],
        );
    }

    public function postActionRunUpgrade($params, $data)
    {
        if ($this->getConfig()->get('restrictedMode')) {
            if (!$this->getUser()->get('isSuperAdmin')) {
                throw new Forbidden();
            }
        }

        $upgradeManager = new \Espo\Core\UpgradeManager($this->getContainer());
        $upgradeManager->install($data);

        return true;
    }

    public function actionCronMessage($params, $data)
    {
        return $this->getContainer()->get('scheduledJob')->getSetupMessage();
    }

}

