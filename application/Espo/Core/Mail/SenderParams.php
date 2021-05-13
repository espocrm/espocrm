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

class SenderParams
{
    private $fromAddress = null;

    private $fromName = null;

    private $replyToAddress = null;

    private $replyToName = null;

    private $paramList = [
        'fromAddress',
        'fromName',
        'replyToAddress',
        'replyToName',
    ];

    public static function create(): self
    {
        return new self();
    }

    public function toArray(): array
    {
        $params = [];

        foreach ($this->paramList as $name) {
            if ($this->$name !== null) {
                $params[$name] = $this->$name;
            }
        }

        return $params;
    }

    public static function fromArray(array $params): self
    {
        $obj = new self();

        foreach ($obj->paramList as $name) {
            if (array_key_exists($name, $params)) {
               $obj->$name = $params[$name];
            }
        }

        return $obj;
    }

    public function getFromAddress(): ?string
    {
        return $this->fromAddress;
    }

    public function getFromName(): ?string
    {
        return $this->fromName;
    }

    public function getReplyToAddress(): ?string
    {
        return $this->replyToAddress;
    }

    public function getReplyToName(): ?string
    {
        return $this->replyToName;
    }

    public function withFromAddress(?string $fromAddress): self
    {
        $obj = clone $this;

        $obj->fromAddress = $fromAddress;

        return $obj;
    }

    public function withFromName(?string $fromName): self
    {
        $obj = clone $this;

        $obj->fromName = $fromName;

        return $obj;
    }

    public function withReplyToAddress(?string $replyToAddress): self
    {
        $obj = clone $this;

        $obj->replyToAddress = $replyToAddress;

        return $obj;
    }

    public function withReplyToName(?string $replyToName): self
    {
        $obj = clone $this;

        $obj->replyToName = $replyToName;

        return $obj;
    }
}
