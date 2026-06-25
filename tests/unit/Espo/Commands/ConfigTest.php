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

namespace tests\unit\Espo\Commands;

use Closure;
use Espo\Classes\ConsoleCommands\GetConfigParam;
use Espo\Classes\ConsoleCommands\SetConfigParam;
use Espo\Core\Console\Command\Params;
use Espo\Core\Console\IO;
use Espo\Core\Utils\Config;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    public function testGetParamNoParam(): void
    {
        $params = new Params(
            options: [],
            flagList: [],
            argumentList: [],
        );

        $io = $this->createMock(IO::class);
        $config = $this->createMock(Config::class);

        $this->expectIoError($io);

        (new GetConfigParam($config))->run($params, $io);
    }

    public function testGetParamNonExistingParam(): void
    {
        $params = new Params(
            options: [],
            flagList: [],
            argumentList: ['test'],
        );

        $io = $this->createMock(IO::class);
        $config = $this->createMock(Config::class);

        $config
            ->method('has')
            ->with('test')
            ->willReturn(false);

        $this->expectIoError($io);

        (new GetConfigParam($config))->run($params, $io);
    }

    public function testGetParamExisting(): void
    {
        $params = new Params(
            options: [],
            flagList: [],
            argumentList: ['test'],
        );

        $io = $this->createMock(IO::class);
        $config = $this->createMock(Config::class);

        $config
            ->method('has')
            ->with('test')
            ->willReturn(true);

        $config
            ->method('get')
            ->with('test')
            ->willReturn('hello');

        $this->expectIoNoError($io);
        $this->expectWriteLine($io, 'hello');

        (new GetConfigParam($config))->run($params, $io);
    }

    public function testGetParamJson(): void
    {
        $params = new Params(
            options: [],
            flagList: ['json'],
            argumentList: ['test'],
        );

        $io = $this->createMock(IO::class);
        $config = $this->createMock(Config::class);

        $config
            ->method('has')
            ->with('test')
            ->willReturn(true);

        $config
            ->method('get')
            ->with('test')
            ->willReturn('hello');

        $this->expectIoNoError($io);
        $this->expectWriteLine($io, '"hello"');

        (new GetConfigParam($config))->run($params, $io);
    }

    public function testGetParamTrue(): void
    {
        $params = new Params(
            options: [],
            flagList: [],
            argumentList: ['test'],
        );

        $io = $this->createMock(IO::class);
        $config = $this->createMock(Config::class);

        $config
            ->method('has')
            ->with('test')
            ->willReturn(true);

        $config
            ->method('get')
            ->with('test')
            ->willReturn(true);

        $this->expectIoNoError($io);
        $this->expectWriteLine($io, 'true');

        (new GetConfigParam($config))->run($params, $io);
    }

    public function testGetParamFalse(): void
    {
        $params = new Params(
            options: [],
            flagList: [],
            argumentList: ['test'],
        );

        $io = $this->createMock(IO::class);
        $config = $this->createMock(Config::class);

        $config
            ->method('has')
            ->with('test')
            ->willReturn(true);

        $config
            ->method('get')
            ->with('test')
            ->willReturn(false);

        $this->expectIoNoError($io);
        $this->expectWriteLine($io, 'false');

        (new GetConfigParam($config))->run($params, $io);
    }

    public function testGetParamNull(): void
    {
        $params = new Params(
            options: [],
            flagList: [],
            argumentList: ['test'],
        );

        $io = $this->createMock(IO::class);
        $config = $this->createMock(Config::class);

        $config
            ->method('has')
            ->with('test')
            ->willReturn(true);

        $config
            ->method('get')
            ->with('test')
            ->willReturn(null);

        $this->expectIoNoError($io);
        $this->expectWriteLine($io, 'null');

        (new GetConfigParam($config))->run($params, $io);
    }

    private function expectIoError(IO & MockObject $io): void
    {
        $io->expects($this->once())
            ->method('setExitStatus')
            ->with(1);
    }

    private function expectIoNoError(IO & MockObject $io): void
    {
        $io->expects($this->never())
            ->method('setExitStatus');
    }

    private function expectWriteLine(IO & MockObject $io, string $value): void
    {
        $io->expects($this->once())
            ->method('writeLine')
            ->with($value);
    }

    public function testSetConfigNoParam(): void
    {
        $params = new Params(
            options: [],
            flagList: [],
            argumentList: [],
        );

        $io = $this->createMock(IO::class);
        $config = $this->createMock(Config::class);
        $configWriter = $this->createMock(Config\ConfigWriter::class);

        $this->expectIoError($io);

        (new SetConfigParam($config, $configWriter))->run($params, $io);
    }

    public function testSetConfigNoValue(): void
    {
        $params = new Params(
            options: [],
            flagList: [],
            argumentList: ['test'],
        );

        $io = $this->createMock(IO::class);
        $config = $this->createMock(Config::class);
        $configWriter = $this->createMock(Config\ConfigWriter::class);

        $this->expectIoError($io);

        (new SetConfigParam($config, $configWriter))->run($params, $io);
    }

    public function testSetConfigValueString1(): void
    {
        $this->internalTestSet(
            type: null,
            paramName: 'test',
            value: 'hello',
            valueExpected: 'hello',
        );
    }

    public function testSetConfigValueString2(): void
    {
        $this->internalTestSet(
            type: 'string',
            paramName: 'test',
            value: 'hello',
            valueExpected: 'hello',
        );
    }

    public function testSetConfigValueBool1(): void
    {
        $this->internalTestSet(
            type: 'bool',
            paramName: 'test',
            value: '1',
            valueExpected: true,
        );
    }

    public function testSetConfigValueBool2(): void
    {
        $this->internalTestSet(
            type: 'bool',
            paramName: 'test',
            value: '0',
            valueExpected: false,
        );
    }

    public function testSetConfigValueBool3(): void
    {
        $this->internalTestSet(
            type: 'bool',
            paramName: 'test',
            value: 'true',
            valueExpected: true,
        );
    }

    public function testSetConfigValueBool4(): void
    {
        $this->internalTestSet(
            type: 'bool',
            paramName: 'test',
            value: 'false',
            valueExpected: false,
        );
    }

    public function testSetConfigValueBoolBad(): void
    {
        $this->internalTestSet(
            type: 'bool',
            paramName: 'test',
            value: 'yes',
            error: true,
        );
    }

    public function testSetConfigValueJson(): void
    {
        $this->internalTestSet(
            type: 'json',
            paramName: 'test',
            value: '"true"',
            valueExpected: "true",
        );
    }

    public function testSetConfigValueObject1(): void
    {
        $this->internalTestSet(
            type: null,
            paramName: 'database.host',
            value: 'hello',
            valueExpected: ['host' => 'hello', 'port' => 0],
            hook: function (Config & MockObject $config) {
                $config
                    ->expects($this->once())
                    ->method('get')
                    ->with('database')
                    ->willReturn(['host' => 'localhost', 'port' => 0]);
            },
            setParamNameExpected: 'database',
        );
    }

    public function testSetConfigValueAutoTrue1(): void
    {
        $this->internalTestSet(
            type: 'auto',
            paramName: 'test',
            value: 'true',
            valueExpected: true,
        );
    }

    public function testSetConfigValueAutoTrue2(): void
    {
        $this->internalTestSet(
            type: 'auto',
            paramName: 'test',
            value: 'TRUE',
            valueExpected: true,
        );
    }

    public function testSetConfigValueAutoFalse1(): void
    {
        $this->internalTestSet(
            type: 'auto',
            paramName: 'test',
            value: 'false',
            valueExpected: false,
        );
    }

    public function testSetConfigValueAutoNull1(): void
    {
        $this->internalTestSet(
            type: 'auto',
            paramName: 'test',
            value: 'null',
        );
    }

    public function testSetConfigValueAutoNull2(): void
    {
        $this->internalTestSet(
            type: 'auto',
            paramName: 'test',
            value: 'NULL',
        );
    }

    public function testSetConfigValueAutoInt(): void
    {
        $this->internalTestSet(
            type: 'auto',
            paramName: 'test',
            value: '2',
            valueExpected: 2,
        );
    }

    public function testSetConfigValueAutoFloat(): void
    {
        $this->internalTestSet(
            type: 'auto',
            paramName: 'test',
            value: '2.0',
            valueExpected: 2.0,
        );
    }

    public function testSetConfigValueAutoString(): void
    {
        $this->internalTestSet(
            type: 'auto',
            paramName: 'test',
            value: 'value',
            valueExpected: 'value',
        );
    }

    /**
     * @param (Closure(Config & MockObject): void)|null $hook
     */
    private function internalTestSet(
        ?string $type,
        string $paramName,
        string $value,
        mixed $valueExpected = null,
        bool $error = false,
        ?Closure $hook = null,
        ?string $setParamNameExpected = null,
    ): void {

        $options = [];

        if ($type !== null) {
            $options = ['type' => $type];
        }

        $params = new Params(
            options: $options,
            flagList: [],
            argumentList: [$paramName, $value],
        );

        $io = $this->createMock(IO::class);
        $config = $this->createMock(Config::class);
        $configWriter = $this->createMock(Config\ConfigWriter::class);

        if ($hook) {
            $hook($config);
        }

        if (!$error) {
            $this->expectIoNoError($io);

            $configWriter
                ->expects($this->once())
                ->method('save');

            $configWriter
                ->expects($this->once())
                ->method('set')
                ->with($setParamNameExpected ?? $paramName, $valueExpected);
        } else {
            $this->expectIoError($io);
        }

        (new SetConfigParam($config, $configWriter))->run($params, $io);
    }
}
