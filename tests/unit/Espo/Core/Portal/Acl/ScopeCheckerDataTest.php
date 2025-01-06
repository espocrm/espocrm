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

namespace tests\unit\Espo\Core\Portal\Acl;

use Espo\Core\{
    Portal\Acl\AccessChecker\ScopeCheckerData,

};

class ScopeCheckerDataTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
    }

    public function testCheckerData0()
    {
        $checkerData = ScopeCheckerData
            ::createBuilder()
            ->build();

        $this->assertEquals(false, $checkerData->isOwn());
        $this->assertEquals(false, $checkerData->inAccount());
        $this->assertEquals(false, $checkerData->inContact());
    }

    public function testCheckerData1()
    {
        $checkerData = ScopeCheckerData
            ::createBuilder()
            ->setIsOwn(true)
            ->setInAccount(true)
            ->setInContact(true)
            ->build();

        $this->assertEquals(true, $checkerData->isOwn());
        $this->assertEquals(true, $checkerData->inAccount());
        $this->assertEquals(true, $checkerData->inContact());
    }

    public function testCheckerData2()
    {
        $checkerData = ScopeCheckerData
            ::createBuilder()
            ->setIsOwn(false)
            ->setInAccountChecker(
                function (): bool {
                    return true;
                }
            )
            ->build();

        $this->assertEquals(false, $checkerData->isOwn());
        $this->assertEquals(true, $checkerData->inAccount());
    }

    public function testCheckerData3()
    {
        $checkerData = ScopeCheckerData
            ::createBuilder()
            ->setIsOwnChecker(
                function (): bool {
                    return false;
                }
            )
            ->setInAccountChecker(
                function (): bool {
                    return false;
                }
            )
            ->setInContactChecker(
                function (): bool {
                    return true;
                }
            )
            ->build();

        $this->assertEquals(false, $checkerData->isOwn());
        $this->assertEquals(false, $checkerData->inAccount());
        $this->assertEquals(true, $checkerData->inContact());
    }
}
