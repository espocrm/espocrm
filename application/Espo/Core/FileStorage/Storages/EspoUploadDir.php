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

use Espo\Core\{
    Exceptions\Error,
    Utils\File\Manager as FileManager,
    FileStorage\Storage,
    FileStorage\Local,
    FileStorage\Attachment,
};

use Psr\Http\Message\StreamInterface;

use GuzzleHttp\Psr7\Stream;

class EspoUploadDir implements Storage, Local
{
    protected $fileManager;

    public function __construct(FileManager $fileManager)
    {
        $this->fileManager = $fileManager;
    }

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
            throw new Error("Could not get size for non-existing file '{$filePath}'.");
        }

        return filesize($filePath);
    }

    public function getStream(Attachment $attachment): StreamInterface
    {
        $filePath = $this->getFilePath($attachment);

        if (!$this->exists($attachment)) {
            throw new Error("Could not get stream for non-existing '{$filePath}'.");
        }

        $resouce = fopen($filePath, 'r');

        return new Stream($resouce);
    }

    public function putStream(Attachment $attachment, StreamInterface $stream): void
    {
        $filePath = $this->getFilePath($attachment);

        $contents = $stream->getContents();

        $result = $this->fileManager->putContents($filePath, $contents);

        if (!$result) {
            throw new Error("Could not store a file '{$filePath}'.");
        }
    }

    public function getLocalFilePath(Attachment $attachment): string
    {
        return $this->getFilePath($attachment);
    }

    protected function getFilePath(Attachment $attachment)
    {
        $sourceId = $attachment->getSourceId();

        return 'data/upload/' . $sourceId;
    }
}
