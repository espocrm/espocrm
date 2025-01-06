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

use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\BadRequest;

use Espo\Core\Acl;
use Espo\Core\Api\Request;
use Espo\Core\Api\Response;

use Espo\Core\Exceptions\NotFound;
use Espo\Tools\DataPrivacy\Erasor;

class DataPrivacy
{
    /**
     * @throws Forbidden
     */
    public function __construct(private Erasor $erasor, private Acl $acl)
    {
        if ($this->acl->getPermissionLevel(Acl\Permission::DATA_PRIVACY) === Acl\Table::LEVEL_NO) {
            throw new Forbidden();
        }
    }

    /**
     * @throws BadRequest
     * @throws Forbidden
     * @throws NotFound
     */
    public function postActionErase(Request $request, Response $response): void
    {
        $data = $request->getParsedBody();

        if (
            empty($data->entityType) ||
            empty($data->id) ||
            empty($data->fieldList) ||
            !is_array($data->fieldList)
        ) {
            throw new BadRequest();
        }

        $this->erasor->erase($data->entityType, $data->id, $data->fieldList);

        $response->writeBody('true');
    }
}
