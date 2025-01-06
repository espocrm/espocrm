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

namespace Espo\Core\Mail\Account;

use Espo\Core\Field\DateTime;

interface Storage
{
    /**
     * Set message flags.
     *
     * @param string[] $flags
     */
    public function setFlags(int $id, array $flags): void;

    /**
     * Get a message size.
     */
    public function getSize(int $id): int;

    /**
     * Get message raw content.
     */
    public function getRawContent(int $id): string;

    /**
     * Get a message unique ID.
     */
    public function getUniqueId(int $id): string;

    /**
     * Get IDs from unique ID.
     *
     * @return int[]
     */
    public function getIdsFromUniqueId(string $uniqueId): array;

    /**
     * Get IDs since a specific date.
     *
     * @return int[]
     */
    public function getIdsSinceDate(DateTime $since): array;

    /**
     * Get only header and flags. Won't fetch the whole email.
     *
     * @return array{header: string, flags: string[]}
     */
    public function getHeaderAndFlags(int $id): array;

    /**
     * Close the resource.
     */
    public function close(): void;

    /**
     * @return string[]
     */
    public function getFolderNames(): array;

    /**
     * Select a folder.
     */
    public function selectFolder(string $name): void;

    /**
     * Store a message.
     */
    public function appendMessage(string $content, ?string $folder = null): void;
}
