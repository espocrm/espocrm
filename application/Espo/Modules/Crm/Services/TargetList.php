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

namespace Espo\Modules\Crm\Services;

use \Espo\ORM\Entity;

class TargetList extends \Espo\Services\Record
{
    protected function loadAdditionalFields(Entity $entity)
    {
        parent::loadAdditionalFields($entity);
        $this->loadEntryCountField($entity);
    }

    protected function loadAdditionalFieldsForList(Entity $entity)
    {
        parent::loadAdditionalFields($entity);
        $this->loadEntryCountField($entity);
    }

    protected function loadEntryCountField(Entity $entity)
    {
        $count = 0;
        $count += $this->getEntityManager()->getRepository('TargetList')->countRelated($entity, 'contacts');
        $count += $this->getEntityManager()->getRepository('TargetList')->countRelated($entity, 'leads');
        $count += $this->getEntityManager()->getRepository('TargetList')->countRelated($entity, 'users');
        $count += $this->getEntityManager()->getRepository('TargetList')->countRelated($entity, 'accounts');
        $entity->set('entryCount', $count);
    }
}

