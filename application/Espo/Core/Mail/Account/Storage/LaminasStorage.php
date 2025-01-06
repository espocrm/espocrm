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

namespace Espo\Core\Mail\Account\Storage;

use Espo\Core\Mail\Account\Storage;
use Espo\Core\Mail\Mail\Storage\Imap;
use Espo\Core\Field\DateTime;

use RecursiveIteratorIterator;

class LaminasStorage implements Storage
{
    public function __construct(private Imap $imap)
    {}

    /**
     * @param string[] $flags
     */
    public function setFlags(int $id, array $flags): void
    {
        $this->imap->setFlags($id, $flags);
    }

    public function getSize(int $id): int
    {
        /** @var int */
        return $this->imap->getSize($id);
    }

    public function getRawContent(int $id): string
    {
        return $this->imap->getRawContent($id);
    }

    public function getUniqueId(int $id): string
    {
        /** @var string */
        return $this->imap->getUniqueId($id);
    }

    /**
     * @return int[]
     */
    public function getIdsFromUniqueId(string $uniqueId): array
    {
        return $this->imap->getIdsFromUniqueId($uniqueId);
    }

    /**
     * @return int[]
     */
    public function getIdsSinceDate(DateTime $since): array
    {
        return $this->imap->getIdsSinceDate(
            $since->toDateTime()->format('d-M-Y')
        );
    }

    /**
     * @return array{header: string, flags: string[]}
     */
    public function getHeaderAndFlags(int $id): array
    {
        return $this->imap->getHeaderAndFlags($id);
    }

    public function close(): void
    {
        $this->imap->close();
    }

    /**
     * @return string[]
     */
    public function getFolderNames(): array
    {
        $folderIterator = new RecursiveIteratorIterator(
            $this->imap->getFolders(),
            RecursiveIteratorIterator::SELF_FIRST
        );

        $list = [];

        foreach ($folderIterator as $folder) {
            $list[] = mb_convert_encoding($folder->getGlobalName(), 'UTF-8', 'UTF7-IMAP');
        }

        /** @var string[] */
        return $list;
    }

    public function selectFolder(string $name): void
    {
        $nameConverted = mb_convert_encoding($name, 'UTF7-IMAP', 'UTF-8');

        $this->imap->selectFolder($nameConverted);
    }

    public function appendMessage(string $content, ?string $folder = null): void
    {
        if ($folder !== null) {
            $folder = mb_convert_encoding($folder, 'UTF7-IMAP', 'UTF-8');
        }

        $this->imap->appendMessage($content, $folder);
    }
}
