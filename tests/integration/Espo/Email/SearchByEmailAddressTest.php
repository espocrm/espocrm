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

namespace tests\integration\Espo\Email;

use Espo\Core\Record\ServiceContainer;
use Espo\Core\Select\SearchParams;
use Espo\Entities\Email;
use integration\Core\NoTransaction;
use tests\integration\Core\BaseTestCase;

class SearchByEmailAddressTest extends BaseTestCase
{
    public function testSearchByEmailAddress()
    {
        $entityManager = $this->getEntityManager();

        $email = $entityManager->getNewEntity('Email');

        $email->set('from', 'test@test.com');
        $email->set('status', 'Archived');

        $entityManager->saveEntity($email);

        $emailService = $this->getApplication()
            ->getContainer()
            ->getByClass(ServiceContainer::class)
            ->getByClass(Email::class);

        $result = $emailService->find(
            SearchParams::fromRaw([
                'where' => [
                    [
                        'type' => 'equals',
                        'attribute' => 'emailAddress',
                        'value' => 'test@test.com'
                    ]
                ]
            ])
        );

        $this->assertEquals(1, count($result->getCollection()));
    }

    /**
     * Full-text search index is not applied for uncommited data.
     */
    #[NoTransaction]
    public function testTextSearch()
    {
        $entityManager = $this->getEntityManager();

        $email = $entityManager->getNewEntity('Email');

        $email->set('from', 'test@test.com');
        $email->set('status', 'Archived');
        $email->set('name', 'Improvements to our Privacy Policy');
        $email->set('body', 'name abc test');

        $entityManager->saveEntity($email);

        $emailService = $this->getApplication()
            ->getContainer()
            ->getByClass(ServiceContainer::class)
            ->getByClass(Email::class);

        $result = $emailService->find(
            SearchParams::fromRaw([
                'textFilter' => 'name abc'
            ])
        );

        $this->assertEquals(1, count($result->getCollection()));

        $result = $emailService->find(
             SearchParams::fromRaw([
                'textFilter' => 'Improvements to our Privacy Policy'
            ])
        );

        $this->assertEquals(1, count($result->getCollection()));
    }
}
