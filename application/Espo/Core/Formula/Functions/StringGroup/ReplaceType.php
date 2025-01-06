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

class ReplaceType extends BaseFunction
{
    public function process(ArgumentList $args)
    {
        $args = $this->evaluate($args);

        if (count($args) < 3) {
            $this->throwTooFewArguments();
        };

        $string = $args[0];
        $search = $args[1];
        $replace = $args[2];

        if (!is_string($string)) {
            $this->logBadArgumentType(1, 'string');
            return '';
        }

        if (!is_string($search)) {
            $this->logBadArgumentType(2, 'string');
            return $string;
        }

        if (!is_string($replace)) {
            $this->logBadArgumentType(3, 'string');
            return $string;
        }

        return str_replace($search, $replace, $string);
    }
}
