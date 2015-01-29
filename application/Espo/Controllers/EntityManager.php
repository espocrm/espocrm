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

use \Espo\Core\Exceptions\BadRequest;
use \Espo\Core\Exceptions\Forbidden;
use \Espo\Core\Exceptions\Error;

class EntityManager extends \Espo\Core\Controllers\Base
{
    protected function checkControllerAccess()
    {
        if (!$this->getUser()->isAdmin()) {
            throw new Forbidden();
        }
    }

    public function actionCreateEntity($params, $data, $request)
    {
        if (!$request->isPost()) {
            throw new BadRequest();
        }

        if (empty($data['name']) || empty($data['type'])) {
            throw new BadRequest();
        }

        $name = $data['name'];
        $type = $data['type'];

        $name = filter_var($name, \FILTER_SANITIZE_STRING);
        $type = filter_var($type, \FILTER_SANITIZE_STRING);

        $params = array();

        if (!empty($data['labelSingular'])) {
            $params['labelSingular'] = $data['labelSingular'];
        }
        if (!empty($data['labelPlural'])) {
            $params['labelPlural'] = $data['labelPlural'];
        }
        if (!empty($data['stream'])) {
            $params['stream'] = $data['stream'];
        }

        $result = $this->getContainer()->get('entityManagerUtil')->create($name, $type, $params);

        if ($result) {
            $tabList = $this->getConfig()->get('tabList', []);
            $tabList[] = $name;
            $this->getConfig()->set('tabList', $tabList);
            $this->getConfig()->save();

            $this->getContainer()->get('dataManager')->rebuild();
        } else {
            throw new Error();
        }

        return true;
    }

    public function actionUpdateEntity($params, $data, $request)
    {
        if (!$request->isPost()) {
            throw new BadRequest();
        }
        if (empty($data['name'])) {
            throw new BadRequest();
        }
        $name = $data['name'];
        $name = filter_var($name, \FILTER_SANITIZE_STRING);

        $result = $this->getContainer()->get('entityManagerUtil')->update($name, $data);

        if ($result) {
            $this->getContainer()->get('dataManager')->clearCache();
        } else {
            throw new Error();
        }

        return true;
    }

    public function actionRemoveEntity($params, $data, $request)
    {
        if (!$request->isPost()) {
            throw new BadRequest();
        }

        if (empty($data['name'])) {
            throw new BadRequest();
        }
        $name = $data['name'];
        $name = filter_var($name, \FILTER_SANITIZE_STRING);

        $result = $this->getContainer()->get('entityManagerUtil')->delete($name);

        if ($result) {
            $tabList = $this->getConfig()->get('tabList', []);
            if (($key = array_search($name, $tabList)) !== false) {
                unset($tabList[$key]);
                $tabList = array_values($tabList);
            }
            $this->getConfig()->set('tabList', $tabList);
            $this->getConfig()->save();

            $this->getContainer()->get('dataManager')->clearCache();
        } else {
            throw new Error();
        }

        return true;
    }

    public function actionCreateLink($params, $data, $request)
    {
        if (!$request->isPost()) {
            throw new BadRequest();
        }

        $paramList = [
        	'entity',
        	'entityForeign',
        	'link',
        	'linkForeign',
        	'label',
        	'labelForeign',
        	'linkType'
        ];

        $d = array();
        foreach ($paramList as $item) {
        	if (empty($data[$item])) {
        		throw new BadRequest();
        	}
        	$d[$item] = filter_var($data[$item], \FILTER_SANITIZE_STRING);
        }

        $result = $this->getContainer()->get('entityManagerUtil')->createLink($d);

        if ($result) {
            $this->getContainer()->get('dataManager')->rebuild();
        } else {
            throw new Error();
        }

        return true;
    }

    public function actionUpdateLink($params, $data, $request)
    {
        if (!$request->isPost()) {
            throw new BadRequest();
        }

        $paramList = [
        	'entity',
        	'entityForeign',
        	'link',
        	'linkForeign',
        	'label',
        	'labelForeign'
        ];

        $d = array();
        foreach ($paramList as $item) {
        	$d[$item] = filter_var($data[$item], \FILTER_SANITIZE_STRING);
        }

        $result = $this->getContainer()->get('entityManagerUtil')->updateLink($d);

        if ($result) {
            $this->getContainer()->get('dataManager')->clearCache();
        } else {
            throw new Error();
        }

        return true;
    }

    public function actionRemoveLink($params, $data, $request)
    {
        if (!$request->isPost()) {
            throw new BadRequest();
        }

        $paramList = [
        	'entity',
        	'link',
        ];
        $d = array();
        foreach ($paramList as $item) {
        	$d[$item] = filter_var($data[$item], \FILTER_SANITIZE_STRING);
        }

        $result = $this->getContainer()->get('entityManagerUtil')->deleteLink($d);

        if ($result) {
            $this->getContainer()->get('dataManager')->clearCache();
        } else {
            throw new Error();
        }

        return true;
    }
}

