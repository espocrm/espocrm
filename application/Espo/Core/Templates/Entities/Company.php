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

namespace Espo\Core\Templates\Entities;

use Espo\Core\Field\Address;
use Espo\Core\Field\EmailAddressGroup;
use Espo\Core\Field\PhoneNumberGroup;
use Espo\Core\ORM\Entity;

class Company extends Entity
{
    public const TEMPLATE_TYPE = 'Company';

    public function getEmailAddressGroup(): EmailAddressGroup
    {
        /** @var EmailAddressGroup */
        return $this->getValueObject('emailAddress');
    }

    public function getPhoneNumberGroup(): PhoneNumberGroup
    {
        /** @var PhoneNumberGroup */
        return $this->getValueObject('phoneNumber');
    }

    public function setEmailAddressGroup(EmailAddressGroup $group): static
    {
        return $this->setValueObject('emailAddress', $group);
    }

    public function setPhoneNumberGroup(PhoneNumberGroup $group): static
    {
        return  $this->setValueObject('phoneNumber', $group);
    }

    public function getBillingAddress(): Address
    {
        /** @var Address */
        return $this->getValueObject('billingAddress');
    }

    public function setBillingAddress(Address $address): static
    {
        return $this->setValueObject('billingAddress', $address);
    }

    public function getShippingAddress(): Address
    {
        /** @var Address */
        return $this->getValueObject('shippingAddress');
    }

    public function setShippingAddress(Address $address): static
    {
        return $this->setValueObject('shippingAddress', $address);
    }
}

