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

namespace tests\unit\Espo\Core\Job;

use Espo\Core\{
    Job\QueueProcessor\Params,
};

class QueueProcessorParamsTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp() : void
    {
    }

    public function testParams1()
    {
        $params = \Espo\Core\Job\QueueProcessor\Params
            ::create()
            ->withLimit(10);

        $this->assertFalse($params->useProcessPool());
        $this->assertFalse($params->noLock());

        $this->assertEquals(10, $params->getLimit());

        $this->assertNull($params->getQueue());
    }

    public function testParams2()
    {
        $params = \Espo\Core\Job\QueueProcessor\Params
            ::create()
            ->withLimit(10)
            ->withUseProcessPool(true)
            ->withNoLock(true)
            ->withGroup('group-0')
            ->withQueue('q0');

        $this->assertTrue($params->useProcessPool());
        $this->assertTrue($params->noLock());

        $this->assertEquals('q0', $params->getQueue());

        $this->assertEquals('group-0', $params->getGroup());
    }
}
