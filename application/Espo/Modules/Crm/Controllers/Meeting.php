<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2021 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
 * Website: https://www.espocrm.com
 *
 * EspoCRM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * EspoCRM is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with EspoCRM. If not, see http://www.gnu.org/licenses/.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

namespace Espo\Modules\Crm\Controllers;

use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\NotFound;

use Espo\Core\Api\Request;

use Espo\Modules\Crm\Services\Meeting as Service;

class Meeting extends \Espo\Core\Controllers\Record
{
    public function postActionSendInvitations(Request $request)
    {
        $data = $request->getParsedBody();

        if (empty($data->id)) {
            throw new BadRequest();
        }

        /** @var \Espo\Modules\Crm\Entities\Meeting|null */
        $entity = $this->getMeetingService()->getEntity($data->id);

        if (!$entity) {
            throw new NotFound();
        }

        if (!$this->getAcl()->check($entity, 'edit')) {
            throw new Forbidden();
        }

        if (!$this->getAcl()->checkScope('Email', 'create')) {
            throw new Forbidden();
        }

        return $this->getMeetingService()->sendInvitations($entity);
    }

    public function postActionMassSetHeld(Request $request)
    {
        $data = $request->getParsedBody();

        if (empty($data->ids) || !is_array($data->ids)) {
            throw new BadRequest();
        }

        return $this->getMeetingService()->massSetHeld($data->ids);
    }

    public function postActionMassSetNotHeld(Request $request)
    {
        $data = $request->getParsedBody();

        if (empty($data->ids) || !is_array($data->ids)) {
            throw new BadRequest();
        }

        return $this->getMeetingService()->massSetNotHeld($data->ids);
    }

    public function postActionSetAcceptanceStatus(Request $request)
    {
        $data = $request->getParsedBody();

        if (empty($data->id) || empty($data->status)) {
            throw new BadRequest();
        }

        return $this->getMeetingService()->setAcceptanceStatus($data->id, $data->status);
    }

    private function getMeetingService(): Service
    {
        /** @var Service */
        return $this->getRecordService();
    }
}
