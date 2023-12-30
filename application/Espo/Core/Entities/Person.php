<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2024 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

namespace Espo\Core\Entities;

use Espo\Core\Field\Address;
use Espo\Core\Field\EmailAddressGroup;
use Espo\Core\Field\PhoneNumberGroup;
use Espo\Core\ORM\Entity;
use Espo\Core\ORM\Helper;

use Espo\ORM\EntityManager;
use Espo\ORM\Value\ValueAccessorFactory;

class Person extends Entity
{
    private Helper $helper;

    public function __construct(
        string $entityType,
        array $defs,
        EntityManager $entityManager,
        Helper $helper,
        ?ValueAccessorFactory $valueAccessorFactory = null
    ) {
        parent::__construct($entityType, $defs, $entityManager, $valueAccessorFactory);

        $this->helper = $helper;
    }

    /**
     * @param string $value
     * @return void
     */
    protected function _setLastName($value)
    {
        $this->setInContainer('lastName', $value);

        $name = $this->helper->formatPersonName($this, 'name');

        $this->setInContainer('name', $name);
    }

    /**
     * @param string $value
     * @return void
     */
    protected function _setFirstName($value)
    {
        $this->setInContainer('firstName', $value);

        $name = $this->helper->formatPersonName($this, 'name');

        $this->setInContainer('name', $name);
    }

    /**
     * @param string $value
     * @return void
     */
    protected function _setMiddleName($value)
    {
        $this->setInContainer('middleName', $value);

        $name = $this->helper->formatPersonName($this, 'name');

        $this->setInContainer('name', $name);
    }

    /**
     * Get a primary email address.
     */
    public function getEmailAddress(): ?string
    {
        return $this->get('emailAddress');
    }

    /**
     * Get a primary phone number.
     */
    public function getPhoneNumber(): ?string
    {
        return $this->get('phoneNumber');
    }

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

    public function getName(): ?string
    {
        return $this->get('name');
    }

    public function getFirstName(): ?string
    {
        return $this->get('firstName');
    }

    public function getLastName(): ?string
    {
        return $this->get('lastName');
    }

    public function getMiddleName(): ?string
    {
        return $this->get('middleName');
    }

    public function setFirstName(?string $firstName): self
    {
        $this->set('firstName', $firstName);

        return $this;
    }

    public function setLastName(?string $lastName): self
    {
        $this->set('lastName', $lastName);

        return $this;
    }

    public function setMiddleName(?string $middleName): self
    {
        $this->set('middleName', $middleName);

        return $this;
    }

    public function setEmailAddressGroup(EmailAddressGroup $group): self
    {
        $this->setValueObject('emailAddress', $group);

        return $this;
    }

    public function setPhoneNumberGroup(PhoneNumberGroup $group): self
    {
        $this->setValueObject('phoneNumber', $group);

        return $this;
    }

    public function getAddress(): Address
    {
        /** @var Address */
        return $this->getValueObject('address');
    }

    public function setAddress(Address $address): self
    {
        $this->setValueObject('address', $address);

        return $this;
    }
}
