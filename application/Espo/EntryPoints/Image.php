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

namespace Espo\EntryPoints;

use Espo\Repositories\Attachment as AttachmentRepository;

use Espo\Core\Acl;
use Espo\Core\Api\Request;
use Espo\Core\Api\Response;
use Espo\Core\EntryPoint\EntryPoint;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Error;
use Espo\Core\Exceptions\ForbiddenSilent;
use Espo\Core\Exceptions\NotFound;
use Espo\Core\Exceptions\NotFoundSilent;
use Espo\Core\FileStorage\Manager as FileStorageManager;
use Espo\Core\ORM\EntityManager;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\File\Manager as FileManager;
use Espo\Core\Utils\Metadata;
use Espo\Entities\Attachment;

use GdImage;
use RuntimeException;
use Throwable;

class Image implements EntryPoint
{
    /** @var ?string[] */
    protected $allowedRelatedTypeList = null;
    /** @var ?string[] */
    protected $allowedFieldList = null;

    public function __construct(
        private FileStorageManager $fileStorageManager,
        private FileManager $fileManager,
        protected Acl $acl,
        protected EntityManager $entityManager,
        protected Config $config,
        protected Metadata $metadata
    ) {}

    public function run(Request $request, Response $response): void
    {
        $id = $request->getQueryParam('id');
        $size = $request->getQueryParam('size') ?? null;

        if (!$id) {
            throw new BadRequest("No id.");
        }

        $this->show($response, $id, $size);
    }

    /**
     * @throws Error
     * @throws NotFoundSilent
     * @throws NotFound
     * @throws ForbiddenSilent
     */
    protected function show(
        Response $response,
        string $id,
        ?string $size,
        bool $disableAccessCheck = false,
        bool $noCacheHeaders = false,
    ): void {

        $attachment = $this->entityManager->getRDBRepositoryByClass(Attachment::class)->getById($id);

        if (!$attachment) {
            throw new NotFoundSilent("Attachment not found.");
        }

        if (!$disableAccessCheck && !$this->acl->checkEntity($attachment)) {
            throw new ForbiddenSilent("No access to attachment.");
        }

        $fileType = $attachment->getType();

        if (!in_array($fileType, $this->getAllowedFileTypeList())) {
            throw new ForbiddenSilent("Not allowed file type '$fileType'.");
        }

        if ($this->allowedRelatedTypeList) {
            if (!in_array($attachment->getRelatedType(), $this->allowedRelatedTypeList)) {
                throw new NotFoundSilent("Not allowed related type.");
            }
        }

        if ($this->allowedFieldList) {
            if (!in_array($attachment->getTargetField(), $this->allowedFieldList)) {
                throw new NotFoundSilent("Not allowed field.");
            }
        }

        $fileSize = 0;
        $fileName = $attachment->getName();

        $toResize = $size && in_array($fileType, $this->getResizableFileTypeList());

        if ($toResize) {
            $contents = $this->getThumbContents($attachment, $size);

            if ($contents) {
                $fileName = $size . '-' . $attachment->getName();
                $fileSize = strlen($contents);

                $response->writeBody($contents);
            } else {
                $toResize = false;
            }
        }

        if (!$toResize) {
            $stream = $this->fileStorageManager->getStream($attachment);
            $fileSize = $stream->getSize() ?? $this->fileStorageManager->getSize($attachment);

            $response->setBody($stream);
        }

        if ($fileType) {
            $response->setHeader('Content-Type', $fileType);
        }

        $response
            ->setHeader('Content-Disposition', 'inline;filename="' . $fileName . '"')
            ->setHeader('Content-Length', (string) $fileSize)
            ->setHeader('Content-Security-Policy', "default-src 'self'");

        if (!$noCacheHeaders) {
            $response->setHeader('Cache-Control', 'private, max-age=864000, immutable');
        }
    }

    /**
     * @throws Error
     * @throws NotFound
     */
    private function getThumbContents(Attachment $attachment, string $size): ?string
    {
        if (!array_key_exists($size, $this->getSizes())) {
            throw new Error("Bad size.");
        }

        $useCache = !$this->config->get('thumbImageCacheDisabled', false);

        $sourceId = $attachment->getSourceId();

        $cacheFilePath = "data/upload/thumbs/{$sourceId}_$size";

        if ($useCache && $this->fileManager->isFile($cacheFilePath)) {
            return $this->fileManager->getContents($cacheFilePath);
        }

        $filePath = $this->getAttachmentRepository()->getFilePath($attachment);

        if (!$this->fileManager->isFile($filePath)) {
            throw new NotFound("File not found.");
        }

        $fileType = $attachment->getType() ?? '';

        $targetImage = $this->createThumbImage($filePath, $fileType, $size);

        if (!$targetImage) {
            return null;
        }

        ob_start();

        switch ($fileType) {
            case 'image/jpeg':
                imagejpeg($targetImage);

                break;

            case 'image/png':
                imagepng($targetImage);

                break;

            case 'image/gif':
                imagegif($targetImage);

                break;

            case 'image/webp':
                imagewebp($targetImage);

                break;
        }

        $contents = ob_get_contents() ?: '';

        ob_end_clean();

        imagedestroy($targetImage);

        if ($useCache) {
            $this->fileManager->putContents($cacheFilePath, $contents);
        }

        return $contents;
    }

    /**
     * @throws Error
     */
    private function createThumbImage(string $filePath, string $fileType, string $size): ?GdImage
    {
        if (!is_array(getimagesize($filePath))) {
            throw new Error();
        }

        [$originalWidth, $originalHeight] = getimagesize($filePath);

        [$width, $height] = $this->getSizes()[$size];

        if ($originalWidth <= $width && $originalHeight <= $height) {
            $targetWidth = $originalWidth;
            $targetHeight = $originalHeight;
        } else {
            if ($originalWidth > $originalHeight) {
                $targetWidth = $width;
                $targetHeight = (int) ($originalHeight / ($originalWidth / $width));

                if ($targetHeight > $height) {
                    $targetHeight = $height;
                    $targetWidth = (int) ($originalWidth / ($originalHeight / $height));
                }
            } else {
                $targetHeight = $height;
                $targetWidth = (int) ($originalWidth / ($originalHeight / $height));

                if ($targetWidth > $width) {
                    $targetWidth = $width;
                    $targetHeight = (int) ($originalHeight / ($originalWidth / $width));
                }
            }
        }

        if ($targetWidth < 1 || $targetHeight < 1) {
            throw new RuntimeException("No width or height.");
        }

        $targetImage = imagecreatetruecolor($targetWidth, $targetHeight);

        if ($targetImage === false) {
            return null;
        }

        switch ($fileType) {
            case 'image/jpeg':
                $sourceImage = imagecreatefromjpeg($filePath);

                if ($sourceImage === false) {
                    return null;
                }

                $this->resample(
                    $targetImage,
                    $sourceImage,
                    $targetWidth,
                    $targetHeight,
                    $originalWidth,
                    $originalHeight
                );

                break;

            case 'image/png':
                $sourceImage = imagecreatefrompng($filePath);

                if ($sourceImage === false) {
                    return null;
                }

                imagealphablending($targetImage, false);
                imagesavealpha($targetImage, true);

                $transparent = imagecolorallocatealpha($targetImage, 255, 255, 255, 127);

                if ($transparent !== false) {
                    imagefilledrectangle($targetImage, 0, 0, $targetWidth, $targetHeight, $transparent);
                }

                $this->resample(
                    $targetImage,
                    $sourceImage,
                    $targetWidth,
                    $targetHeight,
                    $originalWidth,
                    $originalHeight
                );

                break;

            case 'image/gif':
                $sourceImage = imagecreatefromgif($filePath);

                if ($sourceImage === false) {
                    return null;
                }

                $this->resample(
                    $targetImage,
                    $sourceImage,
                    $targetWidth,
                    $targetHeight,
                    $originalWidth,
                    $originalHeight
                );

                break;

            case 'image/webp':
                try {
                    $sourceImage = imagecreatefromwebp($filePath);
                } catch (Throwable) {
                    return null;
                }

                if ($sourceImage === false) {
                    return null;
                }

                $this->resample(
                    $targetImage,
                    $sourceImage,
                    $targetWidth,
                    $targetHeight,
                    $originalWidth,
                    $originalHeight
                );

                break;
        }

        if (in_array($fileType, $this->getFixOrientationFileTypeList())) {
            $targetImage = $this->fixOrientation($targetImage, $filePath);
        }

        return $targetImage;
    }

    /**
     * @param string $filePath
     * @return ?int
     */
    private function getOrientation(string $filePath)
    {
        if (!function_exists('exif_read_data')) {
            return 0;
        }

        $data = exif_read_data($filePath) ?: [];

        return $data['Orientation'] ?? null;
    }

    private function fixOrientation(GdImage $targetImage, string $filePath): GdImage
    {
        $orientation = $this->getOrientation($filePath);

        if ($orientation) {
            $angle = [0, 0, 0, 180, 0, 0, -90, 0, 90][$orientation];

            $targetImage = imagerotate($targetImage, $angle, 0) ?: $targetImage;
        }

        return $targetImage;
    }

    /**
     * @return string[]
     */
    private function getAllowedFileTypeList(): array
    {
        return $this->metadata->get(['app', 'image', 'allowedFileTypeList']) ?? [];
    }

    /**
     * @return string[]
     */
    private function getResizableFileTypeList(): array
    {
        return $this->metadata->get(['app', 'image', 'resizableFileTypeList']) ?? [];
    }

    /**
     * @return string[]
     */
    private function getFixOrientationFileTypeList(): array
    {
        return $this->metadata->get(['app', 'image', 'fixOrientationFileTypeList']) ?? [];
    }

    /**
     * @return array<string, array{int, int}>
     */
    protected function getSizes(): array
    {
        return $this->metadata->get(['app', 'image', 'sizes']) ?? [];
    }

    private function getAttachmentRepository(): AttachmentRepository
    {
        /** @var AttachmentRepository */
        return $this->entityManager->getRepository(Attachment::ENTITY_TYPE);
    }

    private function resample(
        GdImage $targetImage,
        GdImage $sourceImage,
        int $targetWidth,
        int $targetHeight,
        int $originalWidth,
        int $originalHeight
    ): void {

        imagecopyresampled(
            $targetImage,
            $sourceImage,
            0, 0, 0, 0,
            $targetWidth, $targetHeight, $originalWidth, $originalHeight
        );
    }
}
