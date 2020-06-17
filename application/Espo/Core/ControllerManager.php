<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2020 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

namespace Espo\Core;

use Espo\Core\Utils\Util;
use Espo\Core\Exceptions\NotFound;

class ControllerManager
{
    private $container;

    private $controllersHash = null;

    public function __construct(\Espo\Core\Container $container)
    {
        $this->container = $container;

        $this->controllersHash = (object) [];
    }

    protected function getContainer()
    {
        return $this->container;
    }

    protected function getControllerClassName(string $name) : string
    {
        $className = $this->getContainer()->get('classFinder')->find('Controllers', $name);

        if (!$className) {
            throw new NotFound("Controller '{$name}' does not exist.");
        }

        if (!class_exists($className)) {
            throw new NotFound("Class not found for controller '{$name}'.");
        }

        return $className;
    }

    public function createController($name)
    {
        $controllerClassName = $this->getControllerClassName($name);
        $controller = new $controllerClassName($this->container);

        return $controller;
    }

    public function getController($name)
    {
        if (!property_exists($this->controllersHash, $name)) {
            $this->controllersHash->$name = $this->createController($name);
        }
        return $this->controllersHash->$name;
    }

    public function processRequest(\Espo\Core\Controllers\Base $controller, $actionName, $params, $data, $request, $response = null)
    {
        if ($data && stristr($request->getContentType(), 'application/json')) {
            $data = json_decode($data);
        }

        if ($actionName == 'index') {
            $actionName = $controller::$defaultAction;
        }

        $requestMethod = $request->getMethod();

        $actionNameUcfirst = ucfirst($actionName);

        $beforeMethodName = 'before' . $actionNameUcfirst;
        $actionMethodName = 'action' . $actionNameUcfirst;
        $afterMethodName = 'after' . $actionNameUcfirst;

        $fullActionMethodName = strtolower($requestMethod) . ucfirst($actionMethodName);

        if (method_exists($controller, $fullActionMethodName)) {
            $primaryActionMethodName = $fullActionMethodName;
        } else {
            $primaryActionMethodName = $actionMethodName;
        }

        if (!method_exists($controller, $primaryActionMethodName)) {
            throw new NotFound("Action {$requestMethod} '{$actionName}' does not exist in controller '".$controller->getName()."'.");
        }

        // TODO Remove in 6.0.0
        if ($data instanceof \stdClass) {
            if (
                $this->container->get('metadata')->get(
                    ['app', 'deprecatedControllerActions', $controller->getName(), $primaryActionMethodName])
            ) {
                $data = get_object_vars($data);
            }
        }

        if (method_exists($controller, $beforeMethodName)) {
            $controller->$beforeMethodName($params, $data, $request, $response);
        }

        $result = $controller->$primaryActionMethodName($params, $data, $request, $response);

        if (method_exists($controller, $afterMethodName)) {
            $controller->$afterMethodName($params, $data, $request, $response);
        }

        if (is_array($result) || is_bool($result) || $result instanceof \StdClass) {
            return \Espo\Core\Utils\Json::encode($result);
        }

        return $result;
    }

    public function process($controllerName, $actionName, $params, $data, $request, $response = null)
    {
        $controller = $this->getController($controllerName);
        return $this->processRequest($controller, $actionName, $params, $data, $request, $response);
    }
}
