<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2020 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

namespace Espo\Core\Formula\Functions\StringGroup;

use \Espo\Core\Exceptions\Error;

class PadType extends \Espo\Core\Formula\Functions\Base
{
    public function process(\StdClass $item)
    {
        $args = $this->fetchArguments($item);

        if (count($args) < 2) throw new Error("Formula: string\\pad: should have at least 2 arguments.");

        $input = $args[0];
        $length = $args[1];
        $string = $args[2] ?? ' ';
        $type = $args[3] ?? 'right';

        if (!is_string($input)) $input = strval($input);
        if (!is_int($length)) throw new Error("Formula: string\\pad: second argument should be integer.");

        $map = [
            'right' => \STR_PAD_RIGHT,
            'left' => \STR_PAD_LEFT,
            'both' => \STR_PAD_BOTH,
        ];

        $padType = $map[$type] ?? \STR_PAD_RIGHT;

        return str_pad($input, $length, $string, $padType);
    }
}