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

namespace Espo\Tools\Formula;

use Espo\Core\Formula\Exceptions\SyntaxError;
use Espo\Core\Formula\Exceptions\Error;

use stdClass;

class RunResult
{
    private bool $isSuccess = false;
    private ?string $output = null;
    private ?string $message = null;
    private ?Error $exception = null;

    private function __construct(bool $isSuccess)
    {
        $this->isSuccess = $isSuccess;
    }

    public static function createSuccess(?string $output): self
    {
        $obj = new self(true);
        $obj->output = $output;

        return $obj;
    }

    public static function createError(Error $exception, ?string $output): self
    {
        $obj = new self(false);

        $obj->message = $exception->getMessage();
        $obj->exception = $exception;
        $obj->output = $output;

        return $obj;
    }

    public static function createSyntaxError(SyntaxError $exception): self
    {
        $obj = new self(false);

        $obj->message = $exception->getShortMessage();
        $obj->exception = $exception;

        return $obj;
    }

    public function isSuccess(): bool
    {
        return $this->isSuccess;
    }

    public function getOutput(): ?string
    {
        return $this->output;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function getException(): ?Error
    {
        return $this->exception;
    }

    public function toStdClass(): stdClass
    {
        $data = (object) [];

        $data->isSuccess = $this->isSuccess();

        if (!$this->isSuccess) {
            $data->message = $this->message;
            $data->isSyntaxError = $this->exception instanceof SyntaxError;
        }

        $data->output = $this->output;

        return $data;
    }
}
