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

namespace tests\unit\Espo\Tools\MassUpdate;

use Espo\Core\MassAction\Data as MassActionData;
use Espo\Tools\MassUpdate\Data;
use Espo\Tools\MassUpdate\Action;

class DataTest extends \PHPUnit\Framework\TestCase
{
    public function testData1(): void
    {
        $massActionData = MassActionData::fromRaw(
            (object) [
                'values' => (object) [
                    'a1' => '1',
                    'a2' => '2',
                    'a3' => '3',
                ],
                'actions' => (object) [
                    'a1' => 'update',
                    'a2' => 'add',
                    'a3' => 'remove',
                ],
            ],
        );

        $data = Data::fromMassActionData($massActionData);

        $this->assertEquals('1', $data->getValue('a1'));
        $this->assertEquals(null, $data->getValue('a0'));
        $this->assertEquals(true, $data->has('a1'));
        $this->assertEquals(false, $data->has('a0'));

        $this->assertEquals(['a1', 'a2', 'a3'], $data->getAttributeList());
        $this->assertEquals(
            (object) [
                    'a1' => '1',
                    'a2' => '2',
                    'a3' => '3',
                ],
            $data->getValues()
        );

        $this->assertEquals(Action::UPDATE, $data->getAction('a1'));
        $this->assertEquals(Action::ADD, $data->getAction('a2'));
        $this->assertEquals(Action::REMOVE, $data->getAction('a3'));

        $this->assertEquals('1m', $data->with('a1', '1m')->getValue('a1'));
        $this->assertEquals(false, $data->without('a1')->has('a1'));

        $massActionDataModified = $data
            ->with('a1', '1m', Action::ADD)
            ->without('a2')
            ->toMassActionData();

        $values = $massActionDataModified->get('values');
        $actions = $massActionDataModified->get('actions');

        $this->assertEquals('1m', $values->a1);
        $this->assertFalse(property_exists($values, 'a2'));

        $this->assertEquals(Action::ADD, $actions->a1);
        $this->assertFalse(property_exists($actions, 'a2'));
    }

    public function testCreate(): void
    {
        $data = Data::create()
            ->with('a1', null, Action::UPDATE)
            ->with('a2', ['1'], Action::ADD);

        $this->assertEquals(null, $data->getValue('a1'));
        $this->assertEquals(['1'], $data->getValue('a2'));
        $this->assertEquals(Action::ADD, $data->getAction('a2'));
    }

    public function testWith(): void
    {
        $data = Data::create()
            ->with('a1', '1', Action::ADD)
            ->with('a1', '2')
            ->with('a2', '2');

        $this->assertEquals(Action::ADD, $data->getAction('a1'));
        $this->assertEquals(Action::UPDATE, $data->getAction('a2'));
    }

    public function testBc(): void
    {
        $massActionData = MassActionData::fromRaw(
            (object) [
                'a1' => '1',
                'a2' => '2',
                'a3' => '3',
            ],
        );

        $data = Data::fromMassActionData($massActionData);

        $this->assertEquals('1', $data->getValue('a1'));
        $this->assertEquals(Action::UPDATE, $data->getAction('a1'));
    }
}
