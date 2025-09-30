<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

namespace tests\integration\Espo\Note;

use Espo\{
    Tools\Stream\Service as StreamService,
    ORM\EntityManager,
};

class AclTest extends \tests\integration\Core\BaseTestCase
{
    public function testProcessNoteAcl()
    {
        /* @var $em EntityManager*/
        $em = $this->getContainer()->get('entityManager');

        /* @var $streamService StreamService*/
        $streamService = $this->getContainer()->get('injectableFactory')->create(StreamService::class);

        $team1 = $em->createEntity('Team', [
            'name' => 'team-1',
        ]);

        $team2 = $em->createEntity('Team', [
            'name' => 'team-2',
        ]);

        $user1 = $em->createEntity('User', [
            'userName' => 'user-1',
            'lastName' => 'user-1',
        ]);

        $user2 = $em->createEntity('User', [
            'userName' => 'user-2',
            'lastName' => 'user-2',
        ]);

        $account = $em->createEntity('Account', [

        ]);

        // Opportunity

        $opportunity = $em->createEntity('Opportunity', [
            'assignedUserId' => $user1->getId(),
            'teamsIds' => [$team1->getId()],
            'accountId' => $account->getId(),
        ]);

        $streamService->noteCreate($opportunity);

        $note1 = $em
            ->getRDBRepository('Note')
            ->where([
                'type' => 'Create',
                'parentId' => $opportunity->getId(),
                'parentType' => $opportunity->getEntityType(),
            ])
            ->findOne();

        $this->assertEquals([$team1->getId()], $note1->getLinkMultipleIdList('teams'));
        $this->assertEquals([$user1->getId()], $note1->getLinkMultipleIdList('users'));

        $opportunity->set([
            'assignedUserId' => $user2->getId(),
            'teamsIds' => [$team2->getId()],
        ]);

        $em->saveEntity($opportunity);

        $note1 = $em->getEntityById('Note', $note1->getId());

        $this->assertEquals([$team2->getId()], $note1->getLinkMultipleIdList('teams'));
        $this->assertEquals([$user2->getId()], $note1->getLinkMultipleIdList('users'));

        // Meeting

        $meeting = $em->createEntity('Meeting', [
            'usersIds' => [$user1->getId()],
            'teamsIds' => [$team1->getId()],
            'parentId' => $account->getId(),
            'parentType' => $account->getEntityType(),
        ]);

        $streamService->noteRelate($meeting, $account);

        $note2 = $em
            ->getRDBRepository('Note')
            ->where([
                'type' => 'Relate',
                'relatedId' => $meeting->getId(),
                'relatedType' => $meeting->getEntityType(),
            ])
            ->findOne();

        $this->assertEquals([$team1->getId()], $note2->getLinkMultipleIdList('teams'));
        $this->assertEquals([$user1->getId()], $note2->getLinkMultipleIdList('users'));

        $meeting->set([
            'usersIds' => [$user2->getId()],
            'teamsIds' => [$team2->getId()],
        ]);

        $em->saveEntity($meeting);

        $note2 = $em->getEntityById('Note', $note2->getId());

        $this->assertEquals([$team2->getId()], $note2->getLinkMultipleIdList('teams'));
        $this->assertEquals([$user2->getId()], $note2->getLinkMultipleIdList('users'));
    }
}
