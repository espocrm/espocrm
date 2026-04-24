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

namespace tests\unit\Espo\Tools\DashboardTemplate;

use Espo\Classes\FieldDuplicators\DashboardTemplate\Layout as LayoutFieldDuplicator;
use Espo\Entities\DashboardTemplate;
use PHPUnit\Framework\TestCase;

class DuplicateTest extends TestCase
{
    public function testDuplicate(): void
    {
        $entity = $this->createMock(DashboardTemplate::class);

        $original = [
            (object) [
                'id' => 'tab-01',
                'layout' => [
                    (object) [
                        'id' => 'd-01',
                    ]
                ],
            ],
        ];

        $originalOptions = (object) [
            'd-01' => (object) ['k' => 'v'],
        ];

        $entity->method('getLayoutRaw')
            ->willReturn($original);

        $entity->method('getDashletsOptionsRaw')
            ->willReturn($originalOptions);

        $duplicator = new LayoutFieldDuplicator();

        $values = $duplicator->duplicate($entity, DashboardTemplate::FIELD_LAYOUT);

        $copy = $values->{DashboardTemplate::FIELD_LAYOUT} ?? null;
        $copyOptions = $values->{DashboardTemplate::FIELD_DASHLETS_OPTIONS} ?? null;

        $this->assertIsArray($copy);
        $this->assertIsString($copy[0]->id);
        $this->assertNotEquals($original[0]->id, $copy[0]->id);
        $this->assertNotEquals($original[0]->layout[0]->id, $copy[0]->layout[0]->id);
        $this->assertIsString($copy[0]->layout[0]->id);
        $this->assertFalse(property_exists($copyOptions, 'd-01'));
        $this->assertCount(1, get_object_vars($copyOptions));
    }
}
