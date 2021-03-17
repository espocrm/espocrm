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

namespace Espo\Core\Entities;

use Espo\Core\{
    ORM\Entity,
    Fields\EmailAddressGroup,
    Fields\PhoneNumberGroup,
    Fields\Address,
};

class Person extends Entity
{
    protected function _setLastName($value)
    {
        $this->setValue('lastName', $value);

        $name = $this->getEntityManager()->getHelper()->formatPersonName($this, 'name');

        $this->setValue('name', $name);
    }

    protected function _setFirstName($value)
    {
        $this->setValue('firstName', $value);

        $name = $this->getEntityManager()->getHelper()->formatPersonName($this, 'name');

        $this->setValue('name', $name);
    }

    protected function _setMiddleName($value)
    {
        $this->setValue('middleName', $value);

        $name = $this->getEntityManager()->getHelper()->formatPersonName($this, 'name');

        $this->setValue('name', $name);
    }

    public function getEmailAddressGroup() : EmailAddressGroup
    {
        return $this->getValueObject('emailAddress');
    }

    public function getPhoneNumberGroup() : PhoneNumberGroup
    {
        return $this->getValueObject('phoneNumber');
    }

    public function setEmailAddressGroup(EmailAddressGroup $group) : void
    {
        $this->setValueObject('emailAddress', $group);
    }

    public function setPhoneNumberGroup(PhoneNumberGroup $group) : void
    {
        $this->setValueObject('phoneNumber', $group);
    }

    public function getAddress() : Address
    {
        return $this->getValueObject('address');
    }

    public function setAddress(Address $address) : void
    {
        $this->setValueObject('address', $address);
    }
}
