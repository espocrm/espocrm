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

namespace Espo\Modules\Crm\Controllers;

use Espo\Core\Controllers\Record;
use Espo\Core\Exceptions\Error;
use Espo\Core\Exceptions\NotFound;
use Espo\Core\Mail\Exceptions\NoSmtp;
use Espo\Modules\Crm\Entities\MassEmail as MassEmailEntity;
use Espo\Modules\Crm\Tools\MassEmail\Service;

use Espo\Core\Acl\Table;
use Espo\Core\Api\Request;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Forbidden;

use stdClass;

class MassEmail extends Record
{
    /**
     * @throws BadRequest
     * @throws Forbidden
     * @throws Error
     * @throws NoSmtp
     * @throws NotFound
     */
    public function postActionSendTest(Request $request): bool
    {
        $id = $request->getParsedBody()->id ?? null;
        $targetList = $request->getParsedBody()->targetList ?? null;

        if (!$id || !is_array($targetList)) {
            throw new BadRequest();
        }

        $this->getMassEmailService()->processTest($id, $targetList);

        return true;
    }

    /**
     * @return stdClass[]
     * @throws Forbidden
     */
    public function getActionSmtpAccountDataList(): array
    {
        if (
            !$this->acl->checkScope(MassEmailEntity::ENTITY_TYPE, Table::ACTION_CREATE) &&
            !$this->acl->checkScope(MassEmailEntity::ENTITY_TYPE, Table::ACTION_EDIT)
        ) {
            throw new Forbidden();
        }

        return $this->getMassEmailService()->getSmtpAccountDataList();
    }

    private function getMassEmailService(): Service
    {
        return $this->injectableFactory->create(Service::class);
    }
}
