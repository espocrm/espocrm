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

namespace Espo\Core\Mail\Account;

use Espo\Core\Field\Date;
use Espo\Core\Field\Link;
use Espo\Core\Field\LinkMultiple;

use Espo\Entities\Email;

interface Account
{
    /**
     * Update fetch-data.
     */
    public function updateFetchData(FetchData $fetchData): void;

    /**
     * Relate an email with the account.
     */
    public function relateEmail(Email $email): void;

    /**
     * Max number of emails to be fetched per iteration.
     */
    public function getPortionLimit(): int;

    /**
     * Is available for fetching.
     */
    public function isAvailableForFetching(): bool;

    /**
     * An email address of the account.
     */
    public function getEmailAddress(): ?string;

    /**
     * A user fetched emails will be assigned to (through the `assignedUsers` field).
     */
    public function getAssignedUser(): ?Link;

    /**
     * A user the account belongs to.
     */
    public function getUser(): ?Link;

    /**
     * Users email should be related to (put into inbox).
     */
    public function getUsers(): LinkMultiple;

    /**
     * Teams email should be related to.
     */
    public function getTeams(): LinkMultiple;

    /**
     * Fetched emails won't be marked as read upon fetching.
     */
    public function keepFetchedEmailsUnread(): bool;

    /**
     * Get fetch-data.
     */
    public function getFetchData(): FetchData;

    /**
     * Fetch email since a specific date.
     */
    public function getFetchSince(): ?Date;

    /**
     * A folder fetched emails should be put into.
     */
    public function getEmailFolder(): ?Link;

    /**
     * Folders to fetch from.
     *
     * @return string[]
     */
    public function getMonitoredFolderList(): array;

    public function getId(): ?string;

    public function getEntityType(): string;

    public function getHost(): ?string;

    public function getPort(): ?int;

    public function getUsername(): ?string;

    public function getPassword(): ?string;

    public function getSecurity(): ?string;

    /**
     * @return ?class-string<object>
     */
    public function getImapHandlerClassName(): ?string;

    public function getSentFolder(): ?string;
}
