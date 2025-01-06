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

namespace tests\unit\Espo\Core\Utils\Resource;

use Espo\Core\Utils\Resource\Reader;
use Espo\Core\Utils\Resource\Reader\Params as ReaderParams;
use Espo\Core\Utils\File\Unifier;
use Espo\Core\Utils\File\UnifierObj;

class ReaderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Reader
     */
    private $reader;

    /**
     * @var Unifier
     */
    private $unifier;

    /**
     * @var UnifierObj
     */
    private $unifierObj;

    protected function setUp(): void
    {
        $this->unifier = $this->createMock(Unifier::class);
        $this->unifierObj = $this->createMock(UnifierObj::class);

        $this->reader = new Reader($this->unifier, $this->unifierObj);
    }

    public function testRead1(): void
    {
        $params = ReaderParams::create();

        $this->unifierObj
            ->expects($this->once())
            ->method('unify')
            ->with('test/hello', false)
            ->willReturn((object) []);

        $this->reader->read('test/hello', $params);
    }

    public function testRead2(): void
    {
        $params = ReaderParams::create();

        $this->unifier
            ->expects($this->once())
            ->method('unify')
            ->with('test/hello', false)
            ->willReturn([]);

        $this->reader->readAsArray('test/hello', $params);
    }

    public function testRead3(): void
    {
        $params = ReaderParams::create()
            ->withNoCustom();

        $this->unifier
            ->expects($this->once())
            ->method('unify')
            ->with('test/hello', true)
            ->willReturn([]);

        $this->reader->readAsArray('test/hello', $params);
    }
}
