<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2021 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

namespace Espo\Core\FileStorage\Storages;

use Espo\Entities\Attachment;

/**
 * File storing and fetching. Files are represented as Attachment entities.
 */
interface Storage
{
    /**
     * Get file contents.
     */
    public function getContents(Attachment $attachment) : ?string;

    /**
     * Whether a file exists in a storage.
     */
    public function isFile(Attachment $attachment) : bool;

    /**
     * Store file contents.
     */
    public function putContents(Attachment $attachment, string $contents);

    /**
     * Get a file path.
     */
    public function getLocalFilePath(Attachment $attachment) : string;

    /**
     * Remove a file.
     */
    public function unlink(Attachment $attachment);

    /**
     * Whether a file can be downloaded by URL.
     */
    public function hasDownloadUrl(Attachment $attachment) : bool;

    /**
     * Get download URL.
     */
    public function getDownloadUrl(Attachment $attachment) : string;
}
