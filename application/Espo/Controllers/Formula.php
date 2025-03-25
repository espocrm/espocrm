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

namespace Espo\Controllers;

use Espo\Core\Api\Request;
use Espo\Core\Exceptions\NotFoundSilent;
use Espo\Tools\Formula\Service;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\ForbiddenSilent;
use Espo\Entities\User;
use Espo\Core\Field\LinkParent;

use stdClass;

class Formula
{

    /**
     * @throws ForbiddenSilent
     */
    public function __construct(
        private Service $service,
        User $user,
    ) {
        if (!$user->isAdmin()) {
            throw new ForbiddenSilent();
        }
    }

    /**
     * @throws BadRequest
     */
    public function postActionCheckSyntax(Request $request): stdClass
    {
        $expression = $request->getParsedBody()->expression ?? null;

        if (!$expression || !is_string($expression)) {
            throw new BadRequest("No or non-string expression.");
        }

        return $this->service->checkSyntax($expression)->toStdClass();
    }

    /**
     * @throws BadRequest
     * @throws NotFoundSilent
     */
    public function postActionRun(Request $request): stdClass
    {
        $expression = $request->getParsedBody()->expression ?? null;
        $targetType = $request->getParsedBody()->targetType ?? null;
        $targetId = $request->getParsedBody()->targetId ?? null;

        if (!$expression || !is_string($expression)) {
            throw new BadRequest("No or non-string expression.");
        }

        $targetLink = null;

        if ($targetType && $targetId) {
            $targetLink = LinkParent::create($targetType, $targetId);
        }

        return $this->service->run($expression, $targetLink)->toStdClass();
    }
}
