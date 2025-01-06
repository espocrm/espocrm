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

namespace tests\integration\Espo\ORM;

use Espo\Entities\Team;
use Espo\Modules\Crm\Entities\Account;
use Espo\ORM\EntityManager;
use tests\integration\Core\BaseTestCase;

class EntityManagerTest extends BaseTestCase
{
    public function testRefreshEntity(): void
    {
        $app = $this->createApplication();

        /** @var EntityManager $em */
        $em = $app->getContainer()->getByClass(EntityManager::class);

        $team1 = $em->createEntity(Team::ENTITY_TYPE);
        $team2 = $em->createEntity(Team::ENTITY_TYPE);

        /** @var Account $account */
        $account = $em->createEntity(Account::ENTITY_TYPE, [
            'name' => 'Test',
            'teamsIds' => [$team1->getId()],
        ]);

        $account->set('name', 'Hello');

        $em->getRelation($account, 'teams')->relateById($team2->getId());

        $em->refreshEntity($account);

        $this->assertEquals('Test', $account->get('name'));
        $this->assertFalse($account->isAttributeChanged('name'));
        $this->assertEqualsCanonicalizing([$team1->getId(), $team2->getId()], $account->getLinkMultipleIdList('teams'));
    }
}
