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

use Espo\Core\Exceptions\NotFound;
use Espo\Services\Integration as Service;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Api\Request;
use Espo\Entities\User;

use stdClass;

class Integration
{

    /**
     * @throws Forbidden
     */
    public function __construct(
        private Service $service,
        private User $user,
    ) {

        if (!$this->user->isAdmin()) {
            throw new Forbidden();
        }
    }

    /**
     * @throws Forbidden
     * @throws NotFound
     */
    public function getActionRead(Request $request): stdClass
    {
        /** @var string $id */
        $id = $request->getRouteParam('id');

        $entity = $this->service->read($id);

        return $entity->getValueMap();
    }

    /**
     * @throws Forbidden
     * @throws NotFound
     */
    public function putActionUpdate(Request $request): stdClass
    {
        /** @var string $id */
        $id = $request->getRouteParam('id');
        $data = $request->getParsedBody();

        $entity = $this->service->update($id, $data);

        return $entity->getValueMap();
    }
}
