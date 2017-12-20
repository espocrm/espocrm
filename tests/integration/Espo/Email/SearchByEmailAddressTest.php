<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2018 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
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

namespace tests\integration\Espo\Email;

class SearchByEmailAddressTest extends \tests\integration\Core\BaseTestCase
{

    public function testSearchByEmailAddress()
    {
        $entityManager = $this->getContainer()->get('entityManager');

        $email = $entityManager->getEntity('Email');
        $email->set('from', 'test@test.com');
        $email->set('status', 'Archived');
        $entityManager->saveEntity($email);

        $emailService = $this->getApplication()->getContainer()->get('serviceFactory')->create('Email');

        $result = $emailService->findEntities(array(
            'where' => array(
                array(
                    'type' => 'equals',
                    'attribute' => 'emailAddress',
                    'value' => 'test@test.com'
                )
            )
        ));

        $this->assertArrayHasKey('collection', $result);
        $this->assertEquals(1, count($result['collection']));
    }
}
