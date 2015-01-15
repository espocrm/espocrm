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

namespace tests\Espo\Core;

use tests\ReflectionHelper;


class ImportTest extends \PHPUnit_Framework_TestCase
{
    protected $objects;
    
    protected $importService;


    protected function setUp()
    {
        $this->objects['serviceFactory'] = $this->getMockBuilder('\Espo\Core\ServiceFactory')->disableOriginalConstructor()->getMock();

        $this->objects['config'] = $this->getMockBuilder('\Espo\Core\Utils\Config')->disableOriginalConstructor()->getMock();
        
        $this->objects['fileManager'] = $this->getMockBuilder('\Espo\Core\Utils\File\Manager')->disableOriginalConstructor()->getMock();

        $this->objects['metadata'] = $this->getMockBuilder('\Espo\Core\Utils\Metadata')->disableOriginalConstructor()->getMock();
        
        $this->objects['acl'] = $this->getMockBuilder('\Espo\Core\Acl')->disableOriginalConstructor()->getMock();


        $this->importService = new \Espo\Services\Import();
        $this->importService->inject('serviceFactory', $this->objects['serviceFactory']);
        $this->importService->inject('config', $this->objects['config']);
        $this->importService->inject('fileManager', $this->objects['fileManager']);
        $this->importService->inject('metadata', $this->objects['metadata']);
        $this->importService->inject('acl', $this->objects['acl']);
        
    }

    protected function tearDown()
    {
        $this->importService = NULL;
    }
    
    
    function testImportRow()
    {
            
    }
}

