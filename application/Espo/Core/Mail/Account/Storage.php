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

namespace Espo\Core\Mail\Account;

use Espo\Core\Field\DateTime;
use Espo\Core\Mail\Exceptions\ImapError;

interface Storage
{
    /**
     * Mark as unseen.
     *
     * @throws ImapError
     */
    public function unmarkSeen(int $id): void;

    /**
     * Get a message size.
     *
     * @throws ImapError
     */
    public function getSize(int $id): int;

    /**
     * Get message raw content.
     *
     * @throws ImapError
     */
    public function getRawContent(int $id): string;

    /**
     * Get IDs from unique ID.
     *
     * @return int[]
     *
     * @throws ImapError
     */
    public function getUidsFromUid(int $id): array;

    /**
     * Get IDs since a specific date.
     *
     * @return int[]
     *
     * @throws ImapError
     */
    public function getUidsSinceDate(DateTime $since): array;

    /**
     * Get only header and flags. Won't fetch the whole email.
     *
     * @return array{header: string, flags: string[]}
     *
     * @throws ImapError
     */
    public function getHeaderAndFlags(int $id): array;

    /**
     * Close the resource.
     */
    public function close(): void;

    /**
     * @return string[]
     *
     * @throws ImapError
     */
    public function getFolderNames(): array;

    /**
     * Select a folder.
     *
     * @throws ImapError
     */
    public function selectFolder(string $name): void;

    /**
     * Store a message.
     *
     * @throws ImapError
     */
    public function appendMessage(string $content, string $folder): void;
}
