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

namespace Espo\Core\Formula\Functions\JsonGroup;

use Espo\Core\Formula\ArgumentList;
use Espo\Core\Formula\Exceptions\Error;
use Espo\Core\Formula\Exceptions\ExecutionException;
use Espo\Core\Formula\Exceptions\TooFewArguments;
use Espo\Core\Formula\Functions\BaseFunction;

class RetrieveType extends BaseFunction
{
    /**
     * @return mixed
     * @throws TooFewArguments
     * @throws Error
     * @throws ExecutionException
     */
    public function process(ArgumentList $args)
    {
        if (count($args) < 1) {
            $this->throwTooFewArguments();
        }

        $jsonString = $this->evaluate($args[0]);

        $path = count($args) > 1 ?
            $this->evaluate($args[1]) :
            '';

        if (!is_string($jsonString)) {
            $this->throwBadArgumentType(1, 'string');
        }

        if (!is_string($path)) {
            $this->throwBadArgumentType(2, 'string');
        }

        $item = json_decode($jsonString);

        $pathArray = $this->splitPath($path);

        return $this->retrieveAttribute($item, $pathArray);
    }

    /**
     * @param string $path
     * @return string[]
     */
    private function splitPath(string $path): array
    {
        if ($path === '') {
            return [];
        }

        /** @var list<string> $pathArray */
        $pathArray = preg_split('/(?<!\\\)\./', $path);

        foreach ($pathArray as $i => $item) {
            $pathArray[$i] = str_replace('\.', '.', $item);
        }

        return $pathArray;
    }

    /**
     * @param mixed $item
     * @param string[] $path
     * @return mixed
     */
    private function retrieveAttribute($item, array $path)
    {
        if (!count($path)) {
            return $item;
        }

        $key = array_shift($path);

        if (is_array($item)) {
            $key = intval($key);

            $subItem = $item[$key] ?? null;

            if (is_null($subItem)) {
                return null;
            }

            return $this->retrieveAttribute($subItem, $path);
        }

        if (is_object($item)) {
            $subItem = $item->$key ?? null;

            if (is_null($subItem)) {
                return null;
            }

            return $this->retrieveAttribute($subItem, $path);
        }

        return null;
    }
}
