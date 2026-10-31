<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2026 EspoCRM, Inc.
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

use Espo\Core\Formula\EvaluatedArgumentList;
use Espo\Core\Formula\Exceptions\BadArgumentType;
use Espo\Core\Formula\Exceptions\BadArgumentValue;
use Espo\Core\Formula\Exceptions\TooFewArguments;
use Espo\Core\Formula\Func;

/**
 * @noinspection PhpUnused
 */
class ReplaceManyType implements Func
{
    public function process(EvaluatedArgumentList $arguments): string
    {
        if (count($arguments) < 2) {
            throw TooFewArguments::create(2);
        }

        $input = $arguments[0];
        $map = $arguments[1];

        if (!is_string($input)) {
            throw BadArgumentType::create(1, 'string');
        }

        if (!is_object($map)) {
            throw BadArgumentType::create(2, 'object');
        }

        $pairs = [];

        foreach (get_object_vars($map) as $k => $v) {
            if (!is_scalar($v) && $v !== null) {
                throw BadArgumentValue::create(2, "Replacement strings must be scalar.");
            }

            $pairs[$k] = (string) $v;
        }

        return strtr($input, $pairs);
    }
}
