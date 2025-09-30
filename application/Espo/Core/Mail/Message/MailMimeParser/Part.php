<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

namespace Espo\Core\Mail\Message\MailMimeParser;

use Espo\Core\Mail\Message\Part as PartInterface;

use ZBateson\MailMimeParser\Message\IMessagePart;

class Part implements PartInterface
{
    private IMessagePart $part;

    public function __construct(IMessagePart $part)
    {
        $this->part = $part;
    }

    public function getContentType(): ?string
    {
        return $this->part->getContentType();
    }

    public function hasContent(): bool
    {
        return $this->part->hasContent();
    }

    public function getContent(): ?string
    {
        return $this->part->getContent();
    }

    public function getContentId(): ?string
    {
        return $this->part->getContentId();
    }

    public function getCharset(): ?string
    {
        return $this->part->getCharset();
    }

    public function getContentDisposition(): ?string
    {
        return $this->part->getContentDisposition();
    }

    public function getFilename(): ?string
    {
        return $this->part->getFilename();
    }
}
