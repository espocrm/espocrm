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

use Espo\Core\Api\Response;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Api\Request;
use Espo\Core\Utils\Json;
use Espo\Core\Utils\Metadata as MetadataUtil;
use Espo\Entities\User as UserEntity;
use Espo\Tools\App\MetadataService as Service;

use stdClass;

class Metadata
{
    private Service $service;
    private MetadataUtil $metadata;
    private UserEntity $user;

    public function __construct(
        Service $service,
        MetadataUtil $metadata,
        UserEntity $user
    ) {
        $this->service = $service;
        $this->metadata = $metadata;
        $this->user = $user;
    }

    public function getActionRead(Request $request): mixed
    {
        $key = $request->getQueryParam('key');

        if (is_string($key)) {
            return $this->service->getDataForFrontendByKey($key);
        }

        return $this->service->getDataForFrontend();
    }

    /**
     * @throws Forbidden
     */
    public function getActionGet(Request $request, Response $response): void
    {
        if (!$this->user->isAdmin()) {
            throw new Forbidden();
        }

        $key = $request->getQueryParam('key');

        $value = $this->metadata->get($key);

        $response->writeBody(Json::encode($value));
    }
}
