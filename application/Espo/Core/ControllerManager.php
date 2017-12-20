<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2018 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

namespace Espo\Core;

use \Espo\Core\Utils\Util;
use \Espo\Core\Exceptions\NotFound;

class ControllerManager
{
    private $config;

    private $metadata;

    private $container;

    public function __construct(\Espo\Core\Container $container)
    {
        $this->container = $container;

        $this->config = $this->container->get('config');
        $this->metadata = $this->container->get('metadata');
    }

    protected function getConfig()
    {
        return $this->config;
    }

    protected function getMetadata()
    {
        return $this->metadata;
    }

    public function process($controllerName, $actionName, $params, $data, $request)
    {
        $customClassName = '\\Espo\\Custom\\Controllers\\' . Util::normilizeClassName($controllerName);
        if (class_exists($customClassName)) {
            $controllerClassName = $customClassName;
        } else {
            $moduleName = $this->metadata->getScopeModuleName($controllerName);
            if ($moduleName) {
                $controllerClassName = '\\Espo\\Modules\\' . $moduleName . '\\Controllers\\' . Util::normilizeClassName($controllerName);
            } else {
                $controllerClassName = '\\Espo\\Controllers\\' . Util::normilizeClassName($controllerName);
            }
        }

        if ($data && stristr($request->getContentType(), 'application/json')) {
            $data = json_decode($data);
        }

        if (!class_exists($controllerClassName)) {
            throw new NotFound("Controller '$controllerName' is not found");
        }

        $controller = new $controllerClassName($this->container, $request->getMethod());

        if ($actionName == 'index') {
            $actionName = $controllerClassName::$defaultAction;
        }

        $actionNameUcfirst = ucfirst($actionName);

        $beforeMethodName = 'before' . $actionNameUcfirst;
        $actionMethodName = 'action' . $actionNameUcfirst;
        $afterMethodName = 'after' . $actionNameUcfirst;

        $fullActionMethodName = strtolower($request->getMethod()) . ucfirst($actionMethodName);

        if (method_exists($controller, $fullActionMethodName)) {
            $primaryActionMethodName = $fullActionMethodName;
        } else {
            $primaryActionMethodName = $actionMethodName;
        }

        if (!method_exists($controller, $primaryActionMethodName)) {
            throw new NotFound("Action '$actionName' (".$request->getMethod().") does not exist in controller '$controllerName'");
        }

        // TODO Remove in 5.1.0
        if ($data instanceof \stdClass) {
            if ($this->getMetadata()->get(['app', 'deprecatedControllerActions', $controllerName, $primaryActionMethodName])) {
                $data = get_object_vars($data);
            }
        }

        if (method_exists($controller, $beforeMethodName)) {
            $controller->$beforeMethodName($params, $data, $request);
        }

        $result = $controller->$primaryActionMethodName($params, $data, $request);

        if (method_exists($controller, $afterMethodName)) {
            $controller->$afterMethodName($params, $data, $request);
        }

        if (is_array($result) || is_bool($result) || $result instanceof \StdClass) {
            return \Espo\Core\Utils\Json::encode($result);
        }

        return $result;
    }
}
