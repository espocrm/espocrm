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

namespace tests\integration\Espo\Export;

use Espo\ORM\EntityManager;

use Espo\Core\{
    FileStorage\Manager as FileStorageManager,
    Select\SearchParams,
    Select\Where\Item as WhereItem,
};

use Espo\Tools\Export\{
    Factory,
    Params,
};

class ExportTest extends \tests\integration\Core\BaseTestCase
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var Factory
     */
    private $factory;

    /**
     * @var FileStorageManager
     */
    private $fileStorageManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->entityManager = $this->getContainer()->get('entityManager');

        $this->factory = $this->getContainer()
            ->get('injectableFactory')
            ->create(Factory::class);

        $this->fileStorageManager = $this->getContainer()->get('fileStorageManager');
    }

    public function testCsvWithFieldList(): void
    {
        $user =$this->entityManager->createEntity('User', [
            'id' => 'user-id',
            'userName' => 'user',
            'lastName' => 'User',
        ]);

        $this->entityManager->createEntity('Task', [
            'id' => '1',
            'name' => 'test-1',
            'assignedUserId' => $user->getId(),
        ]);

        $this->entityManager->createEntity('Task', [
            'id' => '2',
            'name' => 'test-2',
            'assignedUserId' => $user->getId(),
        ]);

        $this->entityManager->createEntity('Task', [
            'id' => '3',
            'name' => 'test-3',
        ]);

        $searchParams = SearchParams
            ::create()
            ->withWhere(WhereItem::fromRaw([
                'type' => 'equals',
                'attribute' => 'assignedUserId',
                'value' => $user->getId(),
            ]));

        $params = Params
            ::create('Task')
            ->withFieldList([
                'name',
                'assignedUser',
            ])
            ->withSearchParams($searchParams)
            ->withFormat('csv');

        $export = $this->factory->create();

        $attachmentId = $export
            ->setParams($params)
            ->run()
            ->getAttachmentId();

        $attachment = $this->entityManager->getEntity('Attachment', $attachmentId);

        $contents = $this->fileStorageManager->getContents($attachment);

        $exepectedContents =
            "name,assignedUserId,assignedUserName\n" .
            "test-2,user-id,User\n" .
            "test-1,user-id,User\n";

        $this->assertEquals($exepectedContents, $contents);
    }

    public function testCsvWithAttributeList(): void
    {
        $user = $this->entityManager->createEntity('User', [
            'id' => 'user-id',
            'userName' => 'user',
            'lastName' => 'User',
        ]);

        $this->entityManager->createEntity('Task', [
            'id' => '1',
            'name' => 'test-1',
            'assignedUserId' => $user->getId(),
        ]);

        $this->entityManager->createEntity('Task', [
            'id' => '2',
            'name' => 'test-2',
            'assignedUserId' => $user->getId(),
        ]);

        $params = Params
            ::create('Task')
            ->withAttributeList([
                'id',
                'name',
                'assignedUserId',
            ])
            ->withAccessControl(false)
            ->withFormat('csv');

        $export = $this->factory->create();

        $attachmentId = $export
            ->setParams($params)
            ->run()
            ->getAttachmentId();

        $attachment = $this->entityManager->getEntity('Attachment', $attachmentId);

        $contents = $this->fileStorageManager->getContents($attachment);

        $exepectedContents =
            "id,name,assignedUserId\n" .
            "2,test-2,user-id\n" .
            "1,test-1,user-id\n";

        $this->assertEquals($exepectedContents, $contents);
    }

    public function testCsvCollection(): void
    {
        $user = $this->entityManager->createEntity('User', [
            'id' => 'user-id',
            'userName' => 'user',
            'lastName' => 'User',
        ]);

        $this->entityManager->createEntity('Task', [
            'id' => '1',
            'name' => 'test-1',
            'assignedUserId' => $user->getId(),
        ]);

        $this->entityManager->createEntity('Task', [
            'id' => '2',
            'name' => 'test-2',
            'assignedUserId' => $user->getId(),
        ]);

        $this->entityManager->createEntity('Task', [
            'id' => '3',
            'name' => 'test-3',
        ]);

        $collection = $this->entityManager
            ->getRDBRepository('Task')
            ->where([
                'assignedUserId' => $user->getId(),
            ])
            ->order('id', 'ASC')
            ->find();

        $params = Params
            ::create('Task')
            ->withAttributeList([
                'name',
                'assignedUserId',
            ])
            ->withFieldList([
                'name',
                'assignedUser',
            ])
            ->withFormat('csv');

        $export = $this->factory->create();

        $attachmentId = $export
            ->setParams($params)
            ->setCollection($collection)
            ->run()
            ->getAttachmentId();

        $attachment = $this->entityManager->getEntity('Attachment', $attachmentId);

        $contents = $this->fileStorageManager->getContents($attachment);

        $exepectedContents =
            "name,assignedUserId\n" .
            "test-1,user-id\n" .
            "test-2,user-id\n";

        $this->assertEquals($exepectedContents, $contents);
    }
}
