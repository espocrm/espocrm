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

namespace tests\integration\Espo\Tools\Merge;

use Espo\Core\Action\Actions\Merge\Merger;
use Espo\Core\Action\Params;
use Espo\Core\Field\DateTimeOptional;
use Espo\Modules\Crm\Entities\Lead;
use Espo\Modules\Crm\Entities\Meeting;
use tests\integration\Core\BaseTestCase;

class MergeTest extends BaseTestCase
{
    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testMerge(): void
    {
        $em = $this->getEntityManager();

        $lead1 = $em->getRepositoryByClass(Lead::class)->getNew();
        $lead1->setLastName('L 1');
        $em->saveEntity($lead1);

        $lead2 = $em->getRepositoryByClass(Lead::class)->getNew();
        $lead2->setLastName('L 2');
        $em->saveEntity($lead2);

        $meeting = $em->getRepositoryByClass(Meeting::class)->getNew();
        $meeting->setParent($lead2);
        $meeting->setName('M');
        $meeting->setDateStart(DateTimeOptional::createNow());
        $em->saveEntity($meeting);

        $merger = $this->getInjectableFactory()->create(Merger::class);

        $merger->process(
            params: new Params(Lead::ENTITY_TYPE, $lead1->getId()),
            sourceIdList: [$lead2->getId()],
            data: (object) [],
        );

        $em->refreshEntity($meeting);

        $this->assertEquals($lead1->getId(), $meeting->getParent()?->getId());
        $this->assertEquals(Lead::ENTITY_TYPE, $meeting->getParent()?->getEntityType());

        $this->assertNull(
            $em->getEntityById(Lead::ENTITY_TYPE, $lead2->getId())
        );
    }
}
