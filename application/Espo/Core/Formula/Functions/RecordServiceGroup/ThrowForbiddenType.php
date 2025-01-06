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

namespace Espo\Core\Formula\Functions\RecordServiceGroup;

use Espo\Core\Exceptions\Error\Body;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\ForbiddenSilent;
use Espo\Core\Formula\ArgumentList;
use Espo\Core\Formula\Exceptions\Error;
use Espo\Core\Formula\Exceptions\ExecutionException;
use Espo\Core\Formula\Functions\BaseFunction;
use Espo\Core\Utils\Json;

class ThrowForbiddenType extends BaseFunction
{
    /**
     * @throws Error
     * @throws ExecutionException
     * @throws Forbidden
     */
    public function process(ArgumentList $args)
    {
        if (empty($this->getVariables()->__isRecordService)) {
            $this->throwError("Can be called only from API script.");
        }

        $message = isset($args[0]) ? $this->evaluate($args[0]) : '';
        $body = isset($args[1]) ? $this->evaluate($args[1]) : null;

        if ($body !== null) {
            throw ForbiddenSilent::createWithBody($message, Json::encode($body));
        }

        throw ForbiddenSilent::createWithBody($message, Body::create()->withMessage($message));
    }
}
