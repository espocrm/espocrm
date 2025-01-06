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

namespace Espo\Modules\Crm\Business\Event;

use RuntimeException;

class Ics
{
    public const STATUS_CONFIRMED = 'CONFIRMED';
    public const STATUS_TENTATIVE = 'TENTATIVE';
    public const STATUS_CANCELLED = 'CANCELLED';

    public const METHOD_REQUEST = 'REQUEST';
    public const METHOD_CANCEL = 'CANCEL';

    /** @var self::METHOD_* string  */
    private string $method;
    private ?string $output = null;
    private string $prodid;
    private ?int $startDate = null;
    private ?int $endDate = null;
    private ?string $summary = null;
    private ?string $address = null;
    private ?string $description = null;
    private ?string $uid = null;
    /** @var self::STATUS_* string */
    private string $status;
    private ?int $stamp = null;
    /** @var array{string, ?string}|null  */
    private ?array $organizer = null;
    /** @var array{string, ?string}[]  */
    private array $attendees = [];

    /**
     * @param array{
     *     organizer?: array{string, ?string}|null,
     *     attendees?: array{string, ?string}[],
     *     startDate?: ?int,
     *     endDate?: ?int,
     *     summary?: ?string,
     *     address?: ?string,
     *     description?: ?string,
     *     uid?: ?string,
     *     status?: self::STATUS_CONFIRMED|self::STATUS_TENTATIVE|self::STATUS_CANCELLED,
     *     method?: self::METHOD_REQUEST|self::METHOD_CANCEL,
     *     stamp?: ?int,
     * } $attributes
     */
    public function __construct(string $prodid, array $attributes = [])
    {
        if ($prodid === '') {
            throw new RuntimeException('PRODID is required');
        }

        $this->status = self::STATUS_CONFIRMED;
        $this->method = self::METHOD_REQUEST;
        $this->prodid = $prodid;

        foreach ($attributes as $key => $value) {
            if (!property_exists($this, $key)) {
                throw new RuntimeException("Bad attribute '$key'.");
            }

            $this->$key = $value;
        }
    }

    public function get(): string
    {
        if ($this->output === null) {
            $this->generate();
        }

        /** @var string */
        return $this->output;
    }

    /** @noinspection SpellCheckingInspection */
    private function generate(): void
    {
        $start =
            "BEGIN:VCALENDAR\r\n" .
            "VERSION:2.0\r\n" .
            "PRODID:-$this->prodid\r\n" .
            "METHOD:$this->method\r\n" .
            "BEGIN:VEVENT\r\n";

        $organizerPart = '';

        if ($this->organizer) {
            $organizerPart = "ORGANIZER;{$this->preparePerson($this->organizer[0], $this->organizer[1])}";
        }

        $body =
            "DTSTART:{$this->formatTimestamp($this->startDate)}\r\n" .
            "DTEND:{$this->formatTimestamp($this->endDate)}\r\n" .
            "SUMMARY:{$this->escapeString($this->summary)}\r\n" .
            "LOCATION:{$this->escapeString($this->address)}\r\n" .
            $organizerPart .
            "DESCRIPTION:{$this->escapeString($this->formatMultiline($this->description))}\r\n" .
            "UID:$this->uid\r\n" .
            "SEQUENCE:0\r\n" .
            "DTSTAMP:{$this->formatTimestamp($this->stamp ?? time())}\r\n" .
            "STATUS:$this->status\r\n";

        foreach ($this->attendees as $attendee) {
            $body .= "ATTENDEE;{$this->preparePerson($attendee[0], $attendee[1])}";
        }

        $end =
            "END:VEVENT\r\n".
            "END:VCALENDAR";

        $this->output = $start . $body . $end;
    }

    private function preparePerson(string $address, ?string $name): string
    {
        return "CN={$this->escapeString($name)}:MAILTO:{$this->escapeString($address)}\r\n";
    }

    private function formatTimestamp(?int $timestamp): string
    {
        if (!$timestamp) {
            $timestamp = time();
        }

        return date('Ymd\THis\Z', $timestamp);
    }

    private function escapeString(?string $string): string
    {
        if (!$string) {
            return '';
        }

        /** @var string */
        return preg_replace('/([,;])/', '\\\$1', $string);
    }

    private function formatMultiline(?string $string): string
    {
        if (!$string) {
            return '';
        }

        return str_replace(["\r\n", "\n"], "\\n", $string);
    }
}
