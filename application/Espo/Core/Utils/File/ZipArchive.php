<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2026 EspoCRM, Inc.
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

namespace Espo\Core\Utils\File;

use Espo\Core\Utils\Util;
use RuntimeException;

class ZipArchive
{
    private Manager $fileManager;

    public function __construct(?Manager $fileManager = null)
    {
        if ($fileManager === null) {
            $fileManager = new Manager();
        }

        $this->fileManager = $fileManager;
    }

    /**
     * Unzip archive.
     *
     * @param string $file A path to a zip file.
     * @param string $destination A destination.
     */
    public function unzip(string $file, string $destination): bool
    {
        if (!class_exists('\ZipArchive')) {
            throw new RuntimeException("php-zip extension is not installed. Cannot unzip the file.");
        }

        $zip = new \ZipArchive;

        $res = $zip->open($file);

        if ($res !== true) {
            return false;
        }

        $this->fileManager->mkdir($destination);


        for ($i = 0; $i < $zip->numFiles; $i ++) {
            $filename = $zip->getNameIndex($i);

            if ($filename === false) {
                continue;
            }

            if (
                str_contains($filename, '..') ||
                str_starts_with($filename, '/') ||
                str_starts_with($filename, '\\')
            ) {
                throw new RuntimeException("No allowed path.");
            }

            $zip->extractTo($destination, $filename);
        }

        $zip->close();

        return true;
    }
}
