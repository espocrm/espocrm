<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2021 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

use Espo\Core\{
    InjectableFactory,
    Utils\ClassFinder,
    Api\Request,
    Api\Response,
    Exceptions\NotFound,
};

use ReflectionClass;

/**
 * Creates controller instances and processes actions.
 */
class ControllerManager
{
    protected $injectableFactory;
    protected $classFinder;

    public function __construct(InjectableFactory $injectableFactory, ClassFinder $classFinder)
    {
        $this->injectableFactory = $injectableFactory;
        $this->classFinder = $classFinder;
    }

    public function process(string $controllerName, string $actionName, Request $request, Response $response)
    {
        $controller = $this->createController($controllerName);

        $requestMethod = $request->getMethod();

        if ($actionName == 'index') {
            $actionName = $controller::$defaultAction ?? 'index';
        }

        $actionNameUcfirst = ucfirst($actionName);

        $actionMethodName = 'action' . $actionNameUcfirst;

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

        if (
            $this->useShortParamList($controller, $primaryActionMethodName)
        ) {
            return $controller->$primaryActionMethodName($request, $response);
        }

        // Below is a legacy way.

        $data = $request->getBodyContents();

        if ($data && $request->getContentType() === 'application/json') {
            $data = json_decode($data);
        }

        $params = $request->getRouteParams();

        $beforeMethodName = 'before' . $actionNameUcfirst;

        if (method_exists($controller, $beforeMethodName)) {
            $controller->$beforeMethodName($params, $data, $request, $response);
        }

        $result = $controller->$primaryActionMethodName($params, $data, $request, $response);

        $afterMethodName = 'after' . $actionNameUcfirst;

        if (method_exists($controller, $afterMethodName)) {
            $controller->$afterMethodName($params, $data, $request, $response);
        }

        return $result;
    }

    protected function useShortParamList(object $controller, string $methodName) : bool
    {
        $class = new ReflectionClass($controller);

        $method = $class->getMethod($methodName);

        $params = $method->getParameters();

        if (count($params) === 0) {
            return false;
        }

        $type = $params[0]->getType();

        if (!$type || $type->isBuiltin()) {
            return false;
        }

        $firstParamClass = new ReflectionClass($type->getName());

        if (
            $firstParamClass->getName() === Request::class ||
            $firstParamClass->isSubclassOf(Request::class)
        ) {
            return true;
        }

        return false;
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
        return $this->injectableFactory->createWith($this->getControllerClassName($name), [
            'name' => $name,
        ]);
    }
}
