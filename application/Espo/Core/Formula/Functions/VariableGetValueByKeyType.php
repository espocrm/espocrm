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

namespace Espo\Core\Formula\Functions;

use Espo\Core\Formula\ArgumentList;
use Espo\Core\Formula\Exceptions\Error;
use Espo\Core\Formula\Exceptions\UndefinedKey;
use stdClass;

/**
 * @noinspection PhpUnused
 */
class VariableGetValueByKeyType extends BaseFunction
{
    public function process(ArgumentList $args)
    {
        if (count($args) < 2) {
            $this->throwTooFewArguments();
        }

        $name = $this->evaluate($args[0]);
        $keys = $this->evaluate($args[1]);

        if (!is_string($name)) {
            $this->throwBadArgumentValue(1, 'string');
        }

        if (!property_exists($this->getVariables(), $name)) {
            throw new Error("Cannot access by key of non-existing variable.");
        }

        $reference =& $this->getVariables()->$name;

        $value = null;

        foreach ($keys as $key) {
            $value =& $this->getByKey($reference, $key);

            $reference =& $value;
        }

        return $value;
    }

    /**
     * @throws Error
     */
    private function &getByKey(mixed &$reference, mixed $key): mixed
    {
        if (!is_array($reference) && !$reference instanceof stdClass) {
            throw new Error("Cannot access by key of variable that is non-array and non-object.");
        }

        if (is_array($reference)) {
            if (!is_int($key)) {
                throw new Error("Cannot get array item value by non-integer key.");
            }

            if ($key < 0) {
                throw new UndefinedKey("Cannot get array item value by key that is less than zero.");
            }

            if ($key > count($reference) - 1) {
                throw new UndefinedKey("Cannot get array item value by key that is out of array end.");
            }

            if (!array_key_exists($key, $reference)) {
                throw new UndefinedKey("Cannot get array item value by non-existent key.");
            }

            /** @noinspection PhpUnnecessaryLocalVariableInspection */
            $value =& $reference[$key];

            return $value;
        }

        if (!is_string($key)) {
            throw new Error("Cannot get object item value by non-string key.");
        }

        if ($key === '') {
            throw new Error("Cannot get object item value by empty string key.");
        }

        if (!property_exists($reference, $key)) {
            throw new UndefinedKey("Cannot get object item value by non-existent key.");
        }

        /** @noinspection PhpUnnecessaryLocalVariableInspection */
        $value =& $reference->$key;

        return $value;
    }
}
