<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2022 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
 * Website: https://www.espocrm.com
 *
 * EspoCRM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * EspoCRM is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with EspoCRM. If not, see http://www.gnu.org/licenses/.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

namespace Espo\Core\Mail;

use Espo\Core\Mail\Account\Storage;
use Espo\Core\Mail\Message\Part;

use RuntimeException;

class MessageWrapper implements Message
{
    private int $id;

    private ?Storage $storage;

    private ?Parser $parser;

    private ?string $rawHeader = null;

    private ?string $rawContent = null;

    private ?string $fullRawContent = null;

    /**
     * @var ?string[]
     */
    private ?array $flagList = null;

    public function __construct(
        int $id,
        ?Storage $storage = null,
        ?Parser $parser = null,
        ?string $fullRawContent = null
    ) {
        if ($storage) {
            $data = $storage->getHeaderAndFlags($id);

            $this->rawHeader = $data['header'];
            $this->flagList = $data['flags'];
        }

        $this->id = $id;
        $this->storage = $storage;
        $this->parser = $parser;
        $this->fullRawContent = $fullRawContent;

        if (
            !$storage &&
            $this->fullRawContent
        ) {
            $rawHeader = null;
            $rawBody = null;

            if (strpos($this->fullRawContent, "\r\n\r\n") !== false) {
                [$rawHeader, $rawBody] = explode("\r\n\r\n", $this->fullRawContent, 2);
            }
            else if (strpos($this->fullRawContent, "\n\n") !== false) {
                [$rawHeader, $rawBody] = explode("\n\n", $this->fullRawContent, 2);
            }

            $this->rawHeader = $rawHeader;
            $this->rawContent = $rawBody;
        }
    }

    public function getRawHeader(): string
    {
        return $this->rawHeader ?? '';
    }

    public function getParser(): ?Parser
    {
        return $this->parser;
    }

    public function hasHeader(string $name): bool
    {
        if (!$this->parser) {
            throw new RuntimeException();
        }

        return $this->parser->hasHeader($this, $name);
    }

    public function getHeader(string $attribute): ?string
    {
        if (!$this->parser) {
            throw new RuntimeException();
        }

        return $this->parser->getHeader($this, $attribute);
    }

    public function getRawContent(): string
    {
        if (is_null($this->rawContent)) {
            if (!$this->storage) {
                throw new RuntimeException();
            }

            $this->rawContent = $this->storage->getRawContent($this->id);
        }

        return $this->rawContent ?? '';
    }

    public function getFullRawContent(): string
    {
        if ($this->fullRawContent) {
            return $this->fullRawContent;
        }

        return $this->getRawHeader() . "\n" . $this->getRawContent();
    }

    /**
     * @return string[]
     */
    public function getFlags(): array
    {
        return $this->flagList ?? [];
    }

    public function isFetched(): bool
    {
        return (bool) $this->rawHeader;
    }

    /**
     * @return Part[]
     */
    public function getPartList(): array
    {
        if (!$this->parser) {
            throw new RuntimeException();
        }

        return $this->parser->getPartList($this);
    }
}
