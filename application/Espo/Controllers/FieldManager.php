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

class FieldManager extends
    Base
{

    public function actionRead($params, $data)
    {
        /**
         * @var \Espo\Core\Utils\FieldManager $fieldManager
         */
        $fieldManager = $this->getContainer()->get('fieldManager');
        $data = $fieldManager->read($params['name'], $params['scope']);
        if (!isset($data)) {
            throw new NotFound();
        }
        return $data;
    }

    public function actionCreate($params, $data)
    {
        /**
         * @var \Espo\Core\Utils\FieldManager $fieldManager
         * @var DataManager                   $dataManager
         */
        if (empty($data['name'])) {
            throw new Error("Field 'name' cannnot be empty");
        }
        $fieldManager = $this->getContainer()->get('fieldManager');
        $fieldManager->create($data['name'], $data, $params['scope']);
        try{
            $dataManager = $this->getContainer()->get('dataManager');
            $dataManager->rebuild($params['scope']);
        } catch(Error $e){
            $fieldManager->delete($data['name'], $params['scope']);
            throw new Error($e->getMessage());
        }
        return $fieldManager->read($data['name'], $params['scope']);
    }

    public function actionUpdate($params, $data)
    {
        /**
         * @var \Espo\Core\Utils\FieldManager $fieldManager
         * @var DataManager                   $dataManager
         */
        $fieldManager = $this->getContainer()->get('fieldManager');
        $fieldManager->update($params['name'], $data, $params['scope']);
        $dataManager = $this->getContainer()->get('dataManager');
        if ($fieldManager->isChanged()) {
            $dataManager->rebuild($params['scope']);
        } else {
            $dataManager->clearCache();
        }
        return $fieldManager->read($params['name'], $params['scope']);
    }

    public function actionDelete($params, $data)
    {
        /**
         * @var \Espo\Core\Utils\FieldManager $fieldManager
         * @var DataManager                   $dataManager
         */
        $fieldManager = $this->getContainer()->get('fieldManager');
        $dataManager = $this->getContainer()->get('dataManager');
        $res = $fieldManager->delete($params['name'], $params['scope']);
        $dataManager->rebuildMetadata();
        return $res;
    }

    protected function checkControllerAccess()
    {
        if (!$this->getUser()->isAdmin()) {
            throw new Forbidden();
        }
    }
}

