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

namespace tests\integration\Espo\Tools\FieldManager;

use Espo\Core\Binding\BindingContainerBuilder;
use Espo\Core\ORM\Type\FieldType;
use Espo\Modules\Crm\Entities\Account;
use Espo\Tools\FieldManager\FieldManager;
use tests\integration\Core\BaseTestCase;

class FieldManagerTest extends BaseTestCase
{
    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testFiltererParams(): void
    {
        $fieldManager = $this->getInjectableFactory()->createWithBinding(
            FieldManager::class,
            BindingContainerBuilder::create()
                ->build()
        );

        $metadata = $this->getMetadata();

        $metadata->set('entityDefs', Account::ENTITY_TYPE, [
            'fields' => [
                'testVarchar' => [
                    'type' => FieldType::VARCHAR,
                    'fieldManagerParamList' => [
                        'required',
                    ],
                ],
                'testLink' => [
                    'type' => FieldType::LINK,
                    'fieldManagerParamList' => [
                        'default',
                    ],
                ],
            ],
        ]);

        $metadata->save();

        $fieldManager->update(Account::ENTITY_TYPE, 'testVarchar', [
            'required' => true,
            'readOnly' => true,
        ]);

        $this->assertTrue($metadata->get("entityDefs.Account.fields.testVarchar.required"));
        $this->assertNull($metadata->get("entityDefs.Account.fields.testVarchar.readOnly"));

        $fieldManager->update(Account::ENTITY_TYPE, 'testLink', [
            'defaultAttributes' => (object) [
                'testLinkId' => 'test-id',
            ],
        ]);

        $this->assertEquals(
            'test-id',
            $metadata->get("entityDefs.Account.fields.testLink.defaultAttributes.testLinkId")
        );
    }
}
