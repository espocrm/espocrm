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

namespace tests\unit\Espo\ORM;

use Espo\ORM\{
    Metadata,
    MetadataDataProvider,
};

class MetadataTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp() : void
    {

    }

    public function testHas1()
    {
        $metadata = $this->createMetadata([
            'Test' => [],
        ]);

        $this->assertTrue($metadata->has('Test'));
    }

    public function testHas2()
    {
        $metadata = $this->createMetadata([
            'Test' => [],
        ]);

        $this->assertFalse($metadata->has('Hello'));
    }

    public function testGet1()
    {
        $metadata = $this->createMetadata([
            'Test' => [
                'indexes' => [],
            ],
        ]);

        $this->assertEquals([], $metadata->get('Test', 'indexes'));
    }

    public function testGet2()
    {
        $metadata = $this->createMetadata([
            'Test' => [
                'relations' => [
                    'test' => [
                        'type' => 'hasMany',
                    ],
                ],
            ],
        ]);

        $this->assertEquals('hasMany', $metadata->get('Test', 'relations.test.type'));
    }

    public function testGet3()
    {
        $metadata = $this->createMetadata([
            'Test' => [
                'relations' => [
                    'test' => [
                        'type' => 'hasMany',
                    ],
                ],
            ],
        ]);

        $this->assertEquals('hasMany', $metadata->get('Test', ['relations', 'test', 'type']));
    }

    protected function createMetadata(array $data) : Metadata
    {
        $metadataDataProvider = $this->createMock(MetadataDataProvider::class);

        $metadataDataProvider
            ->expects($this->any())
            ->method('get')
            ->willReturn($data);

        return new Metadata($metadataDataProvider);
    }
}
