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

namespace Espo\Core\Formula\Parser\Statement;

class WhileRef
{
    public const STATE_EMPTY = 0;
    public const STATE_CONDITION_STARTED = 1;
    public const STATE_CONDITION_ENDED = 2;
    public const STATE_BODY_STARTED = 3;
    public const STATE_READY = 4;

    private ?int $conditionStart = null;
    private ?int $conditionEnd = null;
    private ?int $bodyStart = null;
    private ?int $bodyEnd = null;
    private int $state = self::STATE_EMPTY;

    public function __construct(private int $start)
    {}

    public function setConditionStart(int $conditionStart): void
    {
        $this->conditionStart = $conditionStart;
        $this->state = self::STATE_CONDITION_STARTED;
    }

    public function setConditionEnd(int $conditionEnd): void
    {
        $this->conditionEnd = $conditionEnd;
        $this->state = self::STATE_CONDITION_ENDED;
    }

    public function setBodyStart(int $bodyStart): void
    {
        $this->bodyStart = $bodyStart;
        $this->state = self::STATE_BODY_STARTED;
    }

    public function setBodyEnd(int $bodyEnd): void
    {
        $this->bodyEnd = $bodyEnd;
        $this->state = self::STATE_READY;
    }

    public function isReady(): bool
    {
        return $this->state === self::STATE_READY;
    }

    public function getState(): int
    {
        return $this->state;
    }

    public function getConditionStart(): ?int
    {
        return $this->conditionStart;
    }

    public function getConditionEnd(): ?int
    {
        return $this->conditionEnd;
    }

    public function getBodyStart(): ?int
    {
        return $this->bodyStart;
    }

    public function getBodyEnd(): ?int
    {
        return $this->bodyEnd;
    }

    public function getEnd(): ?int
    {
        return $this->bodyEnd;
    }

    public function getStart(): int
    {
        return $this->start;
    }
}
