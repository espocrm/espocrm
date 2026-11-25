<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2026 EspoCRM, Inc.
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

namespace tests\unit\Espo\Core\Mail;

use Espo\Core\Mail\Parser;
use Espo\Core\Utils\File\MimeType;
use Espo\Core\Utils\InlineAttachment\InlineAttachmentUtil;
use Espo\Entities\Attachment;
use Espo\Entities\Email;
use Espo\Core\Notification\AssignmentNotificator;
use Espo\ORM\Metadata;
use Espo\ORM\Name\Attribute;
use Espo\ORM\Value\ValueAccessor;
use Espo\ORM\Value\ValueAccessorFactory;
use Espo\Core\FieldProcessing\Relation\LinkMultipleSaver;
use Espo\Core\Job\JobSchedulerFactory;
use Espo\Core\Mail\Importer;
use Espo\Core\Mail\Importer\Data as ImporterData;
use Espo\Core\Mail\MessageWrapper;
use Espo\Core\Mail\ParserFactory;
use Espo\Core\Mail\Parsers\MailMimeParser;
use Espo\Core\Notification\AssignmentNotificatorFactory;
use Espo\Core\ORM\EntityManager;
use Espo\Core\Repositories\Database;
use Espo\Core\Utils\Config;
use Espo\ORM\Repository\RDBSelectBuilder;

use Espo\Tools\Email\GroupFolderApplier;
use PHPUnit\Framework\TestCase;

class ImporterTest extends TestCase
{
    private $emailRepository;
    private $config;
    private $assignmentNotificatorFactory;
    private $parserFactory;
    private $linkMultipleSaver;
    private $parentFinder;
    private $jobSchedulerFactory;
    private $duplicateFinder;
    private $email;
    private $repositoryMap;
    private $entityManager;

    protected function setUp(): void
    {
        $entityManager = $this->entityManager = $this->createMock(EntityManager::class);

        $this->config = $this->createMock(Config::class);

        $emailRepository = $this->createMock(Database::class);
        $emptyRepository = $this->createMock(Database::class);

        $metadata = $this->createMock(Metadata::class);

        $selectBuilder = $this->createMock(RDBSelectBuilder::class);

        $this->assignmentNotificatorFactory = $this->createMock(AssignmentNotificatorFactory::class);
        $this->parserFactory = $this->createMock(ParserFactory::class);
        $this->linkMultipleSaver = $this->createMock(LinkMultipleSaver::class);

        $this->parentFinder = $this->createMock(Importer\ParentFinder::class);

        $mimeType = $this->createMock(MimeType::class);

        $this->parserFactory
            ->expects($this->any())
            ->method('create')
            ->willReturnCallback(
                function () use ($mimeType) {
                    return new MailMimeParser(
                        entityManager: $this->entityManager,
                        mimeTypeUtil: $mimeType,
                    );
                }
            );

        $emailRepository
            ->expects($this->any())
            ->method('where')
            ->willReturn($selectBuilder);

        $emailRepository
            ->expects($this->any())
            ->method('select')
            ->willReturn($selectBuilder);

        $entityManager
            ->expects($this->any())
            ->method('getMetadata')
            ->willReturn($metadata);

        $metadata
            ->expects($this->any())
            ->method('get')
            ->willReturn(null);

        $emptyRepository
            ->expects($this->any())
            ->method('where')
            ->willReturn($selectBuilder);

        $this->emailRepository = $emailRepository;

        $this->repositoryMap = [
             [Email::class, $this->emailRepository],
             //['Account', $emptyRepository],
             //['Contact', $emptyRepository],
             //['Lead', $emptyRepository],
        ];

        $valueAccessor = $this->createMock(ValueAccessor::class);
        $valueAccessorFactory = $this->createMock(ValueAccessorFactory::class);

        $valueAccessorFactory
            ->expects($this->any())
            ->method('create')
            ->willReturn(
                $valueAccessor
            );

        $emailDefs = require('tests/unit/testData/Core/Mail/email_defs.php');
        //$attachmentDefs = require('tests/unit/testData/Core/Mail/attachment_defs.php');

        $this->email = new Email('Email', $emailDefs, $entityManager, $valueAccessorFactory);
        //$attachment = new Attachment('Attachment', $attachmentDefs, $entityManager, $valueAccessorFactory);

        $this->assignmentNotificatorFactory
            ->expects($this->any())
            ->method('create')
            ->willReturn(
                $this->createMock(AssignmentNotificator::class)
            );


        $this->duplicateFinder = $this->createMock(Importer\DuplicateFinder::class);

        $this->jobSchedulerFactory = $this->createMock(JobSchedulerFactory::class);
    }

    public function testImport1(): void
    {
        $entityManager = $this->entityManager;
        $config = $this->config;
        $email = $this->email;

        $entityManager
            ->expects($this->any())
            ->method('getRDBRepositoryByClass')
            ->willReturnMap($this->repositoryMap);

        $entityManager
            ->expects($this->exactly(1))
            ->method('saveEntity')
            ->with($this->isInstanceOf(Email::class))
            ->willReturnCallback(function (Email $entity) {
                $entity->set('id', 'test-id');
            });

        $this->emailRepository
            ->expects($this->once())
            ->method('getNew')
            ->willReturn($email);

        $config
            ->expects($this->any())
            ->method('get')
            ->willReturnMap(
                [
                    ['b2cMode', false]
                ]
            );

        $saver = $this->createMock(Importer\EmailSaver::class);

        $saver
            ->expects($this->exactly(1))
            ->method('save')
            ->with($this->isInstanceOf(Email::class));

        $contents = file_get_contents('tests/unit/testData/Core/Mail/test_email_1.eml');

        $importer = new Importer\DefaultImporter(
            entityManager: $entityManager,
            config: $config,
            notificatorFactory: $this->assignmentNotificatorFactory,
            parserFactory: $this->parserFactory,
            linkMultipleSaver: $this->linkMultipleSaver,
            duplicateFinder: $this->duplicateFinder,
            jobSchedulerFactory: $this->jobSchedulerFactory,
            parentFinder: $this->parentFinder,
            autoReplyDetector: $this->createMock(Importer\AutoReplyDetector::class),
            emailSaver: $saver,
            groupFolderApplier: $this->createMock(GroupFolderApplier::class),
        );

        $message = new MessageWrapper(0, null, null, $contents);

        $data = ImporterData
            ::create()
            ->withTeamIdList(['teamTestId'])
            ->withUserIdList(['userTestId']);

        $email = $importer->import($message, $data);

        $this->assertEquals('test 3', $email->get('name'));

        $userIdList = $email->getLinkMultipleIdList('users');
        $this->assertTrue(in_array('userTestId', $userIdList));

        $this->assertStringContainsString('<br>Admin Test', $email->get('body'));
        $this->assertStringContainsString('Admin Test', $email->get('bodyPlain'));

        $messageId = '<e558c4dfc2a0f0d60f5ebff474c97ffc/1466410740/1950@espo>';

        $this->assertEquals($messageId, $email->get('messageId'));
        $this->assertEquals($messageId . '-r@test.com', $email->get('messageIdInternal'));
    }

    /**
     * References to attachments not created by the parser are neutralized.
     *
     * @noinspection HtmlRequiredAltAttribute
     */
    public function testImportStripForeignInlineAttachments(): void
    {
        $entityManager = $this->entityManager;

        $valueAccessorFactory = $this->createMock(ValueAccessorFactory::class);

        $valueAccessorFactory
            ->expects($this->any())
            ->method('create')
            ->willReturn($this->createMock(ValueAccessor::class));

        $attachmentDefs = require('tests/unit/testData/Core/Mail/attachment_defs.php');

        $ownAttachment = new Attachment(
            entityType: Attachment::ENTITY_TYPE,
            defs: $attachmentDefs,
            entityManager: $entityManager,
            valueAccessorFactory: $valueAccessorFactory,
        );

        $ownAttachment->set(Attribute::ID, 'own1');

        $body =
            '<p>Hello</p>' .
            '<img src="?entryPoint=attachment&amp;id=own1">' .
            '<img src="?entryPoint=attachment&amp;id=foreign1">' .
            "<img src='?entryPoint=attachment&id=foreign2'>" .
            '<img src="https://crm.test/?entryPoint=attachment&amp;id=foreign3">' .
            '<p>See ?entryPoint=attachment&amp;id=foreign4 for details.</p>' .
            '<img src="?entryPoint&#61;attachment&amp;id=foreign5" width="1" height="1">';

        $expectedBody =
            '<p>Hello</p>' .
            '<img src="?entryPoint=attachment&amp;id=own1">' .
            '<img src="?entryPoint=%61ttachment&id=foreign1">' .
            "<img src='?entryPoint=%61ttachment&id=foreign2'>" .
            '<img src="https://crm.test/?entryPoint=%61ttachment&id=foreign3">' .
            '<p>See ?entryPoint=%61ttachment&id=foreign4 for details.</p>' .
            '<img src="?entryPoint=%61ttachment&amp;id=foreign5" width="1" height="1">';

        $parser = $this->createMock(Parser::class);

        $parser
            ->expects($this->any())
            ->method('getAddressNameMap')
            ->willReturn((object) []);

        $parser
            ->expects($this->once())
            ->method('getInlineAttachmentList')
            ->willReturnCallback(function ($message, Email $email) use ($body, $ownAttachment) {
                $email->setIsHtml();
                $email->setBody($body);

                return [$ownAttachment];
            });

        $entityManager
            ->expects($this->any())
            ->method('getRDBRepositoryByClass')
            ->willReturnMap($this->repositoryMap);

        $entityManager
            ->expects($this->any())
            ->method('saveEntity')
            ->willReturnCallback(function ($entity) {
                if ($entity instanceof Email) {
                    $entity->set(Attribute::ID, 'test-id');
                }
            });

        $this->emailRepository
            ->expects($this->once())
            ->method('getNew')
            ->willReturn($this->email);

        $importer = new Importer\DefaultImporter(
            entityManager: $entityManager,
            config: $this->config,
            notificatorFactory: $this->assignmentNotificatorFactory,
            parserFactory: $this->parserFactory,
            linkMultipleSaver: $this->linkMultipleSaver,
            duplicateFinder: $this->duplicateFinder,
            jobSchedulerFactory: $this->jobSchedulerFactory,
            parentFinder: $this->parentFinder,
            autoReplyDetector: $this->createMock(Importer\AutoReplyDetector::class),
            emailSaver: $this->createMock(Importer\EmailSaver::class),
            groupFolderApplier: $this->createMock(GroupFolderApplier::class),
        );

        $message = new MessageWrapper(
            id: 0,
            storage: null,
            parser: $parser,
            fullRawContent: '',
        );

        $email = $importer->import($message, ImporterData::create());

        $this->assertNotNull($email);
        $this->assertEquals($expectedBody, $email->getBody());
        $this->assertEquals(['own1'], InlineAttachmentUtil::extractIds($email->getBody() ?? ''));
    }
}
