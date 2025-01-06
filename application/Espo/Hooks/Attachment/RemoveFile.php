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

namespace Espo\Hooks\Attachment;

use Espo\Core\FileStorage\Manager as FileStorageManager;
use Espo\Core\Hook\Hook\AfterRemove;
use Espo\Core\Utils\File\Manager as FileManager;
use Espo\Core\Utils\Metadata;
use Espo\Entities\Attachment;
use Espo\ORM\Entity;
use Espo\ORM\EntityManager;
use Espo\ORM\Repository\Option\RemoveOptions;

/**
 * @implements AfterRemove<Attachment>
 */
class RemoveFile implements AfterRemove
{
    public function __construct(
        private Metadata $metadata,
        private EntityManager $entityManager,
        private FileManager $fileManager,
        private FileStorageManager $fileStorageManager
    ) {}

    /**
     * @param Attachment $entity
     */
    public function afterRemove(Entity $entity, RemoveOptions $options): void
    {
        $duplicateCount = $this->entityManager
            ->getRDBRepositoryByClass(Attachment::class)
            ->where([
                'OR' => [
                    'sourceId' => $entity->getSourceId(),
                    'id' => $entity->getSourceId(),
                ]
            ])
            ->count();

        if ($duplicateCount) {
            return;
        }

        if ($this->fileStorageManager->exists($entity)) {
            $this->fileStorageManager->unlink($entity);
        }

        $this->removeThumbs($entity);
    }

    private function removeThumbs(Attachment $entity): void
    {
        /** @var string[] $typeList */
        $typeList = $this->metadata->get(['app', 'image', 'resizableFileTypeList']) ?? [];

        if (!in_array($entity->getType(), $typeList)) {
            return;
        }

        /** @var string[] $sizeList */
        $sizeList = array_keys($this->metadata->get(['app', 'image', 'sizes']) ?? []);

        foreach ($sizeList as $size) {
            $filePath = "data/upload/thumbs/{$entity->getSourceId()}_{$size}";

            if ($this->fileManager->isFile($filePath)) {
                $this->fileManager->removeFile($filePath);
            }
        }
    }
}
