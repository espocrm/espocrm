<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014  Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

namespace Espo\Core\Entities;

class Person extends \Espo\Core\ORM\Entity
{
    public static $person = true;
    
    public function _setLastName($value)
    {
        $this->setValue('lastName', $value);
        
        $firstName = $this->get('firstName');
        if (empty($firstName)) {
            $this->setValue('name', $value);
        } else {
            $this->setValue('name', $firstName . ' ' . $value);
        }
    }
    
    public function _setFirstName($value)
    {
        $this->setValue('firstName', $value);
        
        $lastName = $this->get('lastName');
        if (empty($lastName)) {
            $this->setValue('name', $value);
        } else {
            $this->setValue('name', $value . ' ' . $lastName);
        }
    }
}

