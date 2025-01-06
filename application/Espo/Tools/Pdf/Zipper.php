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

namespace Espo\Tools\Pdf;

use Espo\Core\Utils\Util;
use LogicException;
use RuntimeException;
use ZipArchive;

class Zipper
{
    private ?string $filePath = null;
    /** @var array{string, string}[] */
    private array $itemList = [];

    public function __construct() {}

    public function add(Contents $contents, string $name): void
    {
        $tempPath = tempnam(sys_get_temp_dir(), 'espo-pdf-zip-item');

        if ($tempPath === false) {
            throw new RuntimeException("Could not create a temp file.");
        }

        $fp = fopen($tempPath, 'w');

        if ($fp === false) {
            throw new RuntimeException("Could not open a temp file {$tempPath}.");
        }

        fwrite($fp, $contents->getString());
        fclose($fp);

        $this->itemList[] = [$tempPath, Util::sanitizeFileName($name) . '.pdf'];
    }

    public function archive(): void
    {
        $tempPath = tempnam(sys_get_temp_dir(), 'espo-pdf-zip');

        if ($tempPath === false) {
            throw new RuntimeException("Could not create a temp file.");
        }

        $archive = new ZipArchive();
        $archive->open($tempPath, ZipArchive::CREATE);

        foreach ($this->itemList as $item) {
            $archive->addFile($item[0], $item[1]);
        }

        $archive->close();

        $this->filePath = $tempPath;
    }

    public function getFilePath(): string
    {
        if (!$this->filePath) {
            throw new LogicException();
        }

        return $this->filePath;
    }
}
