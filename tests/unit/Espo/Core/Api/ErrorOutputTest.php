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

namespace tests\unit\Espo\Core\Api;

use Espo\Core\Api\ConfigDataProvider;
use Espo\Core\Api\ErrorOutput;
use Espo\Core\Api\Request;
use Espo\Core\Api\Response;
use Espo\Core\Utils\Log;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class ErrorOutputTest extends TestCase
{
    public function testExceptionNotExposed(): void
    {
        $e = new RuntimeException();

        $errorOutput = $this->createErrorOutput(exposeExceptions: false);

        $request = $this->createMock(Request::class);
        $response = $this->createMock(Response::class);

        $response->expects(self::never())
            ->method('setHeader')
            ->with(ErrorOutput::HEADER_STATUS_REASON);

        $errorOutput->process($request, $response, $e);
    }

    public function testExceptionExposed(): void
    {
        $e = new RuntimeException("Test.");

        $errorOutput = $this->createErrorOutput(exposeExceptions: true);

        $request = $this->createMock(Request::class);
        $response = $this->createMock(Response::class);

        $response->expects(self::once())
            ->method('setHeader')
            ->with(ErrorOutput::HEADER_STATUS_REASON);

        $errorOutput->process($request, $response, $e);
    }

    /**
     * @return ConfigDataProvider
     */
    private function createConfig(bool $exposeExceptions): ConfigDataProvider
    {
        $config = $this->createMock(ConfigDataProvider::class);
        $config
            ->expects(self::any())
            ->method('exposeExceptions')
            ->willReturn($exposeExceptions);

        return $config;
    }

    private function createErrorOutput(bool $exposeExceptions): ErrorOutput
    {
        return new ErrorOutput(
            log: $this->createMock(Log::class),
            config: $this->createConfig(exposeExceptions: $exposeExceptions),
        );
    }
}
