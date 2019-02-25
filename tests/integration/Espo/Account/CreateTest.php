<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

namespace tests\integration\Espo\Account;

class CreateTest extends \tests\integration\Core\BaseTestCase
{
    protected $dataFile = 'Account/ChangeFields.php';

    protected $userName = 'admin';
    protected $password = '1';

    public function testCreate()
    {
        $service = $this->getContainer()->get('serviceFactory')->create('Account');

        $entity = $service->createEntity((object) [
            'name' => 'Test Account',
            'emailAddress' => 'test@tester.com',
            'phoneNumber' => '123-456-789',
        ]);

        $this->assertInstanceOf('\\Espo\\ORM\\Entity', $entity);
        $this->assertTrue(!empty($entity->id));
    }

    /*public function testCreate()
    {
        $result = $this->sendRequest('POST', 'Account', '{"type":"","industry":"","assignedUserId":"1","assignedUserName":"Admin","name":"Test Account","emailAddressData":[{"emailAddress":"test@tester.com","primary":true,"optOut":false,"invalid":false,"lower":"test@tester.com"}],"emailAddress":"test@tester.com","phoneNumberData":[{"phoneNumber":"123-456-789","primary":true,"type":"Office"}],"phoneNumber":"123-456-789","website":"","sicCode":"","billingAddressPostalCode":"","billingAddressStreet":"","billingAddressState":"","billingAddressCity":"","billingAddressCountry":"","shippingAddressPostalCode":"","shippingAddressStreet":"","shippingAddressState":"","shippingAddressCity":"","shippingAddressCountry":"","description":"","teamsIds":[],"teamsNames":{}}');

        $this->assertTrue(!empty($result['id']));
    }*/

    /*public function testCreate2()
    {
        $result = $this->sendRequest('POST', 'Account', array(
            'name' => 'Test Account',
            'emailAddress' => 'test@tester.com',
            'phoneNumber' => '123-456-789',
        ));

        $this->assertTrue(!empty($result['id']));
    }*/
}
