<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2021 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

namespace tests\integration\Espo\Core\Utils\FieldManager;

class ArrayTypeTest extends \tests\integration\Core\BaseTestCase
{
    private $jsonFieldDefs = '{
        "name":"testArray",
        "label":"TestArray",
        "type":"array",
        "required":false,
        "noEmptyString":false,
        "dynamicLogicVisible":null,
        "dynamicLogicRequired":null,
        "dynamicLogicReadOnly":null,
        "dynamicLogicOptions":null,
        "options":["option1","option2","option3"],
        "translatedOptions":{"option1":"option1","option2":"option2","option3":"option3"},
        "tooltipText":null,
        "isPersonalData":false,
        "inlineEditDisabled":false,
        "audited":false,
        "readOnly":false,
        "tooltip":false
    }';

    protected function createFieldManager($app = null)
    {
        if (!$app) {
            $app = $this;
        }

        return $app->getContainer()->get('injectableFactory')->create(
            'Espo\\Tools\\FieldManager\\FieldManager'
        );
    }

    public function testCreate()
    {
        $fieldManager = $this->createFieldManager();

        $fieldDefs = get_object_vars(json_decode($this->jsonFieldDefs));

        $fieldManager->create('Account', 'testArray', $fieldDefs);

        $this->getContainer()->get('dataManager')->rebuild(['Account']);

        $app = $this->createApplication();

        $metadata = $app->getContainer()->get('metadata');
        $savedFieldDefs = $metadata->get('entityDefs.Account.fields.testArray');

        $this->assertArrayHasKey('type', $savedFieldDefs);
        $this->assertArrayHasKey('isCustom', $savedFieldDefs);
        $this->assertArrayHasKey('options', $savedFieldDefs);
        $this->assertEquals('array', $savedFieldDefs['type']);
        $this->assertTrue($savedFieldDefs['isCustom']);

        $entityManager = $app->getContainer()->get('entityManager');
        $account = $entityManager->getEntity('Account');
        $account->set([
            'name' => 'Test',
            'testArray' => ['option1', 'option3']
        ]);

        $entityManager->saveEntity($account);

        $account = $entityManager->getEntity('Account', $account->id);
        $this->assertEquals(['option1', 'option3'], $account->get('testArray'));
    }

    public function testUpdate()
    {
        $this->testCreate();

        $app = $this->createApplication();

        $fieldManager = $this->createFieldManager($app);

        $fieldDefs = get_object_vars(json_decode($this->jsonFieldDefs));
        $fieldDefs['required'] = true;

        $fieldManager->update('Account', 'testArray', $fieldDefs);

        $this->getContainer()->get('dataManager')->rebuild(['Account']);

        $app = $this->createApplication();

        $metadata = $app->getContainer()->get('metadata');
        $savedFieldDefs = $metadata->get('entityDefs.Account.fields.testArray');

        $this->assertTrue($savedFieldDefs['required']);

        $entityManager = $app->getContainer()->get('entityManager');
        $account = $entityManager->getEntity('Account');
        $account->set([
            'name' => 'Test',
        ]);

        $entityManager->saveEntity($account);
    }
}
