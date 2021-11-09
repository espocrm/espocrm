<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2021 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

use Laminas\Mail\Storage\Message;

use Espo\Core\Mail\Mail\Storage\Imap;

class MessageWrapper
{
    private $parser;

    private $storage;

    private $id;

    private $rawHeader = null;

    private $rawContent = null;

    private $message = null;

    protected $fullRawContent = null;

    protected $flagList = null;

    public function __construct(?Imap $storage = null, ?string $id = null, ?Parser $parser = null)
    {
        if ($storage) {
            $data = $storage->getHeaderAndFlags($id);

            $this->rawHeader = $data['header'];
            $this->flagList = $data['flags'];
        }

        $this->id = $id;
        $this->storage = $storage;
        $this->parser = $parser;
    }

    public function setFullRawContent(string $content): void
    {
        $this->fullRawContent = $content;
    }

    public function getRawHeader(): string
    {
        return $this->rawHeader ?? '';
    }

    public function getParser(): ?Parser
    {
        return $this->parser;
    }

    public function hasAttribute(string $attribute): bool
    {
        return $this->getParser()->hasMessageAttribute($this, $attribute);
    }

    public function getAttribute(string $attribute): ?string
    {
        return $this->getParser()->getMessageAttribute($this, $attribute);
    }

    public function getRawContent(): string
    {
        if (is_null($this->rawContent)) {
            $this->rawContent = $this->storage->getRawContent((int) $this->id);
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

    public function getMessage(): Message
    {
        if (!$this->message) {
            $data = [];

            if ($this->storage) {
                $data['handler'] = $this->storage;
            }

            if ($this->flagList) {
                $data['flags'] = $this->flagList;
            }

            if ($this->fullRawContent) {
                $data['raw'] = $this->fullRawContent;
            }
            else if ($this->rawHeader) {
                $data['headers'] = $this->rawHeader;
            }

            if ($this->id) {
                $data['id'] = $this->id;
            }

            $this->message = new Message($data);
        }

        return $this->message;
    }

    public function getFlags(): array
    {
        return $this->flagList ?? [];
    }

    public function isFetched(): bool
    {
        return (bool) $this->rawHeader;
    }
}
