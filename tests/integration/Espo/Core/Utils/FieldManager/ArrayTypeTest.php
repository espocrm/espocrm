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

namespace tests\integration\Espo\Core\Utils\FieldManager;

use Espo\ORM\EntityManager;
use tests\integration\Core\BaseTestCase;

class ArrayTypeTest extends BaseTestCase
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
        $savedFieldDefs = $metadata->get('entityDefs.Account.fields.cTestArray');

        $this->assertArrayHasKey('type', $savedFieldDefs);
        $this->assertArrayHasKey('isCustom', $savedFieldDefs);
        $this->assertArrayHasKey('options', $savedFieldDefs);
        $this->assertEquals('array', $savedFieldDefs['type']);
        $this->assertTrue($savedFieldDefs['isCustom']);

        $entityManager = $app->getContainer()->getByClass(EntityManager::class);

        $account = $entityManager->getEntity('Account');
        $account->set([
            'name' => 'Test',
            'cTestArray' => ['option1', 'option3']
        ]);

        $entityManager->saveEntity($account);

        $account = $entityManager->getEntity('Account', $account->getId());
        $this->assertEquals(['option1', 'option3'], $account->get('cTestArray'));
    }

    public function testUpdate()
    {
        $this->testCreate();

        $app = $this->createApplication();

        $fieldManager = $this->createFieldManager($app);

        $fieldDefs = get_object_vars(json_decode($this->jsonFieldDefs));
        $fieldDefs['required'] = true;

        $fieldManager->update('Account', 'cTestArray', $fieldDefs);

        $this->getContainer()->get('dataManager')->rebuild(['Account']);

        $app = $this->createApplication();

        $metadata = $app->getContainer()->get('metadata');
        $savedFieldDefs = $metadata->get('entityDefs.Account.fields.cTestArray');

        $this->assertTrue($savedFieldDefs['required']);

        $entityManager = $app->getContainer()->getByClass(EntityManager::class);
        $account = $entityManager->getEntity('Account');
        $account->set([
            'name' => 'Test',
        ]);

        $entityManager->saveEntity($account);
    }
}
