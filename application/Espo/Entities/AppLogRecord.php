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

namespace Espo\Entities;

use Espo\Core\ORM\Entity;
use Espo\ORM\Defs\Params\AttributeParam;

class AppLogRecord extends Entity
{
    public const ENTITY_TYPE = 'AppLogRecord';

    public function setMessage(string $message): self
    {
        $this->set('message', $message);

        return $this;
    }

    public function setLevel(string $level): self
    {
        $this->set('level', $level);

        return $this;
    }

    public function setCode(?int $code): self
    {
        $this->set('code', $code);

        return $this;
    }

    public function setExceptionClass(?string $exceptionClass): self
    {
        $len = $this->getAttributeParam('exceptionClass', AttributeParam::LEN);

        if ($exceptionClass && strlen($exceptionClass) > $len) {
            $exceptionClass = substr($exceptionClass, $len);
        }

        $this->set('exceptionClass', $exceptionClass);

        return $this;
    }

    public function setFile(?string $file): self
    {
        $len = $this->getAttributeParam('file', AttributeParam::LEN);

        if ($file && strlen($file) > $len) {
            $file = substr($file, $len);
        }

        $this->set('file', $file);

        return $this;
    }

    public function setLine(?int $code): self
    {
        $this->set('line', $code);

        return $this;
    }

    public function setRequestMethod(?string $requestMethod): self
    {
        $this->set('requestMethod', $requestMethod);

        return $this;
    }

    public function setRequestResourcePath(?string $requestResourcePath): self
    {
        $len = $this->getAttributeParam('requestResourcePath', AttributeParam::LEN);

        if ($requestResourcePath && strlen($requestResourcePath) > $len) {
            $requestResourcePath = substr($requestResourcePath, $len);
        }

        $this->set('requestResourcePath', $requestResourcePath);

        return $this;
    }
}
