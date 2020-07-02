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

use Espo\Core\Exceptions\NotFound;
use Espo\Core\Exceptions\BadRequest;

use Espo\Core\{
    InjectableFactory,
    Utils\ClassFinder,
    Utils\Json,
    Utils\Util,
    Api\Request,
    Api\Response,
};

class ControllerManager
{
    private $controllersHash;

    protected $injectableFactory;
    protected $classFinder;

    public function __construct(InjectableFactory $injectableFactory, ClassFinder $classFinder)
    {
        $this->injectableFactory = $injectableFactory;
        $this->classFinder = $classFinder;

        $this->controllersHash = (object) [];
    }

    public function process(
        string $controllerName,
        string $actionName,
        array $params,
        $data,
        Request $request,
        Response $response
    ) {
        $controller = $this->getController($controllerName);
        return $this->processRequest($controller, $controllerName, $actionName, $params, $data, $request, $response);
    }

    protected function getControllerClassName(string $name) : string
    {
        $className = $this->classFinder->find('Controllers', $name);

        if (!$className) {
            throw new NotFound("Controller '{$name}' does not exist.");
        }

        if (!class_exists($className)) {
            throw new NotFound("Class not found for controller '{$name}'.");
        }

        return $className;
    }

    protected function createController(string $name) : object
    {
        $className = $this->getControllerClassName($name);

        $controller = $this->injectableFactory->createWith($className, [
            'name' => $name,
        ]);

        return $controller;
    }

    protected function getController(string $name) : object
    {
        if (!property_exists($this->controllersHash, $name)) {
            $this->controllersHash->$name = $this->createController($name);
        }
        return $this->controllersHash->$name;
    }

    protected function processRequest(
        object $controller,
        string $controllerName,
        string $actionName,
        array $params,
        $data,
        Request $request,
        Response $response
    ) {
        if ($data && stristr($request->getContentType(), 'application/json')) {
            $data = json_decode($data);
        }

        if ($actionName == 'index') {
            $actionName = $controller::$defaultAction ?? 'index';
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
            throw new NotFound(
                "Action {$requestMethod} '{$actionName}' does not exist in controller '{$controllerName}'."
            );
        }

        if (method_exists($controller, $beforeMethodName)) {
            $controller->$beforeMethodName($params, $data, $request, $response);
        }

        $class = new \ReflectionClass($controller);
        $method = $class->getMethod($primaryActionMethodName);
        $args = $method->getParameters();
        if (count($args) >= 2) {
            if ($args[1]->hasType()) {
                $dataClass = $args[1]->getClass();
                if ($dataClass && strtolower($dataClass->getName()) === 'stdclass') {
                    if (!$data instanceof \StdClass) {
                        throw new BadRequest(
                            "{$controllerName} {$requestMethod} {$actionName}: Content-Type should be 'application/json'."
                        );
                    }
                }
            }
        }

        $result = $controller->$primaryActionMethodName($params, $data, $request, $response);

        if (method_exists($controller, $afterMethodName)) {
            $controller->$afterMethodName($params, $data, $request, $response);
        }

        if (is_array($result) || is_bool($result) || $result instanceof \StdClass) {
            return Json::encode($result);
        }

        return $result;
    }
}
