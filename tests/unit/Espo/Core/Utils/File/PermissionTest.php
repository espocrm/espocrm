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

namespace tests\unit\Espo\Core\Utils\File;

use tests\unit\ReflectionHelper;

class PermissionTest extends \PHPUnit\Framework\TestCase
{
    protected $object;

    protected $objects;

    protected $reflection;

    protected $fileList;

    protected function setUp()
    {
        $this->objects['fileManager'] = $this->getMockBuilder('\Espo\Core\Utils\File\Manager')->disableOriginalConstructor()->getMock();

        $this->object = new \Espo\Core\Utils\File\Permission($this->objects['fileManager']);

        $this->reflection = new ReflectionHelper($this->object);

        $this->fileList = array(
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
        );
    }

    protected function tearDown()
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

    /*public function bestPossibleList()
    {
        $fileList = array(
            'application/Espo/Controllers',
            'application/Espo/Core',
            'application/Espo/Core/Cron',
            'application/Espo/Core/Loaders',
            'application/Espo/Core/Mail',
            'application/Espo/Core/Mail/Storage/Imap.php',
            'application/Espo/Core/SelectManagers/Base.php',
            'application/Espo/Core/Utils/Database/Orm',
            'application/Espo/Core/Utils/Database/Orm/Fields',
            'application/Espo/Core/Utils/Database/Orm/Relations',
            'application/Espo/Core/Utils',
            'application/Espo/Core/defaults/config.php',
            'application/Espo/Entities',
            'application/Espo/Hooks/Common/Stream.php',
            'application/Espo/Modules/Crm/Controllers/Opportunity.php',
            'application/Espo/Modules/Crm/Jobs/CheckInboundEmails.php',
            'application/Espo/Modules/Crm/Resources/i18n/de_DE',
            'application/Espo/Modules/Crm/Resources/i18n/en_US',
            'application/Espo/Modules/Crm/Resources/i18n/nl_NL',
            'application/Espo/Modules/Crm/Resources/i18n/pl_PL',
            'application/Espo/Modules/Crm/Resources/layouts/InboundEmail',
            'application/Espo/Modules/Crm/Resources/metadata/clientDefs/InboundEmail.json',
            'application/Espo/Modules/Crm/Resources/metadata/entityDefs',
            'application/Espo/Modules/Crm/Services',
            'application/Espo/Repositories',
            'application/Espo/Resources/i18n/de_DE',
            'application/Espo/Resources/i18n/en_US',
            'application/Espo/Resources/i18n/nl_NL',
            'application/Espo/Resources/i18n/pl_PL',
            'application/Espo/Resources/layouts/Email',
            'application/Espo/Resources/layouts/EmailAccount',
            'application/Espo/Resources/layouts/User/filters.json',
            'application/Espo/Resources/metadata/app',
            'application/Espo/Resources/metadata/clientDefs',
            'application/Espo/Resources/metadata/entityDefs',
            'application/Espo/Resources/metadata/integrations/Google.json',
            'application/Espo/Resources/metadata/scopes',
            'application/Espo/SelectManagers/EmailAccount.php',
            'application/Espo/Services',
            'install/core',
            'install/core/actions/settingsTest.php',
            'install/core/i18n/de_DE/install.json',
            'install/core/i18n/en_US/install.json',
            'install/core/i18n/es_ES/install.json',
            'install/core/i18n/nl_NL/install.json',
            'install/core/i18n/pl_PL/install.json',
            'install/core/i18n/ro_RO/install.json',
            'install/core/i18n/tr_TR/install.json',
            'install/js/install.js',
        );

        $result = array(
            'application/Espo/Controllers',
            'application/Espo/Core',
            'application/Espo/Entities',
            'application/Espo/Hooks/Common/Stream.php',
            'application/Espo/Modules/Crm',
            'application/Espo/Repositories',
            'application/Espo/Resources',
            'application/Espo/SelectManagers/EmailAccount.php',
            'application/Espo/Services',
            'install/core',
            'install/js/install.js',
        );
    }*/







}

?>
