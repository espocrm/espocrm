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

namespace tests\unit\Espo\Core\Utils\File;

use Espo\Core\Utils\File\Permission;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use tests\unit\ReflectionHelper;

class PermissionTest extends TestCase
{
    protected $object;
    protected $objects;
    protected $reflection;
    protected $fileList;

    protected function setUp() : void
    {
        $this->objects['fileManager'] = $this->getMockBuilder('\Espo\Core\Utils\File\Manager')->disableOriginalConstructor()->getMock();

        $this->object = new Permission($this->objects['fileManager']);

        $this->reflection = new ReflectionHelper($this->object);

        $this->fileList = [
            'application/Espo/Controllers/Email.php',
            'application/Espo/Controllers/EmailAccount.php',
            'application/Espo/Controllers/EmailAddress.php',
            'application/Espo/Controllers/ExternalAccount.php',
            'application/Espo/Controllers/Import.php',
            'application/Espo/Controllers/Integration.php',
            'application/Espo/Modules/Crm/Resources/i18n/pl_PL/Calendar.json',
            'application/Espo/Modules/Crm/Resources/i18n/pl_PL/Call.json',
            'application/Espo/Modules/Crm/Resources/i18n/pl_PL/Case.json',
            'application/Espo/Modules/Crm/Resources/i18n/pl_PL/Contact.json',
            'application/Espo/Modules/Crm/Resources/i18n/pl_PL/Global.json',
            'application/Espo/Resources/layouts/User/filters.json',
            'application/Espo/Resources/metadata/app/acl.json',
            'application/Espo/Resources/metadata/app/defaultDashboardLayout.json'
        ];
    }

    protected function tearDown() : void
    {
        $this->object = NULL;
    }

    public function testGetSearchCount()
    {
        $search = 'application/Espo/Controllers/';
        $methodResult = $this->reflection->invokeMethod('getSearchCount', array($search, $this->fileList));
        $result = 6;
        $this->assertEquals($result, $methodResult);


        $search = 'application/Espo/Controllers/Email.php';
        $methodResult = $this->reflection->invokeMethod('getSearchCount', array($search, $this->fileList));
        $result = 1;
        $this->assertEquals($result, $methodResult);

        $search = 'application/Espo/Controllers/NotReal';
        $methodResult = $this->reflection->invokeMethod('getSearchCount', array($search, $this->fileList));
        $result = 0;
        $this->assertEquals($result, $methodResult);
    }

    public function testArrangePermissionList()
    {
        $result = array(
            'application/Espo/Controllers',
            'application/Espo/Modules/Crm/Resources/i18n/pl_PL',
            'application/Espo/Resources/layouts/User/filters.json',
            'application/Espo/Resources/metadata/app',
        );
        $this->assertEquals( $result, $this->object->arrangePermissionList($this->fileList) );
    }

    static public function requiredPermissionsData()
    {
        return [
            ['data/config.php', '0775', '0664'],
            ['data/tmp/tmpivqW1X', '0775', '0664'],
            ['application/Espo/Core/Application.php', '0755', '0644'],
            ['custom/Espo/Custom/Resources/metadata/entityDefs', '0775', '0664'],
            ['custom/Espo/Custom/Resources/metadata/entityDefs/Account.json', '0775', '0664'],
            ['custom/Espo/Modules', '0775', '0664'],
            ['client/modules/crm/src', '0755', '0644'],
            ['client/modules/crm/src/views/account/detail.js', '0755', '0644'],
        ];
    }

    #[DataProvider('requiredPermissionsData')]
    public function testGetRequiredPermissions($path, $dirPermission, $filePermission)
    {
        $result = $this->object->getRequiredPermissions($path);

        $this->assertEquals($dirPermission, $result['dir']);
        $this->assertEquals($filePermission, $result['file']);
    }
}
