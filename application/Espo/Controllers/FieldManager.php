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
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

namespace Espo\Controllers;

use \Espo\Core\Exceptions\Error,
    \Espo\Core\Exceptions\Forbidden,
    \Espo\Core\Exceptions\NotFound,
    \Espo\Core\Exceptions\BadRequest;

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

        $data = $this->getContainer()->get('fieldManager')->read($params['name'], $params['scope']);

        if (!isset($data)) {
            throw new BadRequest();
        }

        return $data;
    }

    public function postActionCreate($params, $data)
    {
        if (empty($params['scope']) || empty($data['name'])) {
            throw new BadRequest();
        }

        $fieldManager = $this->getContainer()->get('fieldManager');
        $fieldManager->create($data['name'], $data, $params['scope']);

        try {
            $this->getContainer()->get('dataManager')->rebuild($params['scope']);
        } catch (Error $e) {
            $fieldManager->delete($data['name'], $params['scope']);
            throw new Error($e->getMessage());
        }

        return $fieldManager->read($data['name'], $params['scope']);
    }

    public function putActionUpdate($params, $data)
    {
        if (empty($params['scope']) || empty($params['name'])) {
            throw new BadRequest();
        }

        $fieldManager = $this->getContainer()->get('fieldManager');
        $fieldManager->update($params['name'], $data, $params['scope']);

        if ($fieldManager->isChanged()) {
            $this->getContainer()->get('dataManager')->rebuild($params['scope']);
        } else {
            $this->getContainer()->get('dataManager')->clearCache();
        }

        return $fieldManager->read($params['name'], $params['scope']);
    }

    public function deleteActionDelete($params, $data)
    {
        if (empty($params['scope']) || empty($params['name'])) {
            throw new BadRequest();
        }

        $res = $this->getContainer()->get('fieldManager')->delete($params['name'], $params['scope']);

        $this->getContainer()->get('dataManager')->rebuildMetadata();

        return $res;
    }

    public function postActionResetToDefault($params, $data)
    {
        if (empty($data['scope']) || empty($data['name'])) {
            throw new BadRequest();
        }

        $this->getContainer()->get('fieldManager')->resetToDefault($data['name'], $data['scope']);

        $this->getContainer()->get('dataManager')->rebuildMetadata();

        return true;
    }
}

