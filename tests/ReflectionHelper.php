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

namespace tests;

class ReflectionHelper
{       
    private $object;    
    
    private $reflection;    
    

    public function __construct($object)
    { 
        $this->object = $object;    
        
        $this->reflection = new \ReflectionClass(get_class($this->object));    
    }
    
    
    public function invokeMethod($methodName, array $parameters = array())
    {
        $method = $this->reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($this->object, $parameters);
    }

    public function getProperty($name)
    {
        $property = $this->reflection->getProperty($name);
        $property->setAccessible(true);
        return $property->getValue($this->object);
    }

    public function setProperty($name, $value)
    {
        $property = $this->reflection->getProperty($name);
        $property->setAccessible(true);
        $property->setValue($this->object, $value);
    }
    
    
}