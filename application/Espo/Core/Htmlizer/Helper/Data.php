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

namespace Espo\Core\Htmlizer\Helper;

use stdClass;
use Closure;

class Data
{
    /**
     * @param mixed[] $argumentList
     * @param array<string, mixed> $rootContext
     * @noinspection PhpPluralMixedCanBeReplacedWithArrayInspection
     */
    public function __construct(
        private string $name,
        private array $argumentList,
        private stdClass $options,
        private mixed $context,
        private array $rootContext,
        private ?Closure $func,
        private ?Closure $inverseFunc
    ) {}

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return mixed A context.
     */
    public function getContext(): mixed
    {
        return $this->context;
    }

    /**
     * @return array<string, mixed>
     */
    public function getRootContext(): array
    {
        return $this->rootContext;
    }

    public function getOptions(): stdClass
    {
        return $this->options;
    }

    /**
     * @return mixed[]
     * @noinspection PhpPluralMixedCanBeReplacedWithArrayInspection
     */
    public function getArgumentList(): array
    {
        return $this->argumentList;
    }

    public function hasOption(string $name): bool
    {
        return property_exists($this->options, $name);
    }

    /**
     * @return mixed
     */
    public function getOption(string $name)
    {
        return $this->options->$name ?? null;
    }

    public function getFunction(): ?Closure
    {
        return $this->func;
    }

    /** @noinspection PhpUnused */
    public function getInverseFunction(): ?Closure
    {
        return $this->inverseFunc;
    }
}
