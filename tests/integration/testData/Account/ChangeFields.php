<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2017 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

return array(
    'entities' => array(
        'User' => [
            array(
                'id' => '1',
                'type' => 'admin',
                'userName' => 'admin',
                'password' => '1',
                'salutationName' => '',
                'firstName' => '',
                'lastName' => 'Admin',
                'title' => '',
                'emailAddress' => 'demo@espocrm.com',
                'phoneNumberData' => array(
                    (object) array(
                        'phoneNumber' => '111',
                        'primary' => true,
                        'type' => 'Office',
                    ),
                ),
            ),
        ],
        'Account' => [
            array(
                'id' => '53203b942850b',
                'name' => 'Besharp',
                'website' => 'http://www.be.sharp.ca',
                'phoneNumberData' => array(
                    (object) array(
                        'phoneNumber' => '311-2233-11',
                        'primary' => true,
                        'type' => 'Office',
                    ),
                    (object) array(
                        'phoneNumber' => '311-2233-12',
                        'type' => 'Fax',
                    ),
                ),
                'type' => 'Customer',
                'industry' => 'Apparel',
                'sicCode' => '',
                'billingAddressStreet' => '130 Somerset Street West',
                'billingAddressCity' => 'Ottawa',
                'billingAddressState' => 'Ontario',
                'billingAddressCountry' => 'Canada',
                'billingAddressPostalCode' => 'K3R 0F7',
                'shippingAddressStreet' => '130 Somerset Street West',
                'shippingAddressCity' => 'Ottawa',
                'shippingAddressState' => 'Ontario',
                'shippingAddressCountry' => 'Canada',
                'shippingAddressPostalCode' => 'K3R 0F7',
                'description' => '',
                'emailAddress' => 'supp@be.sharp-example.ca',
                'assignedUserId' => '1',
            ),
            array(
                'id' => '53203b9428546',
                'name' => 'Mein Heimathaus',
                'website' => 'http://www.meinheimathaus.de',
                'phoneNumberData' => array(
                    (object) array(
                        'phoneNumber' => '165-681-158',
                        'primary' => true,
                        'type' => 'Office',
                    ),
                    (object) array(
                        'phoneNumber' => '165-681-159',
                        'type' => 'Other',
                    ),
                ),
                'type' => 'Partner',
                'industry' => 'Finance',
                'sicCode' => '',
                'billingAddressStreet' => 'Goethestraße 23',
                'billingAddressCity' => 'Berlin',
                'billingAddressState' => 'Berlin',
                'billingAddressCountry' => 'Germany',
                'billingAddressPostalCode' => '10623',
                'shippingAddressStreet' => 'Goethestraße 23',
                'shippingAddressCity' => 'Berlin',
                'shippingAddressState' => 'Berlin',
                'shippingAddressCountry' => 'Germany',
                'shippingAddressPostalCode' => '10623',
                'description' => '',
                'emailAddress' => 'supp@be1.sharp-example.ca',
                'assignedUserId' => '1',
            ),
        ],
    ),
);