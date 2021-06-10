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

namespace Espo\Core\Formula\Functions\JsonGroup;

use Espo\Core\Formula\{
    Functions\BaseFunction,
    ArgumentList,
};

class RetrieveType extends BaseFunction
{
    public function process(ArgumentList $args)
    {
        if (count($args) < 2) {
            $this->throwTooFewArguments();
        }

        $jsonString = $this->evaluate($args[0]);
        $path = $this->evaluate($args[1]);

        if (!is_string($jsonString)) {
            $this->throwBadArgumentType(1, 'string');
        }

        if (!is_string($path)) {
            $this->throwBadArgumentType(2, 'string');
        }

        if ($path === '') {
            $this->throwBadArgumentValue(2);
        }

        $item = json_decode($jsonString);

        $pathArray = $this->splitPath($path);

        return $this->retrieveAttribute($item, $pathArray);
    }

    private function splitPath(string $path): array
    {
        $pathArray = preg_split('/(?<!\\\)\./', $path);

        foreach ($pathArray as $i => $item) {
            $pathArray[$i] = str_replace('\.', '.', $item);
        }

        return $pathArray;
    }

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
