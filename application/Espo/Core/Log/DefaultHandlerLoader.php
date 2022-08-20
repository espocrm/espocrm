<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2022 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

namespace Espo\Core\Log;

use Monolog\{
    Logger,
    Handler\HandlerInterface,
    Handler\FormattableHandlerInterface,
    Formatter\FormatterInterface,
};

use ReflectionClass;
use RuntimeException;

class DefaultHandlerLoader
{
    /**
     * @param array{
     *   className?: ?class-string<HandlerInterface>,
     *   params?: ?array<string,mixed>,
     *   level?: ?string,
     * } $data
     */
    public function load(array $data, ?string $defaultLevel = null): HandlerInterface
    {
        /** @var array<mixed,mixed> $data */

        $params = $data['params'] ?? [];

        $level = $data['level'] ?? $defaultLevel;

        if ($level) {
            $params['level'] = Logger::toMonologLevel($level);
        }

        /** @var ?class-string<HandlerInterface> $className */
        $className = $data['className'] ?? null;

        if (!$className) {
            throw new RuntimeException("Log handler does not have className specified.");
        }

        $handler = $this->createInstance($className, $params);

        $formatter = $this->loadFormatter($data);

        if ($formatter && $handler instanceof FormattableHandlerInterface) {
            $handler->setFormatter($formatter);
        }

        return $handler;
    }

    /**
     * @param array{
     *   formatter?: ?string,
     *   className?: ?class-string<FormatterInterface>,
     *   params?: ?array<string,mixed>
     * } $data
     */
    protected function loadFormatter(array $data): ?FormatterInterface
    {
        /** @var array<mixed,mixed> $data */

        $formatterData = $data['formatter'] ?? null;

        if (!$formatterData || !is_array($formatterData)) {
            return null;
        }

        /** @var ?class-string<FormatterInterface> $className */
        $className = $formatterData['className'] ?? null;

        if (!$className) {
            return null;
        }

        $params = $formatterData['params'] ?? [];

        return $this->createInstance($className, $params);
    }

    /**
     * @template T of object
     * @param class-string<T> $className
     * @param array<string,mixed> $params
     * @return T
     */
    protected function createInstance(string $className, array $params): object
    {
        $class = new ReflectionClass($className);

        $constructor = $class->getConstructor();

        if (!$constructor) {
            return $class->newInstanceArgs([]);
        }

        $argumentList = [];

        foreach ($constructor->getParameters() as $parameter) {
            $name = $parameter->getName();

            if (array_key_exists($name, $params)) {
                $value = $params[$name];
            }
            else if ($parameter->isDefaultValueAvailable()) {
                $value = $parameter->getDefaultValue();
            } else {
                continue;
            }

            $argumentList[] = $value;
        }

        return $class->newInstanceArgs($argumentList);
    }
}
