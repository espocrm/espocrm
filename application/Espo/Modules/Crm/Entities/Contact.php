<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2023 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

namespace Espo\Modules\Crm\Entities;

use Espo\Core\Entities\Person;
use Espo\Core\Field\Link;
use Espo\Core\Field\LinkMultiple;

class Contact extends Person
{
    public const ENTITY_TYPE = 'Contact';

    /**
     * An assigned user.
     */
    public function getAssignedUser(): ?Link
    {
        /** @var ?Link */
        return $this->getValueObject('assignedUser');
    }

    /**
     * A primary account.
     */
    public function getAccount(): ?Link
    {
        /** @var ?Link */
        return $this->getValueObject('account');
    }

    /**
     * Accounts.
     */
    public function getAccounts(): LinkMultiple
    {
        /** @var LinkMultiple */
        return $this->getValueObject('accounts');
    }

    /**
     * Teams.
     */
    public function getTeams(): LinkMultiple
    {
        /** @var LinkMultiple */
        return $this->getValueObject('teams');
    }

    /**
     * A title (for a primary account).
     */
    public function getTitle(): ?string
    {
        return $this->get('title');
    }
}
