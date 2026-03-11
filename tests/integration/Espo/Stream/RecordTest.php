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

namespace tests\integration\Espo\Stream;

use Espo\Core\Acl\Table;
use Espo\Core\ORM\Repository\Option\SaveOption;
use Espo\Core\Select\SearchParams;
use Espo\Entities\Note;
use Espo\Entities\Portal;
use Espo\Modules\Crm\Entities\CaseObj;
use Espo\Tools\Stream\RecordService;
use tests\integration\Core\BaseTestCase;

class RecordTest extends BaseTestCase
{
    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testPortal(): void
    {
        $em = $this->getEntityManager();

        $portal = $em->createEntity(Portal::ENTITY_TYPE, ['name' => 'Test']);

        $portalUser = $this->createUser([
            'userName' => 'test',
            'portalsIds' => [$portal->getId()],
        ], [
            'data' => [
                CaseObj::ENTITY_TYPE => [
                    Table::ACTION_READ => Table::LEVEL_OWN,
                    Table::ACTION_STREAM => Table::LEVEL_OWN,
                ]
            ],
        ], isPortal: true);

        $case = $em->createEntity(CaseObj::ENTITY_TYPE, [
            'name' => 'Test',
        ], [SaveOption::CREATED_BY_ID => $portalUser->getId(), SaveOption::SILENT => true]);

        $note = $em->getRDBRepositoryByClass(Note::class)->getNew();
        $note->setParent($case);
        $note->setType(Note::TYPE_POST);
        $em->saveEntity($note);

        $notePinned = $em->getRDBRepositoryByClass(Note::class)->getNew();
        $notePinned->setParent($case);
        $notePinned->setType(Note::TYPE_POST);
        $notePinned->setIsPinned(true);
        $em->saveEntity($notePinned);

        $noteInternal = $em->getRDBRepositoryByClass(Note::class)->getNew();
        $noteInternal->setParent($case);
        $noteInternal->setType(Note::TYPE_POST);
        $noteInternal->setIsInternal(true);
        $em->saveEntity($noteInternal);

        $noteInternalPinned = $em->getRDBRepositoryByClass(Note::class)->getNew();
        $noteInternalPinned->setParent($case);
        $noteInternalPinned->setType(Note::TYPE_POST);
        $noteInternalPinned->setIsPinned(true);
        $noteInternalPinned->setIsInternal(true);
        $em->saveEntity($noteInternalPinned);

        $this->auth('test', portalId: $portal->getId());

        $app = $this->createApplication(portalId: $portal->getId());
        $this->setApplication($app);

        $service = $this->getInjectableFactory()->create(RecordService::class);

        $collection = $service->find(CaseObj::ENTITY_TYPE, $case->getId(), SearchParams::create())->getCollection();
        $pinnedCollection = $service->getPinned(CaseObj::ENTITY_TYPE, $case->getId());

        $this->assertCount(2, $collection);
        $this->assertEquals($note->getId(), $collection[1]->getId());
        $this->assertEquals($notePinned->getId(), $collection[0]->getId());

        $this->assertCount(1, $pinnedCollection);
        $this->assertEquals($notePinned->getId(), $collection[0]->getId());
    }
}
