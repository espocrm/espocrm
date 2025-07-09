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

namespace tests\integration\Espo\Core\Select;

use Espo\Core\Application;
use Espo\Core\Select\SelectManagerFactory;
use tests\integration\Core\BaseTestCase;

class LegacyTest extends BaseTestCase
{
    /**
     * @var SelectManagerFactory
     */
    private $selectManagerFactory;


    protected function setUp(): void
    {
        parent::setUp();

        $injectableFactory = $this->getContainer()->get('injectableFactory');

        $this->selectManagerFactory = $injectableFactory->create(SelectManagerFactory::class);
    }

    protected function initTest(array $aclData = [], bool $skipLogin = false, bool $isPortal = false): Application
    {
        $this->createUser('tester', [
            'data' => $aclData,
        ]);

        if (!$skipLogin) {
            $this->auth('tester');
        }

        $app = $this->createApplication();

        $injectableFactory = $app->getContainer()->get('injectableFactory');

        $this->selectManagerFactory = $injectableFactory->create(SelectManagerFactory::class);

        $app->getContainer()->get('user');

        return $app;
    }


    public function testAccess1(): void
    {
        $app = $this->initTest(
            [
                'Account' => [
                    'read' => 'own',
                ],
            ]
        );

        $container = $app->getContainer();

        $userId = $container->get('user')->getId();

        $selectManager = $this->selectManagerFactory->create('Account');

        $result = $selectManager->getEmptySelectParams();

        $selectManager->applyAccess($result);

        $this->assertEquals(['assignedUserId' => $userId], $result['whereClause']);
    }

    public function testGetSelectParams(): void
    {
        $app = $this->initTest(
            [
                'Opportunity' => [
                    'read' => 'own',
                ],
            ]
        );

        $container = $app->getContainer();

        $userId = $container->get('user')->getId();

        $selectManager = $this->selectManagerFactory->create('Opportunity');

        $params = [
            'primaryFilter' => 'open',
        ];

        $result = $selectManager->getSelectParams($params, true, true, true);


        $this->assertEquals(
            [
                'assignedUserId' => $userId,
                'stage!=' => ['Closed Won', 'Closed Lost'],
            ],
            $result['whereClause']
        );
    }
}
