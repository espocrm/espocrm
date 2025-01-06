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

namespace Espo\Modules\Crm\Tools\MassEmail\Api;

use Espo\Core\Api\Action;
use Espo\Core\Api\Request;
use Espo\Core\Api\Response;
use Espo\Core\Api\ResponseComposer;
use Espo\Core\Exceptions\BadRequest;
use Espo\Modules\Crm\Tools\MassEmail\UnsubscribeService;

/** @noinspection PhpUnused */
class DeleteUnsubscribe implements Action
{
    public function __construct(private UnsubscribeService $service) {}

    public function process(Request $request): Response
    {
        $id = $request->getRouteParam('id');
        $hash = $request->getRouteParam('hash');
        $emailAddress = $request->getRouteParam('emailAddress');

        if ($hash && $emailAddress) {
            $this->service->subscribeAgainWithHash($emailAddress, $hash);

            return ResponseComposer::json(true);
        }

        if (!$id) {
            throw new BadRequest();
        }

        $this->service->subscribeAgain($id);

        return ResponseComposer::json(true);
    }
}
