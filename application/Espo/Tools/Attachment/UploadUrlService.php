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

namespace Espo\Tools\Attachment;

use Espo\Core\Exceptions\Error;
use Espo\Core\Exceptions\ErrorSilent;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\ForbiddenSilent;
use Espo\Core\HttpClient\ClientFactory;
use Espo\Core\HttpClient\Options;
use Espo\Core\Utils\File\MimeType;
use Espo\Core\Utils\Metadata;
use Espo\Core\Utils\Security\UrlCheck;
use Espo\Entities\Attachment as Attachment;
use Espo\ORM\EntityManager;
use Espo\Repositories\Attachment as AttachmentRepository;
use Espo\Core\HttpClient;

use const PATHINFO_EXTENSION;

class UploadUrlService
{

    public function __construct(
        private AccessChecker $accessChecker,
        private Metadata $metadata,
        private EntityManager $entityManager,
        private MimeType $mimeType,
        private DetailsObtainer $detailsObtainer,
        private UrlCheck $urlCheck,
        private ClientFactory $clientFactory,
    ) {}

    /**
     * Upload an image from and URL and store as attachment.
     *
     * @param non-empty-string $url
     * @throws Forbidden
     * @throws Error
     */
    public function uploadImage(string $url, FieldData $data): Attachment
    {
        if (!$this->urlCheck->isUrlAndNotIternal($url)) {
            throw new ForbiddenSilent("Not allowed URL.");
        }

        $attachment = $this->getAttachmentRepository()->getNew();

        $this->accessChecker->check($data);

        [$type, $contents] = $this->getImageDataByUrl($url) ?? [null, null];

        if (!$type || !$contents) {
            throw new ErrorSilent("Bad image data.");
        }

        $attachment
            ->setName($url)
            ->setType($type)
            ->setContents($contents)
            ->setRole(Attachment::ROLE_ATTACHMENT)
            ->setTargetField($data->getField());

        $attachment->set('parentType', $data->getParentType());
        $attachment->set('relatedType', $data->getRelatedType());

        $size = mb_strlen($contents, '8bit');
        $maxSize = $this->detailsObtainer->getUploadMaxSize($attachment);

        if ($maxSize && $size > $maxSize) {
            throw new Error("File size should not exceed {$maxSize}Mb.");
        }

        $this->getAttachmentRepository()->save($attachment);

        $attachment->clear('contents');

        return $attachment;
    }

    /**
     * @param non-empty-string $url
     * @return ?array{string, string} A type and contents.
     * @throws Error
     */
    private function getImageDataByUrl(string $url): ?array
    {
        $client = $this->clientFactory->create(
            new Options(
                protocols: [HttpClient\Protocol::https, HttpClient\Protocol::http],
                redirect: new HttpClient\Options\Redirect(
                    allow: false,
                ),
                internalHostRestriction: new HttpClient\Options\InternalHostRestriction(
                    restrict: true,
                ),
            )
        );

        $request = HttpClient\RequestCreator::create('GET', $url);

        try {
            $response = $client->send($request);
        } catch (HttpClient\Exceptions\SendException $e) {
            throw new Error(previous: $e);
        }

        $type = $response->getHeader('Content-Type')[0] ?? null;

        if ($type) {
            $type = trim(explode(';', $type)[0]);
        }


        if (!$type) {
            /** @var string $extension */
            $extension = preg_replace('#\?.*#', '', pathinfo($url, PATHINFO_EXTENSION));

            $type = $this->mimeType->getMimeTypeByExtension($extension);
        }

        if (!$type) {
            return null;
        }

        /** @var string[] $imageTypeList */
        $imageTypeList = $this->metadata->get('app.image.allowedFileTypeList') ?? [];

        if (!in_array($type, $imageTypeList)) {
            return null;
        }

        return [$type, (string) $response->getBody()];
    }

    private function getAttachmentRepository(): AttachmentRepository
    {
        /** @var AttachmentRepository */
        return $this->entityManager->getRepositoryByClass(Attachment::class);
    }
}
