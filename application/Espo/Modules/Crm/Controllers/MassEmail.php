<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

use \Espo\Core\Exceptions\BadRequest;
use \Espo\Core\Exceptions\Forbidden;
use \Espo\Core\Exceptions\NotFound;

class MassEmail extends \Espo\Core\Controllers\Record
{
    public function postActionSendTest($params, $data)
    {
        if (empty($data->id) || empty($data->targetList) || !is_array($data->targetList)) {
            throw new BadRequest();
        }

        $id = $data->id;

        $targetList = [];
        foreach ($data->targetList as $item) {
            if (empty($item->id) || empty($item->type)) continue;
            $targetId = $item->id;
            $targetType = $item->type;
            $target = $this->getEntityManager()->getEntity($targetType, $targetId);
            if (!$target) continue;
            if (!$this->getAcl()->check($target, 'read')) {
                continue;
            }
            $targetList[] = $target;
        }

        $massEmail = $this->getEntityManager()->getEntity('MassEmail', $id);
        if (!$massEmail) {
            throw new NotFound();
        }
        if (!$this->getAcl()->check($massEmail, 'read')) {
            throw new Forbidden();
        }

        $this->getRecordService()->createQueue($massEmail, true, $targetList);
        $this->getRecordService()->processSending($massEmail, true);
        return true;
    }

    public function getActionSmtpAccountDataList()
    {
        if (!$this->getAcl()->checkScope('MassEmail', 'create') && !$this->getAcl()->checkScope('MassEmail', 'edit')) {
            throw new Forbidden();
        }

        return $this->getServiceFactory()->create('MassEmail')->getSmtpAccountDataList();
    }
}
