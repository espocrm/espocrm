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

namespace Espo\Core\Binding;

use LogicException;

class Binding
{
    public const IMPLEMENTATION_CLASS_NAME = 1;
    public const CONTAINER_SERVICE = 2;
    public const VALUE = 3;
    public const CALLBACK = 4;
    public const FACTORY_CLASS_NAME = 5;

    private int $type;
    /** @var mixed */
    private $value;

    /**
     * @param mixed $value
     */
    private function __construct(int $type, $value)
    {
        $this->type = $type;
        $this->value = $value;
    }

    public function getType(): int
    {
        return $this->type;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param class-string<object> $implementationClassName
     */
    public static function createFromImplementationClassName(string $implementationClassName): self
    {
        if (!$implementationClassName) {
            throw new LogicException("Bad binding.");
        }

        return new self(self::IMPLEMENTATION_CLASS_NAME, $implementationClassName);
    }

    public static function createFromServiceName(string $serviceName): self
    {
        if (!$serviceName) {
            throw new LogicException("Bad binding.");
        }

        return new self(self::CONTAINER_SERVICE, $serviceName);
    }

    /**
     * @param mixed $value
     */
    public static function createFromValue($value): self
    {
        return new self(self::VALUE, $value);
    }

    public static function createFromCallback(callable $callback): self
    {
        return new self(self::CALLBACK, $callback);
    }

    /**
     * @param class-string<Factory<object>> $factoryClassName
     */
    public static function createFromFactoryClassName(string $factoryClassName): self
    {
        if (!$factoryClassName) {
            throw new LogicException("Bad binding.");
        }

        return new self(self::FACTORY_CLASS_NAME, $factoryClassName);
    }
}
