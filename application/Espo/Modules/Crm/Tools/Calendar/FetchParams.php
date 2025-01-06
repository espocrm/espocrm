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

namespace Espo\Modules\Crm\Tools\Calendar;

use Espo\Core\Field\DateTime;

class FetchParams
{
    private DateTime $from;
    private DateTime $to;
    private bool $isAgenda = false;
    private bool $skipAcl = false;
    /** @var ?string[] */
    private ?array $scopeList = null;
    private bool $workingTimeRanges = false;
    private bool $workingTimeRangesInverted = false;

    public function __construct(DateTime $from, DateTime $to)
    {
        $this->from = $from;
        $this->to = $to;
    }

    public static function create(DateTime $from, DateTime $to): self
    {
        return new self($from, $to);
    }

    public function withFrom(DateTime $from): self
    {
        $obj = clone $this;
        $obj->from = $from;

        return $obj;
    }

    public function withTo(DateTime $to): self
    {
        $obj = clone $this;
        $obj->to = $to;

        return $obj;
    }

    public function withIsAgenda(bool $isAgenda = true): self
    {
        $obj = clone $this;
        $obj->isAgenda = $isAgenda;

        return $obj;
    }

    public function withSkipAcl(bool $skipAcl = true): self
    {
        $obj = clone $this;
        $obj->skipAcl = $skipAcl;

        return $obj;
    }

    /**
     * @param ?string[] $scopeList
     */
    public function withScopeList(?array $scopeList): self
    {
        $obj = clone $this;
        $obj->scopeList = $scopeList;

        return $obj;
    }

    public function withWorkingTimeRanges(bool $workingTimeRanges = true): self
    {
        $obj = clone $this;
        $obj->workingTimeRanges = $workingTimeRanges;

        return $obj;
    }

    public function withWorkingTimeRangesInverted(bool $workingTimeRangesInverted = true): self
    {
        $obj = clone $this;
        $obj->workingTimeRangesInverted = $workingTimeRangesInverted;

        return $obj;
    }

    public function getFrom(): DateTime
    {
        return $this->from;
    }

    public function getTo(): DateTime
    {
        return $this->to;
    }

    public function isAgenda(): bool
    {
        return $this->isAgenda;
    }

    public function skipAcl(): bool
    {
        return $this->skipAcl;
    }

    /**
     * @return ?string[]
     */
    public function getScopeList(): ?array
    {
        return $this->scopeList;
    }

    public function workingTimeRanges(): bool
    {
        return $this->workingTimeRanges;
    }

    public function workingTimeRangesInverted(): bool
    {
        return $this->workingTimeRangesInverted;
    }
}
