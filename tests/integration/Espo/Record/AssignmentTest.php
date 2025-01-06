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

namespace tests\integration\Espo\Record;

use Espo\Core\Acl\Table;
use Espo\Core\Record\CreateParams;
use Espo\Core\Record\ServiceContainer;
use Espo\Entities\Team;
use Espo\Modules\Crm\Entities\Lead;
use tests\integration\Core\BaseTestCase;

class AssignmentTest extends BaseTestCase
{
    public function testAssignment1(): void
    {
        $team1 = $this->getEntityManager()->createEntity(Team::ENTITY_TYPE);

        $user1 = $this->createUser([
            'userName' => 'test1',
            'defaultTeamId' => $team1->getId(),
            'teamsIds' => [$team1->getId()],
        ], [
            'data' => [
                Lead::ENTITY_TYPE => [
                    'create' => Table::LEVEL_YES,
                    'read' => Table::LEVEL_OWN,
                    'edit' => Table::LEVEL_OWN,
                    'delete' => Table::LEVEL_OWN,
                ],
            ],
            'assignmentPermission' => Table::LEVEL_NO,
        ]);

        $this->auth('test1');
        $this->reCreateApplication();

        /**
         * @noinspection PhpUnhandledExceptionInspection
         * @var Lead $lead1
         */
        $lead1 = $this->getContainer()
            ->getByClass(ServiceContainer::class)
            ->getByClass(Lead::class)
            ->create((object) [
                'lastName' => 'Test 1',
            ], CreateParams::create());

        $this->assertEquals($user1->getId(), $lead1->getAssignedUser()?->getId());
        $this->assertEquals([$team1->getId()], $lead1->getLinkMultipleIdList('teams'));
    }
}
