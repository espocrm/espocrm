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

namespace Espo\Core\Mail\Event;

use Espo\Core\Utils\DateTime as DateTimeUtil;

use DateTime;
use DateTimeZone;
use RuntimeException;

class Event
{
    private ?string $attendees = null;
    private ?string $organizer = null;
    private ?string $dateStart = null;
    private ?string $dateEnd = null;
    private ?string $location = null;
    private ?string $name = null;
    private ?string $description = null;
    private ?string $timezone = null;
    private ?string $uid = null;

    private bool $isAllDay = false;

    public function withAttendees(?string $attendees): self
    {
        $obj = clone $this;
        $obj->attendees = $attendees;

        return $obj;
    }

    public function withOrganizer(?string $organizer): self
    {
        $obj = clone $this;
        $obj->organizer = $organizer;

        return $obj;
    }

    public function withDateStart(?string $dateStart): self
    {
        $obj = clone $this;
        $obj->dateStart = $dateStart;

        return $obj;
    }

    public function withDateEnd(?string $dateEnd): self
    {
        $obj = clone $this;
        $obj->dateEnd = $dateEnd;

        return $obj;
    }

    public function withLocation(?string $location): self
    {
        $obj = clone $this;
        $obj->location = $location;

        return $obj;
    }

    public function withName(?string $name): self
    {
        $obj = clone $this;
        $obj->name = $name;

        return $obj;
    }

    public function withDescription(?string $description): self
    {
        $obj = clone $this;
        $obj->description = $description;

        return $obj;
    }

    public function withTimezone(?string $timezone): self
    {
        $obj = clone $this;
        $obj->timezone = $timezone;

        return $obj;
    }

    public function withUid(?string $uid): self
    {
        $obj = clone $this;
        $obj->uid = $uid;

        return $obj;
    }

    public function withIsAllDay(bool $isAllDay): self
    {
        $obj = clone $this;
        $obj->isAllDay = $isAllDay;

        return $obj;
    }

    public function getUid(): ?string
    {
        return $this->uid;
    }

    public function isAllDay(): bool
    {
        return $this->isAllDay;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getDateStart(): ?string
    {
        return $this->convertDate($this->dateStart);
    }

    public function getDateEnd(): ?string
    {
        return $this->convertDate($this->dateEnd, true);
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public static function create(): self
    {
        return new self();
    }

    private function convertDate(?string $value, bool $isEnd = false): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($this->isAllDay) {
            $dt = DateTime::createFromFormat('Ymd', $value);

            if ($dt === false) {
                throw new RuntimeException("Could not parse '{$value}'.");
            }

            if ($isEnd) {
                $dt->modify('-1 day');
            }

            return $dt->format(DateTimeUtil::SYSTEM_DATE_FORMAT);
        }

        $timezone = $this->timezone ?? 'UTC';

        $dt = DateTime::createFromFormat('Ymd\THis', $value, new DateTimeZone($timezone));

        if ($dt === false) {
            throw new RuntimeException("Could not parse '{$value}'.");
        }

        $dt->setTimezone(new DateTimeZone('UTC'));

        return $dt->format(DateTimeUtil::SYSTEM_DATE_TIME_FORMAT);
    }

    public function getOrganizerEmailAddress(): ?string
    {
        return $this->getEmailAddressFromAttendee($this->organizer);
    }

    /**
     * @return string[]
     */
    public function getAttendeeEmailAddressList(): array
    {
        if ($this->attendees === null || $this->attendees === '') {
            return [];
        }

        $list = [];

        foreach (explode(',', $this->attendees) as $item) {
            $emailAddress = $this->getEmailAddressFromAttendee($item);

            if ($emailAddress === null) {
                continue;
            }

            $list[] = $emailAddress;
        }

        return $list;
    }

    private function getEmailAddressFromAttendee(?string $item): ?string
    {
        if ($item === null || $item === '') {
            return null;
        }

        if (explode(':', $item)[0] !== 'MAILTO') {
            return null;
        }

        return explode(':', $item)[1] ?? null;
    }
}
