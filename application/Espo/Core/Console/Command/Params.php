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

namespace Espo\Core\Console\Command;

use Espo\Core\Utils\Util;

/**
 * Command parameters.
 *
 * Immutable.
 */
class Params
{
    /** @var array<string, string> */
    private $options;
    /** @var string[] */
    private $flagList;
    /** @var string[] */
    private $argumentList;

    /**
     * @param array<string, string>|null $options
     * @param string[]|null $flagList
     * @param string[]|null $argumentList
     */
    public function __construct(?array $options, ?array $flagList, ?array $argumentList)
    {
        $this->options = $options ?? [];
        $this->flagList = $flagList ?? [];
        $this->argumentList = $argumentList ?? [];
    }

    /**
     * @return array<string, string>
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * @return string[]
     */
    public function getFlagList(): array
    {
        return $this->flagList;
    }

    /**
     * @return string[]
     */
    public function getArgumentList(): array
    {
        return $this->argumentList;
    }

    /**
     * Has an option.
     */
    public function hasOption(string $name): bool
    {
        return array_key_exists($name, $this->options);
    }

    /**
     * Get an option.
     */
    public function getOption(string $name): ?string
    {
        return $this->options[$name] ?? null;
    }

    /**
     * Has a flag.
     */
    public function hasFlag(string $name): bool
    {
        return in_array($name, $this->flagList);
    }

    /**
     * Get an argument by index.
     */
    public function getArgument(int $index): ?string
    {
        return $this->argumentList[$index] ?? null;
    }

    /**
     * @param array<int, string> $args
     */
    public static function fromArgs(array $args): self
    {
        $argumentList = [];
        $options = [];
        $flagList = [];

        foreach ($args as $i => $item) {
            if (str_starts_with($item, '--') && strpos($item, '=') > 2) {
                [$name, $value] = explode('=', substr($item, 2));

                $name = Util::hyphenToCamelCase($name);

                $options[$name] = $value;
            } else if (str_starts_with($item, '--')) {
                $flagList[] = Util::hyphenToCamelCase(substr($item, 2));
            } else if (str_starts_with($item, '-')) {
                $flagList[] = substr($item, 1);
            } else if ($i > 0) {
                $argumentList[] = $item;
            }
        }

        return new self($options, $flagList, $argumentList);
    }
}
