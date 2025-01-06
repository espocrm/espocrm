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

use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Error;
use Espo\Core\Exceptions\Forbidden;

use Espo\Core\Acl;
use Espo\Core\Api\Request;

use Espo\Core\Exceptions\NotFound;
use Espo\Entities\Template as TemplateEntity;
use Espo\Tools\Pdf\MassService;

use stdClass;

class Pdf
{
    private MassService $service;
    private Acl $acl;

    public function __construct(MassService $service, Acl $acl)
    {
        $this->service = $service;
        $this->acl = $acl;
    }

    /**
     * @throws BadRequest
     * @throws Forbidden
     * @throws Error
     * @throws NotFound
     */
    public function postActionMassPrint(Request $request): stdClass
    {
        $data = $request->getParsedBody();

        if (empty($data->idList) || !is_array($data->idList)) {
            throw new BadRequest();
        }

        if (empty($data->entityType)) {
            throw new BadRequest();
        }

        if (empty($data->templateId)) {
            throw new BadRequest();
        }

        if (!$this->acl->checkScope(TemplateEntity::ENTITY_TYPE)) {
            throw new Forbidden();
        }

        if (!$this->acl->checkScope($data->entityType)) {
            throw new Forbidden();
        }

        $id = $this->service->generate($data->entityType, $data->idList, $data->templateId);

        return (object) [
            'id' => $id,
        ];
    }
}
