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

namespace tests\integration\Espo\GlobalSearch;

use Espo\Tools\GlobalSearch\Service;

class GlobalSearchTest extends \tests\integration\Core\BaseTestCase
{
    public function testSearch1()
    {
        $app = $this->createApplication();

        $em = $app->getContainer()->get('entityManager');

        $team = $em->createEntity('Team', [
            'name' => 'test',
        ]);

        $contact = $em->createEntity('Contact', [
            'lastName' => '1',
            'teamsIds' => [$team->getId()],
        ]);

        $account = $em->createEntity('Account', [
            'name' => '1',
            'teamsIds' => [$team->getId()],
        ]);
        $account = $em->createEntity('Account', [
            'name' => '2',
            'teamsIds' => [$team->getId()],
        ]);
        $account = $em->createEntity('Account', [
            'name' => '1',
        ]);

        $this->createUser([
            'userName' => 'tester',
            'teamsIds' => [$team->getId()],
        ], [
            'data' => [
                'Account' => [
                    'create' => 'no',
                    'read' => 'team',
                    'edit' => 'no',
                    'delete' => 'no',
                    'stream' => 'no',
                ],
                'Contact' => [
                    'create' => 'no',
                    'read' => 'team',
                    'edit' => 'no',
                    'delete' => 'no',
                    'stream' => 'no',
                ],
            ],
        ]);

        $this->auth('tester');

        $app = $this->createApplication(true);

        $service = $app->getContainer()->get('injectableFactory')->create(Service::class);

        $result = $service->find('1', 0, 10);

        $this->assertEquals(2, count($result->getCollection()));
    }
}
