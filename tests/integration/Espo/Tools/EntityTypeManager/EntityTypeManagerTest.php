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

namespace tests\integration\Espo\Tools\EntityTypeManager;

use Espo\Core\Templates\Entities\BasePlus;
use Espo\Tools\EntityManager\EntityManager;
use integration\Core\NoTransaction;
use tests\integration\Core\BaseTestCase;

#[NoTransaction]
class EntityTypeManagerTest extends BaseTestCase
{
    private const string PARAM_CATEGORIES = 'categories';

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testCreateUpdateDelete(): void
    {
        $name = 'TestEntityType';

        $tool = $this->getInjectableFactory()->create(EntityManager::class);

        $tool->create($name, BasePlus::TEMPLATE_TYPE, [
            'stream' => true,
        ]);

        //

        $tool->update('C' . $name, [
            'stream' => false,
            self::PARAM_CATEGORIES => true,
        ]);

        $this->reCreateApplication();

        $em = $this->getEntityManager();

        $this->assertTrue($em->hasRepository("C$name"));
        $this->assertTrue($em->hasRepository("C{$name}Category"));

        //

        $tool->delete('C' . $name);

        $this->reCreateApplication();

        $em = $this->getEntityManager();

        $this->assertFalse($em->hasRepository("C{$name}Category"));
        $this->assertFalse($em->hasRepository("C$name"));
    }
}
