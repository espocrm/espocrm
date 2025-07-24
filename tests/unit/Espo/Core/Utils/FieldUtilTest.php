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

namespace tests\unit\Espo\Core\Utils;

use Espo\Core\Utils\FieldUtil;
use Espo\Core\Utils\Metadata;
use Espo\Core\Utils\Util;
use PHPUnit\Framework\TestCase;

class FieldUtilTest extends TestCase
{
    private ?Metadata $metadata = null;

    protected function setUp(): void
    {
        $this->metadata = $this->createMock(Metadata::class);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function initMetadata(array $data): void
    {
        $this->metadata
            ->expects($this->any())
            ->method('get')
            ->willReturnCallback(function ($key, $default) use ($data) {
                return Util::getValueByKey($data, $key, $default);
            });
    }

    public function testGetActualAttributeListSuffix(): void
    {
        $this->initMetadata([
            'fields' => [
                'testType' => [
                    'naming' => 'suffix',
                    'actualFields' => [
                        '',
                        'helloOne',
                    ],
                ],
            ],
            'entityDefs' => [
                'Test' => [
                    'fields' => [
                        'test' => [
                            'type' => 'testType',
                            'additionalAttributeList' => ['helloTwo'],
                            'fullNameAdditionalAttributeList' => ['helloThree'],
                        ]
                    ]
                ]
            ]
        ]);

        $fieldUtil = new FieldUtil($this->metadata);

        $actual = $fieldUtil->getActualAttributeList('Test', 'test');

        $this->assertEquals([
            'test',
            'testHelloOne',
            'testHelloTwo',
            'helloThree',
        ], $actual);
    }

    public function testGetActualAttributeListPrefix(): void
    {
        $this->initMetadata([
            'fields' => [
                'testType' => [
                    'naming' => 'prefix',
                    'actualFields' => [
                        '',
                        'helloOne',
                    ],
                ],
            ],
            'entityDefs' => [
                'Test' => [
                    'fields' => [
                        'test' => [
                            'type' => 'testType',
                            'additionalAttributeList' => ['helloTwo'],
                            'fullNameAdditionalAttributeList' => ['helloThree'],
                        ]
                    ]
                ]
            ]
        ]);

        $fieldUtil = new FieldUtil($this->metadata);

        $actual = $fieldUtil->getActualAttributeList('Test', 'test');

        $this->assertEquals([
            'test',
            'helloOneTest',
            'helloTwoTest',
            'helloThree',
        ], $actual);
    }
}
