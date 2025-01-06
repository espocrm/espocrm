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

namespace tests\unit\Espo\Core\Field\LinkMultiple;

use Espo\Core\Field\LinkMultiple;
use Espo\Core\Field\LinkMultipleItem;

use RuntimeException;

class LinkMultipleTest extends \PHPUnit\Framework\TestCase
{
    public function testEmpty()
    {
        $value = LinkMultiple::create();

        $this->assertEquals(0, count($value->getIdList()));
        $this->assertEquals(0, count($value->getList()));
        $this->assertEquals(0, $value->getCount());
    }

    public function testDuplicate()
    {
        $this->expectException(RuntimeException::class);

        LinkMultiple
            ::create([
                LinkMultipleItem::create('1'),
                LinkMultipleItem::create('1'),
            ]);
    }

    public function testWithAdded1()
    {
        $item = LinkMultipleItem::create('1')->withName('test-1');

        $group = LinkMultiple
            ::create([$item])
            ->withAdded(
                LinkMultipleItem::create('2')
            );

        $this->assertEquals(2, $group->getCount());

        $this->assertEquals('1', $group->getList()[0]->getId());
        $this->assertEquals('test-1', $group->getList()[0]->getName());

        $this->assertEquals(null, $group->getList()[1]->getName());
    }

    public function testWithAddedId()
    {
        $item = LinkMultipleItem::create('1')->withName('test-1');

        $group = LinkMultiple
            ::create([$item])
            ->withAddedId('2');

        $this->assertEquals(2, $group->getCount());

        $this->assertEquals('1', $group->getList()[0]->getId());
        $this->assertEquals('test-1', $group->getList()[0]->getName());

        $this->assertEquals('2', $group->getList()[1]->getId());
    }

    public function testWithAddedIdList(): void
    {
        $item = LinkMultipleItem::create('1')->withName('test-1');

        $group = LinkMultiple
            ::create([$item])
            ->withAddedIdList(['2', '3']);

        $this->assertEquals(3, $group->getCount());

        $this->assertEquals('1', $group->getList()[0]->getId());
        $this->assertEquals('test-1', $group->getList()[0]->getName());

        $this->assertEquals('2', $group->getList()[1]->getId());
    }

    public function testWithAddedList()
    {
        $group = LinkMultiple
            ::create()
            ->withAddedList([
                LinkMultipleItem::create('1'),
                LinkMultipleItem::create('2'),
            ]);

        $this->assertEquals(2, $group->getCount());
    }

    public function testWithRemoved1()
    {
        $group = LinkMultiple
            ::create([
                LinkMultipleItem::create('1'),
                LinkMultipleItem::create('2'),
                LinkMultipleItem::create('3'),
            ])
            ->withRemoved(LinkMultipleItem::create('1'));

        $this->assertEquals(2, $group->getCount());
    }

    public function testGetById()
    {
        $group = LinkMultiple
            ::create([
                LinkMultipleItem::create('1'),
                LinkMultipleItem::create('2'),
                LinkMultipleItem::create('3'),
            ]);

        $this->assertEquals('1', $group->getById('1')->getId());

        $this->assertNull($group->getById('4'));
    }

    public function testClone()
    {
        $group = LinkMultiple
            ::create([
                LinkMultipleItem::create('1'),
                LinkMultipleItem::create('2'),
                LinkMultipleItem::create('3'),
            ]);

        $cloned = clone $group;

        $this->assertEquals('1', $cloned->getById('1')->getId());

        $this->assertNotSame($cloned->getList()[1], $group->getList()[1]);
    }

    public function testItemColumn()
    {
        $item = LinkMultipleItem
            ::create('1')
            ->withColumnValue('key', 'value');

        $this->assertTrue($item->hasColumnValue('key'));

        $this->assertFalse($item->hasColumnValue('key-bad'));

        $this->assertEquals('value', $item->getColumnValue('key'));

        $this->assertEquals(['key'], $item->getColumnList());
    }
}
