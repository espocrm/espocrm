<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2015 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
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
 ************************************************************************/

namespace Espo\Modules\Crm\Controllers;

use \Espo\Core\Exceptions\BadRequest;
use \Espo\Core\Exceptions\Forbidden;
use \Espo\Core\Exceptions\NotFound;

class MassEmail extends \Espo\Core\Controllers\Record
{
    public function postActionSendTest($params, $data)
    {
        if (empty($data['id']) || empty($data['targetList']) || !is_array($data['targetList'])) {
            throw new BadRequest();
        }

        $id = $data['id'];

        $targetList = [];
        foreach ($data['targetList'] as $item) {
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
}
