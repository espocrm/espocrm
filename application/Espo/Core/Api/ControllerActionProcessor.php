<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
 * Website: https://www.espocrm.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU Affero General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU Affero General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

namespace Espo\Core\Api;

use Espo\Core\Exceptions\NotFound;
use Espo\Core\Exceptions\NotFoundSilent;
use Espo\Core\InjectableFactory;
use Espo\Core\Utils\ClassFinder;
use Espo\Core\Utils\Json;

use ReflectionClass;
use ReflectionNamedType;
use stdClass;

/**
 * Creates controller instances and processes actions.
 */
class ControllerActionProcessor
{
    public function __construct(
        private InjectableFactory $injectableFactory,
        private ClassFinder $classFinder
    ) {}

    /**
     * @throws NotFound
     */
    public function process(
        string $controllerName,
        string $actionName,
        Request $request,
        ResponseWrapper $response
    ): ResponseWrapper {

        $controller = $this->createController($controllerName);

        $requestMethod = $request->getMethod();

        if (
            $actionName == 'index' &&
            property_exists($controller, 'defaultAction')
        ) {
            $actionName = $controller::$defaultAction ?? 'index';
        }

        $actionMethodName = 'action' . ucfirst($actionName);

        $fullActionMethodName = strtolower($requestMethod) . ucfirst($actionMethodName);

        $primaryActionMethodName = method_exists($controller, $fullActionMethodName) ?
            $fullActionMethodName :
            $actionMethodName;

        if (!method_exists($controller, $primaryActionMethodName)) {
            throw new NotFoundSilent(
                "Action $requestMethod '$actionName' does not exist in controller '$controllerName'.");
        }

        if ($this->useShortParamList($controller, $primaryActionMethodName)) {
            $result = $controller->$primaryActionMethodName($request, $response) ?? null;

            $this->handleResult($response, $result);

            return $response;
        }

        // Below is a legacy way.

        $data = $request->getBodyContents();

        if ($data && $this->getRequestContentType($request) === 'application/json') {
            $data = json_decode($data);
        }

        $params = $request->getRouteParams();

        $beforeMethodName = 'before' . ucfirst($actionName);

        if (method_exists($controller, $beforeMethodName)) {
            $controller->$beforeMethodName($params, $data, $request, $response);
        }

        $result = $controller->$primaryActionMethodName($params, $data, $request, $response) ?? null;

        $afterMethodName = 'after' . ucfirst($actionName);

        if (method_exists($controller, $afterMethodName)) {
            $controller->$afterMethodName($params, $data, $request, $response);
        }

        $this->handleResult($response, $result);

        return $response;
    }

    /**
     * @param mixed $result
     */
    private function handleResult(Response $response, $result): void
    {
        $responseContents = $result;

        if (
            is_int($result) ||
            is_float($result) ||
            is_array($result) ||
            is_bool($result) ||
            $result instanceof stdClass
        ) {
            $responseContents = Json::encode($result);
        }

        if (is_string($responseContents)) {
            $response->writeBody($responseContents);
        }
    }

    private function useShortParamList(object $controller, string $methodName): bool
    {
        $class = new ReflectionClass($controller);

        $method = $class->getMethod($methodName);
        $params = $method->getParameters();

        if (count($params) === 0) {
            return false;
        }

        $type = $params[0]->getType();

        if (
            !$type instanceof ReflectionNamedType ||
            $type->isBuiltin()
        ) {
            return false;
        }

        /** @var class-string $className */
        $className = $type->getName();

        $firstParamClass = new ReflectionClass($className);

        if (
            $firstParamClass->getName() === Request::class ||
            $firstParamClass->isSubclassOf(Request::class)
        ) {
            return true;
        }

        return false;
    }

    /**
     * @return class-string
     * @throws NotFound
     */
    private function getControllerClassName(string $name): string
    {
        $className = $this->classFinder->find('Controllers', $name);

        if (!$className) {
            throw new NotFound("Controller '$name' does not exist.");
        }

        if (!class_exists($className)) {
            throw new NotFound("Class not found for controller '$name'.");
        }

        return $className;
    }

    /**
     * @throws NotFound
     */
    private function createController(string $name): object
    {
        return $this->injectableFactory->createWith($this->getControllerClassName($name), [
            'name' => $name,
        ]);
    }

    private function getRequestContentType(Request $request): ?string
    {
        if ($request instanceof RequestWrapper) {
            return $request->getContentType();
        }

        return null;
    }
}
