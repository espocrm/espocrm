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

namespace Espo\Core\FileStorage;

use Espo\Entities\Attachment as AttachmentEntity;
use Psr\Http\Message\StreamInterface;

use GuzzleHttp\Psr7\Utils;

use RuntimeException;


/**
 * An access point for file storing and fetching. Files are represented as Attachment entities.
 */
class Manager
{
    private const DEFAULT_STORAGE = 'EspoUploadDir';

    /** @var array<string, Storage> */
    private array $implHash = [];

    /**
     * @var array<string, resource>
     * @noinspection PhpPropertyOnlyWrittenInspection
     * @phpstan-ignore-next-line Used to prevent deleting from memory.
     */
    private $resourceMap = [];

    public function __construct(private Factory $factory)
    {}

    /**
     * Whether a file exists in a storage.
     */
    public function exists(AttachmentEntity $attachment): bool
    {
        $implementation = $this->getImplementation($attachment);

        return $implementation->exists(self::wrapAttachmentEntity($attachment));
    }

    /**
     * Get a file size.
     */
    public function getSize(AttachmentEntity $attachment): int
    {
        $implementation = $this->getImplementation($attachment);

        return $implementation->getSize(self::wrapAttachmentEntity($attachment));
    }

    /**
     * Get file contents.
     */
    public function getContents(AttachmentEntity $attachment): string
    {
        $implementation = $this->getImplementation($attachment);

        return $implementation->getStream(self::wrapAttachmentEntity($attachment))->getContents();
    }

    /**
     * Get a file contents stream.
     */
    public function getStream(AttachmentEntity $attachment): StreamInterface
    {
        $implementation = $this->getImplementation($attachment);

        return $implementation->getStream(self::wrapAttachmentEntity($attachment));
    }

    /**
     * Store file contents represented as a stream.
     */
    public function putStream(AttachmentEntity $attachment, StreamInterface $stream): void
    {
        $implementation = $this->getImplementation($attachment);

        $implementation->putStream(self::wrapAttachmentEntity($attachment), $stream);
    }

    /**
     * Store file contents.
     */
    public function putContents(AttachmentEntity $attachment, string $contents): void
    {
        $implementation = $this->getImplementation($attachment);

        $stream = Utils::streamFor($contents);

        $implementation->putStream(self::wrapAttachmentEntity($attachment), $stream);
    }

    /**
     * Remove a file.
     */
    public function unlink(AttachmentEntity $attachment): void
    {
        $implementation = $this->getImplementation($attachment);

        $implementation->unlink(self::wrapAttachmentEntity($attachment));
    }

    /**
     * Whether an attachment storage is local.
     */
    public function isLocal(AttachmentEntity $attachment): bool
    {
        $implementation = $this->getImplementation($attachment);

        return $implementation instanceof Local;
    }

    /**
     * Get a local file path. If a file is not stored locally, a temporary file will be created.
     */
    public function getLocalFilePath(AttachmentEntity $attachment): string
    {
        $implementation = $this->getImplementation($attachment);

        if ($implementation instanceof Local) {
            return $implementation->getLocalFilePath(self::wrapAttachmentEntity($attachment));
        }

        $contents = $this->getContents($attachment);

        $resource = tmpfile();

        if ($resource === false) {
            throw new RuntimeException("Could not create temp file.");
        }

        fwrite($resource, $contents);

        // PhpStan's bug. Check later, remove 'ignore' if fixed.
        /** @phpstan-ignore-next-line */
        $path = stream_get_meta_data($resource)['uri'];

        if (!$path) {
            throw new RuntimeException("No uri.");
        }

        // To prevent deleting.
        $this->resourceMap[$path] = $resource;

        return $path;
    }

    private static function wrapAttachmentEntity(AttachmentEntity $attachment): AttachmentEntityWrapper
    {
        return new AttachmentEntityWrapper($attachment);
    }

    private function getImplementation(AttachmentEntity $attachment): Storage
    {
        $storage = $attachment->getStorage();

        if (!$storage) {
            $storage = self::DEFAULT_STORAGE;
        }

        if (!array_key_exists($storage, $this->implHash)) {
            $this->implHash[$storage] = $this->factory->create($storage);
        }

        return $this->implHash[$storage];
    }
}
