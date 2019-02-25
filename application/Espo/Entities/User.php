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

namespace Espo\Entities;

class User extends \Espo\Core\Entities\Person
{
    public function isActive()
    {
        return $this->get('isActive');
    }

    public function isAdmin()
    {
        return $this->get('type') === 'admin' || $this->isSystem() || $this->isSuperAdmin();
    }

    public function isPortal()
    {
        return $this->get('type') === 'portal';
    }

    public function isPortalUser()
    {
        return $this->isPortal();
    }

    public function isRegular()
    {
        return $this->get('type') === 'regular' || ($this->has('type') && !$this->get('type'));
    }

    public function isApi()
    {
        return $this->get('type') === 'api';
    }

    public function isSystem()
    {
        return $this->get('type') === 'system';
    }

    public function isSuperAdmin()
    {
        return $this->get('type') === 'super-admin';
    }

    public function getTeamIdList()
    {
        if (!$this->has('teamsIds')) {
            $this->loadLinkMultipleField('teams');
        }
        return $this->get('teamsIds');
    }

    public function loadAccountField()
    {
        if ($this->get('contactId')) {
            $contact = $this->getEntityManager()->getEntity('Contact', $this->get('contactId'));
            if ($contact && $contact->get('accountId')) {
                $this->set('accountId', $contact->get('accountId'));
                $this->set('accountName', $contact->get('accountName'));
            }
        }
    }

    protected function _getName()
    {
        if (!array_key_exists('name', $this->valuesContainer) || !$this->valuesContainer['name']) {
            if ($this->get('userName')) {
                return $this->get('userName');
            }
        }
        return $this->valuesContainer['name'];
    }

    protected function _hasName()
    {
        if (array_key_exists('name', $this->valuesContainer)) {
            return true;
        }
        if ($this->has('userName')) {
            return true;
        }
        return false;
    }
}
