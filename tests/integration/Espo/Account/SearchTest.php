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

namespace tests\integration\Espo\Account;

class SearchTest extends \tests\integration\Core\BaseTestCase
{
    protected $dataFile = 'Account/ChangeFields.php';

    protected $userName = 'admin';
    protected $password = '1';

    public function testSearchByName()
    {
        $service = $this->getContainer()->get('serviceFactory')->create('Account');

        $params = array(
            'where' => array(
                array(
                    'type' => 'textFilter',
                    'value' => 'Besha',
                ),
            ),
            'offset' => 0,
            'maxSize' => 20,
            'asc' => true,
            'sortBy' => 'name',
        );

        $result = $service->findEntities($params);

        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('collection', $result);

        $this->assertEquals(1, $result['total']);

        $this->assertInstanceOf('\\Espo\\ORM\\EntityCollection', $result['collection']);

        $list = $result['collection']->toArray();

        $this->assertEquals('53203b942850b', $list[0]['id']);
    }

    /*public function testSearchByName()
    {
        $result = $this->sendRequest('GET', 'Account', array(
            'maxSize' => 20,
            'offset' => 0,
            'sortBy' => 'name',
            'asc' => true,
            'where' => array(
                array(
                    'type' => 'textFilter',
                    'value' => 'Besha',
                ),
            ),
        ));

        $this->assertEquals(1, $result['total']);
        $this->assertEquals('53203b942850b', $result['list'][0]['id']);
    }*/
}
