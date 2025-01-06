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

namespace tests\integration\Espo\LeadCapture;

use Espo\Core\Record\CreateParams;
use Espo\Core\Record\ServiceContainer;
use Espo\Entities\LeadCapture;
use Espo\ORM\EntityManager;
use Espo\Tools\LeadCapture\CaptureService;
use tests\integration\Core\BaseTestCase;

class LeadCaptureTest extends BaseTestCase
{
    public function testCapture():void
    {
        $entityManager = $this->getContainer()->getByClass(EntityManager::class);

        $targetList = $entityManager->getNewEntity('TargetList');
        $entityManager->saveEntity($targetList);

        $team = $entityManager->getNewEntity('Team');
        $entityManager->saveEntity($team);

        $recordService = $this->getContainer()->getByClass(ServiceContainer::class)->getByClass(LeadCapture::class);
        $service = $this->getInjectableFactory()->create(CaptureService::class);

        $leadCaptureData = (object) [
            'name' => 'test',
            'subscribeToTargetList' => true,
            'targetListId' => $targetList->getId(),
            'targetTeamId' => $team->getId(),
            'fieldList' => ['name', 'emailAddress'],
            'leadSource' => 'Web Site'
        ];

        $leadCapture = $recordService->create($leadCaptureData, CreateParams::create());

        $this->assertNotEmpty($leadCapture->get('apiKey'));

        $data = (object) [
            'firstName' => 'Test',
            'lastName' => 'Tester',
            'emailAddress' => 'test@tester.com'
        ];

        $service->capture($leadCapture->get('apiKey'), $data);

        $lead = $entityManager
            ->getRDBRepository('Lead')
            ->where(['emailAddress' => 'test@tester.com'])
            ->findOne();

        $this->assertNotNull($lead);

        $this->assertEquals('Web Site', $lead->get('source'));
        $this->assertTrue($entityManager->getRelation($lead, 'teams')->isRelatedById($team->getId()));
        $this->assertTrue($entityManager->getRelation($lead, 'targetLists')->isRelatedById($targetList->getId()));
    }
}
