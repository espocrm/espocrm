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

namespace Espo\Core\Formula\Functions\StringGroup;

use Espo\Core\Formula\{
    Functions\BaseFunction,
    ArgumentList,
};

class PadType extends BaseFunction
{
    public function process(ArgumentList $args)
    {
        $args = $this->evaluate($args);

        if (count($args) < 2) {
            $this->throwTooFewArguments();
        }

        $input = $args[0];
        $length = $args[1];
        $string = $args[2] ?? ' ';
        $type = $args[3] ?? 'right';

        if (!is_string($input)) {
            $input = strval($input);
        }

        if (!is_int($length)) {
            $this->throwBadArgumentType(2);
        }

        $map = [
            'right' => \STR_PAD_RIGHT,
            'left' => \STR_PAD_LEFT,
            'both' => \STR_PAD_BOTH,
        ];

        $padType = $map[$type] ?? \STR_PAD_RIGHT;

        return str_pad($input, $length, $string, $padType);
    }
}
