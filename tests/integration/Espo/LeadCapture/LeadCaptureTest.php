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

namespace tests\integration\Espo\LeadCapture;

use Espo\Core\Record\CreateParams;

class LeadCaptureTest extends \tests\integration\Core\BaseTestCase
{
    public function testCaptute()
    {
        $entityManager = $this->getContainer()->get('entityManager');

        $targetList = $entityManager->getEntity('TargetList');
        $entityManager->saveEntity($targetList);

        $team = $entityManager->getEntity('Team');
        $entityManager->saveEntity($team);

        $leadCaptureService = $this->getContainer()->get('serviceFactory')->create('LeadCapture');

        $leadCapureData = (object) [
            'name' => 'test',
            'subscribeToTargetList' => true,
            'targetListId' => $targetList->id,
            'targetTeamId' => $team->id,
            'fieldList' => ['name', 'emailAddress'],
            'leadSource' => 'Web Site'
        ];
        
        $leadCapture = $leadCaptureService->create($leadCapureData, CreateParams::create());

        $this->assertNotEmpty($leadCapture->get('apiKey'));

        $data = (object) [
            'firstName' => 'Test',
            'lastName' => 'Tester',
            'emailAddress' => 'test@tester.com'
        ];

        $leadCaptureService->leadCapture($leadCapture->get('apiKey'), $data);

        $lead = $entityManager->getRepository('Lead')
            ->where(['emailAddress' => 'test@tester.com'])
            ->findOne();

        $this->assertNotNull($lead);

        $this->assertEquals('Web Site', $lead->get('source'));

        $this->assertTrue($entityManager->getRepository('Lead')->isRelated($lead, 'teams', $team->id));

        $this->assertTrue($entityManager->getRepository('Lead')->isRelated($lead, 'targetLists', $targetList->id));
    }
}
