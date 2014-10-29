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
use Espo\Core\DataManager;
use Espo\Core\Exceptions\Error;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\NotFound;
use Espo\Core\Utils as Utils;

class Layout extends
    Base
{

    public function actionRead($params, $data)
    {
        /**
         * @var Utils\Layout $layout
         */
        $layout = $this->getContainer()->get('layout');
        $data = $layout->get($params['scope'], $params['name']);
        if (empty($data)) {
            throw new NotFound("Layout " . $params['scope'] . ":" . $params['name'] . ' is not found');
        }
        return $data;
    }

    public function actionPatch($params, $data)
    {
        return $this->actionUpdate($params, $data);
    }

    public function actionUpdate($params, $data)
    {
        /**
         * @var Utils\Layout $layout
         * @var DataManager  $dataManager
         */
        if (!$this->getUser()->isAdmin()) {
            throw new Forbidden();
        }
        $result = $this->getContainer()->get('layout')->set($data, $params['scope'], $params['name']);
        if ($result === false) {
            throw new Error("Error while saving layout");
        }
        $dataManager = $this->getContainer()->get('dataManager');
        $dataManager->updateCacheTimestamp();
        $layout = $this->getContainer()->get('layout');
        return $layout->get($params['scope'], $params['name']);
    }
}
