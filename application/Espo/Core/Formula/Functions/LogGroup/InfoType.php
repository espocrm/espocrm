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

namespace Espo\Core\Formula\Functions\LogGroup;

use Espo\Core\Formula\EvaluatedArgumentList;
use Espo\Core\Formula\Exceptions\BadArgumentType;
use Espo\Core\Formula\Exceptions\TooFewArguments;
use Espo\Core\Formula\Func;
use Espo\Core\Utils\Log;
use Psr\Log\LogLevel;
use stdClass;

class InfoType implements Func
{
    protected string $level = LogLevel::INFO;

    public function __construct(
        private Log $log
    ) {}

    public function process(EvaluatedArgumentList $arguments): mixed
    {
        if (count($arguments) < 1) {
            throw TooFewArguments::create(1);
        }

        $message = $arguments[0];
        $context = $arguments[1] ?? (object) [];

        if (!is_string($message)) {
            throw BadArgumentType::create(1, 'string');
        }

        if (!$context instanceof stdClass) {
            throw BadArgumentType::create(2, 'object');
        }

        $context = array_merge(get_object_vars($context), ['context' => 'formula']);

        $this->log->log($this->level, $message, $context);

        return null;
    }
}
