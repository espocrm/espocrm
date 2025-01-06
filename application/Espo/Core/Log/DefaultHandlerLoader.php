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

namespace Espo\Core\Log;

use Monolog\Formatter\FormatterInterface;
use Monolog\Handler\FormattableHandlerInterface;
use Monolog\Handler\HandlerInterface;
use Monolog\Logger;

use ReflectionClass;
use RuntimeException;

/**
 * @internal
 *
 * @phpstan-type DefaultHandlerLoaderData array{
 *     className?: ?class-string<HandlerInterface>,
 *     params?: ?array<string, mixed>,
 *     level?: string|null,
 *     formatter?: ?FormatterData,
 * }
 * @phpstan-type FormatterData array{
 *     className?: ?class-string<FormatterInterface>,
 *     params?: ?array<string, mixed>,
 * }
 */
class DefaultHandlerLoader
{
    /**
     * @param DefaultHandlerLoaderData $data
     */
    public function load(array $data, ?string $defaultLevel = null): HandlerInterface
    {
        $params = $data['params'] ?? [];
        $level = $data['level'] ?? $defaultLevel;

        if ($level) {
            /** @phpstan-ignore-next-line */
            $params['level'] = Logger::toMonologLevel($level);
        }

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
     * @internal
     * @param DefaultHandlerLoaderData $data
     */
    public function loadFormatter(array $data): ?FormatterInterface
    {
        $formatterData = $data['formatter'] ?? null;

        if (!$formatterData || !is_array($formatterData)) {
            return null;
        }

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
     * @param array<string, mixed> $params
     * @return T
     */
    private function createInstance(string $className, array $params): object
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
            } else if ($parameter->isDefaultValueAvailable()) {
                $value = $parameter->getDefaultValue();
            } else {
                continue;
            }

            $argumentList[] = $value;
        }

        return $class->newInstanceArgs($argumentList);
    }
}
