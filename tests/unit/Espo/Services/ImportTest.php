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

namespace tests\unit\Espo\Core;

use Espo\Core\ServiceFactory;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\File\Manager as FileManager;
use Espo\Core\Utils\Metadata;
use Espo\Core\Acl;
use Espo\Core\Select\SelectBuilderFactory;

use Espo\Services\Import;


class ImportTest extends \PHPUnit\Framework\TestCase
{
    protected $objects;

    protected $importService;

    protected function setUp() : void
    {
        $this->objects['serviceFactory'] = $this->getMockBuilder(ServiceFactory::class)->disableOriginalConstructor()->getMock();

        $this->objects['config'] = $this->getMockBuilder(Config::class)->disableOriginalConstructor()->getMock();

        $this->objects['fileManager'] = $this->getMockBuilder(FileManager::class)->disableOriginalConstructor()->getMock();

        $this->objects['metadata'] = $this->getMockBuilder(Metadata::class)->disableOriginalConstructor()->getMock();

        $this->objects['acl'] = $this->getMockBuilder(Acl::class)->disableOriginalConstructor()->getMock();

        $this->selectBuilderFactory = $this->getMockBuilder(SelectBuilderFactory::class)->disableOriginalConstructor()->getMock();

        $this->importService = new Import($this->selectBuilderFactory);

        $this->importService->inject('serviceFactory', $this->objects['serviceFactory']);
        $this->importService->inject('config', $this->objects['config']);
        $this->importService->inject('fileManager', $this->objects['fileManager']);
        $this->importService->inject('metadata', $this->objects['metadata']);
        $this->importService->inject('acl', $this->objects['acl']);
    }

    protected function tearDown() : void
    {
        $this->importService = NULL;
    }

    public function testImportRow()
    {
        $this->assertTrue(true);
    }
}
