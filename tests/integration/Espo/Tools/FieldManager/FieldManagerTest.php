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
use Espo\Core\Exceptions\Conflict;
use Espo\Core\ORM\Type\FieldType;
use Espo\Core\Utils\Language;
use Espo\Modules\Crm\Entities\Account;
use Espo\Tools\FieldManager\FieldManager;
use tests\integration\Core\BaseTestCase;

class FieldManagerTest extends BaseTestCase
{
    private function createFieldManager(): FieldManager
    {
        return $this->getInjectableFactory()->create(FieldManager::class);
    }

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testCreateExistingField(): void
    {
        $fieldManager = $this->createFieldManager();

        $name = $fieldManager->create(Account::ENTITY_TYPE, 'testVarchar', [
            'type' => FieldType::VARCHAR,
            'maxLength' => 50,
        ]);

        $this->assertEquals('cTestVarchar', $name);

        $this->expectException(Conflict::class);

        $fieldManager->create(Account::ENTITY_TYPE, 'testVarchar', [
            'type' => FieldType::VARCHAR,
            'maxLength' => 50,
        ]);
    }

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testUpdateCoreFieldWithChanges(): void
    {
        $fieldManager = $this->createFieldManager();

        $fieldManager->update(Account::ENTITY_TYPE, 'name', [
            'type' => FieldType::VARCHAR,
            'maxLength' => 100,
            'label' => 'Modified Name',
        ]);

        $this->assertTrue($fieldManager->isChanged());

        $this->reCreateApplication(reuse: true);

        $metadata = $this->getMetadata();
        $language = $this->getContainer()->getByClass(Language::class);

        $this->assertEquals(100, $metadata->get('entityDefs.Account.fields.name.maxLength'));
        $this->assertEquals(FieldType::VARCHAR, $metadata->get('entityDefs.Account.fields.name.type'));
        $this->assertNull($metadata->get('entityDefs.Account.fields.name.isCustom'));
        // Other core params remain intact.
        $this->assertTrue($metadata->get('entityDefs.Account.fields.name.required'));

        $customFieldDefs = $metadata->getCustom('entityDefs', Account::ENTITY_TYPE)?->fields?->name ?? null;

        $this->assertNotNull($customFieldDefs);
        $this->assertEquals(100, $customFieldDefs->maxLength);
        $this->assertObjectNotHasProperty('label', $customFieldDefs);
        $this->assertObjectNotHasProperty('isCustom', $customFieldDefs);

        $this->assertEquals('Modified Name', $language->translateLabel('name', 'fields', Account::ENTITY_TYPE));
    }

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testUpdateCoreFieldWithNoChanges(): void
    {
        $fieldManager = $this->createFieldManager();

        $metadata = $this->getMetadata();

        $maxLength = $metadata->get('entityDefs.Account.fields.name.maxLength');

        $this->assertNotNull($maxLength);

        $fieldManager->update(Account::ENTITY_TYPE, 'name', [
            'type' => FieldType::VARCHAR,
            'maxLength' => $maxLength,
            'required' => true,
            'readOnly' => false,
            'label' => 'Name',
        ]);

        $this->assertFalse($fieldManager->isChanged());

        $this->reCreateApplication(reuse: true);

        $metadata = $this->getMetadata();

        $customDefs = $metadata->getCustom('entityDefs', Account::ENTITY_TYPE);

        $this->assertFalse(isset($customDefs->fields->name));
        $this->assertEquals($maxLength, $metadata->get('entityDefs.Account.fields.name.maxLength'));
    }

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testUpdateCustomField(): void
    {
        $fieldManager = $this->createFieldManager();

        $name = $fieldManager->create(Account::ENTITY_TYPE, 'testVarchar', [
            'type' => FieldType::VARCHAR,
            'maxLength' => 50,
            'label' => 'Test Varchar',
        ]);

        $fieldManager = $this->createFieldManager();

        $fieldManager->update(Account::ENTITY_TYPE, $name, [
            'type' => FieldType::VARCHAR,
            'maxLength' => 150,
            'required' => true,
            'isCustom' => false,
        ]);

        $this->assertTrue($fieldManager->isChanged());

        $this->reCreateApplication(reuse: true);

        $metadata = $this->getMetadata();

        $defs = $metadata->get("entityDefs.Account.fields.$name");

        $this->assertEquals(FieldType::VARCHAR, $defs['type']);
        $this->assertEquals(150, $defs['maxLength']);
        $this->assertTrue($defs['required']);
        // Custom field remains custom.
        $this->assertTrue($defs['isCustom']);
    }

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testRead(): void
    {
        $fieldManager = $this->createFieldManager();

        $name = $fieldManager->create(Account::ENTITY_TYPE, 'testVarchar', [
            'type' => FieldType::VARCHAR,
            'maxLength' => 50,
            'label' => 'Var Name',
        ]);

        $this->reCreateApplication(reuse: true);

        $fieldManager = $this->getInjectableFactory()->create(FieldManager::class);

        $defs = $fieldManager->read(Account::ENTITY_TYPE, $name);

        $this->assertEquals(FieldType::VARCHAR, $defs['type']);
        $this->assertEquals(50, $defs['maxLength']);
        $this->assertTrue($defs['isCustom']);
        $this->assertEquals('Var Name', $defs['label']);

        $defs = $fieldManager->read(Account::ENTITY_TYPE, 'name');

        $this->assertEquals(FieldType::VARCHAR, $defs['type']);
        $this->assertArrayNotHasKey('isCustom', $defs);
        $this->assertEquals('Name', $defs['label']);
    }

    /**
     * A param defined in `fieldManagerAdditionalParamList` should be saved when the field
     * has fieldManagerParamList. The client does not send `fieldManagerAdditionalParamList` in the payload
     * (it's filtered out by fieldManagerParamList), so it must be obtained from metadata.
     *
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testAdditionalParamsWithParamList(): void
    {
        $metadata = $this->getMetadata();

        $metadata->set('entityDefs', Account::ENTITY_TYPE, [
            'fields' => [
                'testEnum' => [
                    'type' => FieldType::ENUM,
                    'options' => ['A', 'B', 'C'],
                    'fieldManagerParamList' => [
                        'required',
                        'testAdditional',
                        'testAdditionalBool',
                    ],
                    'fieldManagerAdditionalParamList' => [
                        [
                            'name' => 'testAdditional',
                            'view' => 'views/admin/field-manager/fields/not-actual-options',
                        ],
                        [
                            'name' => 'testAdditionalBool',
                            'type' => FieldType::BOOL,
                        ],
                    ],
                ],
            ],
        ]);

        $metadata->save();

        $fieldManager = $this->createFieldManager();

        // Mimics the payload from the front-end.
        $fieldManager->update(Account::ENTITY_TYPE, 'testEnum', [
            'type' => FieldType::ENUM,
            'options' => ['A', 'B', 'C'],
            'required' => true,
            'readOnly' => true,
            'testAdditional' => ['B', 'C'],
            'testAdditionalBool' => true,
            'fieldManagerParamList' => ['required', 'testAdditional', 'testAdditionalBool'],
            'fieldManagerAdditionalParamList' => [
                (object) [
                    'name' => 'testAdditional',
                    'view' => 'views/admin/field-manager/fields/not-actual-options',
                ],
                (object) [
                    'name' => 'testAdditionalBool',
                    'type' => FieldType::BOOL,
                ],
            ],
        ]);

        $this->assertTrue($fieldManager->isChanged());

        $this->reCreateApplication(reuse: true);

        $metadata = $this->getMetadata();

        $this->assertEquals(['B', 'C'], $metadata->get('entityDefs.Account.fields.testEnum.testAdditional'));
        $this->assertTrue($metadata->get('entityDefs.Account.fields.testEnum.testAdditionalBool'));
        $this->assertTrue($metadata->get('entityDefs.Account.fields.testEnum.required'));
        // Not in fieldManagerParamList.
        $this->assertNull($metadata->get('entityDefs.Account.fields.testEnum.readOnly'));
        // Definitions are not altered.
        $this->assertEquals(
            ['required', 'testAdditional', 'testAdditionalBool'],
            $metadata->get('entityDefs.Account.fields.testEnum.fieldManagerParamList')
        );
        $this->assertCount(2, $metadata->get('entityDefs.Account.fields.testEnum.fieldManagerAdditionalParamList'));

        // Update again with a changed value.
        $fieldManager = $this->getInjectableFactory()->create(FieldManager::class);

        $fieldManager->update(Account::ENTITY_TYPE, 'testEnum', [
            'testAdditional' => ['C'],
            'testAdditionalBool' => false,
        ]);

        $this->reCreateApplication(reuse: true);

        $metadata = $this->getMetadata();

        $this->assertEquals(['C'], $metadata->get('entityDefs.Account.fields.testEnum.testAdditional'));
        $this->assertFalse($metadata->get('entityDefs.Account.fields.testEnum.testAdditionalBool'));
    }

    /**
     * A param defined in `fieldManagerAdditionalParamList` should be saved even if
     * `fieldManagerAdditionalParamList` is not passed in the payload.
     *
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testAdditionalParamsWithoutParamList(): void
    {
        $metadata = $this->getMetadata();

        $metadata->set('entityDefs', Account::ENTITY_TYPE, [
            'fields' => [
                'testEnum' => [
                    'type' => FieldType::ENUM,
                    'options' => ['A', 'B', 'C'],
                    'fieldManagerAdditionalParamList' => [
                        [
                            'name' => 'testAdditional',
                        ],
                    ],
                ],
            ],
        ]);

        $metadata->save();

        $fieldManager = $this->createFieldManager();

        $fieldManager->update(Account::ENTITY_TYPE, 'testEnum', [
            'testAdditional' => ['A'],
            'testNotAllowed' => 'test',
        ]);

        $this->reCreateApplication(reuse: true);

        $metadata = $this->getMetadata();

        $this->assertEquals(['A'], $metadata->get('entityDefs.Account.fields.testEnum.testAdditional'));
        // Neither a field type param nor an additional param.
        $this->assertNull($metadata->get('entityDefs.Account.fields.testEnum.testNotAllowed'));
    }

    /**
     * Additional params are taken from the field's metadata, not from the payload. A param from
     * `fieldManagerAdditionalParamList` of one field should not be permitted for another field.
     *
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testAdditionalParamsNotFromPayload(): void
    {
        $metadata = $this->getMetadata();

        $metadata->set('entityDefs', Account::ENTITY_TYPE, [
            'fields' => [
                'testEnum1' => [
                    'type' => FieldType::ENUM,
                    'options' => ['A', 'B'],
                    'fieldManagerAdditionalParamList' => [
                        [
                            'name' => 'testAdditional',
                        ],
                    ],
                ],
                'testEnum2' => [
                    'type' => FieldType::ENUM,
                    'options' => ['A', 'B'],
                ],
            ],
        ]);

        $metadata->save();

        $fieldManager = $this->createFieldManager();

        $fieldManager->update(Account::ENTITY_TYPE, 'testEnum2', [
            'testAdditional' => ['A'],
            'fieldManagerAdditionalParamList' => [
                (object) ['name' => 'testAdditional'],
            ],
        ]);

        $this->reCreateApplication(reuse: true);

        $metadata = $this->getMetadata();

        $this->assertNull($metadata->get('entityDefs.Account.fields.testEnum2.testAdditional'));
    }

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
