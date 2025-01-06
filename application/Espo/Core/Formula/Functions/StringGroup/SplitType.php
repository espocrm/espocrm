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

use Espo\Core\Formula\Functions\BaseFunction;
use Espo\Core\Formula\ArgumentList;

class SplitType extends BaseFunction
{
    /**
     * @return string[]
     * @throws \Espo\Core\Formula\Exceptions\TooFewArguments
     * @throws \Espo\Core\Formula\Exceptions\BadArgumentType
     * @throws \Espo\Core\Formula\Exceptions\Error
     */
    public function process(ArgumentList $args)
    {
        $evaluatedArgs = $this->evaluate($args);

        if (count($evaluatedArgs) < 2) {
            $this->throwTooFewArguments(2);
        }

        $string = $evaluatedArgs[0] ?? '';
        $separator = $evaluatedArgs[1];

        if (!is_string($string)) {
            $this->throwBadArgumentType(1, 'string');
        }

        if (!is_string($separator)) {
            $this->throwBadArgumentType(2, 'string');
        }

        if ($separator === '') {
            return mb_str_split($string);
        }

        return explode($separator, $string);
    }
}
