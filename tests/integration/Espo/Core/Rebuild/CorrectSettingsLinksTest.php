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


namespace tests\integration\Espo\Core\Rebuild;

use Espo\Core\Rebuild\Actions\CorrectSettingsLinks;
use Espo\Entities\Team;
use tests\integration\Core\BaseTestCase;

class CorrectSettingsLinksTest extends BaseTestCase
{
    public function testLink(): void
    {
        $team = $this->getEntityManager()->createEntity(Team::ENTITY_TYPE, ['name' => 'Team 1']);

        $this->setConfigParams([
            'ldapUserDefaultTeamId' => $team->getId(),
            'ldapUserDefaultTeamName' => 'Old Name',
            'baselineRoleId' => 'not-existing-id',
            'baselineRoleName' => 'Removed Role',
        ]);

        $this->getInjectableFactory()->create(CorrectSettingsLinks::class)->process();

        $config = $this->getConfig();

        $this->assertEquals($team->getId(), $config->get('ldapUserDefaultTeamId'));
        $this->assertEquals('Team 1', $config->get('ldapUserDefaultTeamName'));

        $this->assertEquals('not-existing-id', $config->get('baselineRoleId'));
        $this->assertNull($config->get('baselineRoleName'));
    }

    public function testLinkMultiple(): void
    {
        $em = $this->getEntityManager();

        $team1 = $em->createEntity(Team::ENTITY_TYPE, ['name' => 'Team 1']);
        $team2 = $em->createEntity(Team::ENTITY_TYPE, ['name' => 'Team 2']);

        $ids = [$team1->getId(), 'not-existing-id', $team2->getId()];

        $this->setConfigParams([
            'ldapUserTeamsIds' => $ids,
            'ldapUserTeamsNames' => (object) [
                $team1->getId() => 'Old Name',
                'not-existing-id' => 'Removed Team',
            ],
        ]);

        $this->getInjectableFactory()->create(CorrectSettingsLinks::class)->process();

        $config = $this->getConfig();

        $this->assertEquals($ids, $config->get('ldapUserTeamsIds'));

        $this->assertEquals(
            [
                $team1->getId() => 'Team 1',
                $team2->getId() => 'Team 2',
            ],
            (array) $config->get('ldapUserTeamsNames')
        );
    }
}
