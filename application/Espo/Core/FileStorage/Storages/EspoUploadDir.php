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

namespace Espo\Core\FileStorage\Storages;

use Espo\Core\FileStorage\Attachment;
use Espo\Core\FileStorage\Local;
use Espo\Core\FileStorage\Storage;
use Espo\Core\Utils\File\Manager as FileManager;
use Espo\Core\Utils\File\Exceptions\FileError;

use Psr\Http\Message\StreamInterface;
use GuzzleHttp\Psr7\Stream;

class EspoUploadDir implements Storage, Local
{

    public const NAME = 'EspoUploadDir';

    public function __construct(protected FileManager $fileManager)
    {}

    public function unlink(Attachment $attachment): void
    {
        $this->fileManager->unlink(
            $this->getFilePath($attachment)
        );
    }

    public function exists(Attachment $attachment): bool
    {
        $filePath = $this->getFilePath($attachment);

        return $this->fileManager->isFile($filePath);
    }

    public function getSize(Attachment $attachment): int
    {
        $filePath = $this->getFilePath($attachment);

        if (!$this->exists($attachment)) {
            throw new FileError("Could not get size for non-existing file '$filePath'.");
        }

        return $this->fileManager->getSize($filePath);
    }

    public function getStream(Attachment $attachment): StreamInterface
    {
        $filePath = $this->getFilePath($attachment);

        if (!$this->exists($attachment)) {
            throw new FileError("Could not get stream for non-existing '$filePath'.");
        }

        $resource = fopen($filePath, 'r');

        if ($resource === false) {
            throw new FileError("Could not open '$filePath'.");
        }

        return new Stream($resource);
    }

    public function putStream(Attachment $attachment, StreamInterface $stream): void
    {
        $filePath = $this->getFilePath($attachment);

        $stream->rewind();

        // @todo Use a resource to write a file (add a method to the file manager).
        $contents = $stream->getContents();

        $result = $this->fileManager->putContents($filePath, $contents);

        if (!$result) {
            throw new FileError("Could not store a file '$filePath'.");
        }
    }

    public function getLocalFilePath(Attachment $attachment): string
    {
        return $this->getFilePath($attachment);
    }

    /**
     * @return string
     */
    protected function getFilePath(Attachment $attachment)
    {
        $sourceId = $attachment->getSourceId();

        return 'data/upload/' . $sourceId;
    }
}
