<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

use Espo\Core\Exceptions\Error;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\NotFound;
use Espo\Core\Exceptions\BadRequest;

class FieldManager extends \Espo\Core\Controllers\Base
{
    protected function checkControllerAccess()
    {
        if (!$this->getUser()->isAdmin()) {
            throw new Forbidden();
        }
    }

    public function actionRead($params, $data)
    {
        if (empty($params['scope']) || empty($params['name'])) {
            throw new BadRequest();
        }

        $data = $this->getContainer()->get('fieldManager')->read($params['scope'], $params['name']);

        if (!isset($data)) {
            throw new BadRequest();
        }

        return $data;
    }

    public function postActionCreate($params, $data)
    {
        if (empty($params['scope']) || empty($data->name)) {
            throw new BadRequest();
        }

        $fieldManager = $this->getContainer()->get('fieldManager');
        $fieldManager->create($params['scope'], $data->name, get_object_vars($data));

        try {
            $this->getContainer()->get('dataManager')->rebuild($params['scope']);
        } catch (Error $e) {
            $fieldManager->delete($params['scope'], $data->name);
            throw new Error($e->getMessage());
        }

        return $fieldManager->read($params['scope'], $data->name);
    }

    public function patchActionUpdate($params, $data)
    {
        return $this->putActionUpdate($params, $data);
    }

    public function putActionUpdate($params, $data)
    {
        if (empty($params['scope']) || empty($params['name'])) {
            throw new BadRequest();
        }

        $fieldManager = $this->getContainer()->get('fieldManager');
        $fieldManager->update($params['scope'], $params['name'], get_object_vars($data));

        if ($fieldManager->isChanged()) {
            $this->getContainer()->get('dataManager')->rebuild($params['scope']);
        } else {
            $this->getContainer()->get('dataManager')->clearCache();
        }

        return $fieldManager->read($params['scope'], $params['name']);
    }

    public function deleteActionDelete($params, $data)
    {
        if (empty($params['scope']) || empty($params['name'])) {
            throw new BadRequest();
        }

        $result = $this->getContainer()->get('fieldManager')->delete($params['scope'], $params['name']);

        $this->getContainer()->get('dataManager')->rebuildMetadata();

        return $result;
    }

    public function postActionResetToDefault($params, $data)
    {
        if (empty($data->scope) || empty($data->name)) {
            throw new BadRequest();
        }

        $this->getContainer()->get('fieldManager')->resetToDefault($data->scope, $data->name);

        $this->getContainer()->get('dataManager')->clearCache();

        $this->getContainer()->get('dataManager')->rebuildMetadata();

        return true;
    }
}
