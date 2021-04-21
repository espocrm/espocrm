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

namespace tests\integration\Espo\Core\FieldProcessing;

use Espo\Core\{
    ORM\EntityManager,
};

class FileTest extends \tests\integration\Core\BaseTestCase
{
    public function testFile1(): void
    {
        /* @var $entityManager EntityManager */
        $entityManager = $this->getContainer()->get('entityManager');

        $attachment1 = $entityManager->createEntity('Attachment', [
            'contents' => 'test-1',
            'relatedType' => 'Document',
        ]);

        $document = $entityManager->createEntity('Document', [
            'fileId' => $attachment1->getId(),
        ]);

        $attachment1 = $entityManager->getEntity('Attachment', $attachment1->getId());

        $this->assertEquals($document->getId(), $attachment1->get('relatedId'));

        $attachment2 = $entityManager->createEntity('Attachment', [
            'contents' => 'test-2',
            'relatedType' => 'Document',
        ]);

        $document->set('fileId', $attachment2->getId());

        $entityManager->saveEntity($document);

        $attachment2 = $entityManager->getEntity('Attachment', $attachment2->getId());

        $this->assertEquals($document->getId(), $attachment2->get('relatedId'));

        $attachment1 = $entityManager->getEntity('Attachment', $attachment1->getId());

        $this->assertNull($attachment1);
    }
}
