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

namespace tests\unit\Espo\Core\Mail;

use Espo\Entities\Attachment;
use Espo\Entities\Email;

use Espo\Core\Mail\Importer;
use Espo\Core\Mail\MessageWrapper;

class ImporterTest extends \PHPUnit\Framework\TestCase
{
    function setUp() : void
    {
        $GLOBALS['log'] = $this->getMockBuilder('Espo\\Core\\Utils\\Log')->disableOriginalConstructor()->getMock();

        $entityManager = $this->entityManager =
            $this->getMockBuilder('Espo\\Core\\ORM\\EntityManager')->disableOriginalConstructor()->getMock();

        $config = $this->config = $this->getMockBuilder('Espo\\Core\\Utils\\Config')->disableOriginalConstructor()->getMock();

        $emailRepository = $this->getMockBuilder('Espo\\Core\\Repositories\\Database')->disableOriginalConstructor()->getMock();
        $emptyRepository = $this->getMockBuilder('Espo\\Core\\Repositories\\Database')->disableOriginalConstructor()->getMock();

        $metadata = $this->getMockBuilder('Espo\\ORM\\Metadata')->disableOriginalConstructor()->getMock();

        $pdo = $this->getMockBuilder('Pdo')->disableOriginalConstructor()->getMock();

        $selectBuilder = $this->getMockBuilder('Espo\\ORM\\Repository\\RDBSelectBuilder')
            ->disableOriginalConstructor()->getMock();

        $emailRepository
            ->expects($this->any())
            ->method('where')
            ->will($this->returnValue($selectBuilder));

        $emailRepository
            ->expects($this->any())
            ->method('select')
            ->will($this->returnValue($selectBuilder));

        $entityManager
            ->expects($this->any())
            ->method('getPdo')
            ->will($this->returnValue($pdo));

        $entityManager
            ->expects($this->any())
            ->method('getMetadata')
            ->will($this->returnValue($metadata));

        $metadata
            ->expects($this->any())
            ->method('get')
            ->will($this->returnValue(null));

        $emptyRepository
            ->expects($this->any())
            ->method('where')
            ->will($this->returnValue($selectBuilder));

        $this->repositoryMap = [
             ['Email', $emailRepository],
             ['Account', $emptyRepository],
             ['Contact', $emptyRepository],
             ['Lead', $emptyRepository],
        ];

        $emailDefs = require('tests/unit/testData/Core/Mail/email_defs.php');

        $email = $this->email = new Email('Email', $emailDefs, $entityManager);

        $attachmentDefs = require('tests/unit/testData/Core/Mail/attachment_defs.php');

        $attachment = new Attachment('Attachment', $attachmentDefs, $entityManager);

        $this->attachment = $attachment;
    }

    function testImport1()
    {

        $entityManager = $this->entityManager;
        $config = $this->config;
        $email = $this->email;

        $entityManager
            ->expects($this->any())
            ->method('getRepository')
            ->will($this->returnValueMap($this->repositoryMap));

        $entityManager
            ->expects($this->exactly(2))
            ->method('saveEntity')
            ->with($this->isInstanceOf('\\Espo\\Entities\\Email'));

        $entityManager
            ->expects($this->any())
            ->method('getEntity')
            ->with($this->equalTo('Email'))
            ->will($this->returnValue($email));

        $config
            ->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap(array(
                array('b2cMode', false)
            )));

        $contents = file_get_contents('tests/unit/testData/Core/Mail/test_email_1.eml');

        $importer = new Importer($entityManager, $config);

        $message = new MessageWrapper();

        $message->setFullRawContent($contents);

        $email = $importer->importMessage($message, null, ['teamTestId'], ['userTestId']);

        $this->assertEquals('test 3', $email->get('name'));

        $teamIdList = $email->getLinkMultipleIdList('teams');

        $this->assertTrue(in_array('teamTestId', $teamIdList));

        $userIdList = $email->getLinkMultipleIdList('users');
        $this->assertTrue(in_array('userTestId', $userIdList));

        if (method_exists($this, 'assertStringContainsString')) {  /* PHPUnit 7+ */
            $this->assertStringContainsString('<br>Admin Test', $email->get('body'));
            $this->assertStringContainsString('Admin Test', $email->get('bodyPlain'));
        } else {  /* PHPUnit 6 */
            $this->assertContains('<br>Admin Test', $email->get('body'));
            $this->assertContains('Admin Test', $email->get('bodyPlain'));
        }

        $this->assertEquals('<e558c4dfc2a0f0d60f5ebff474c97ffc/1466410740/1950@espo>', $email->get('messageId'));
    }
}
