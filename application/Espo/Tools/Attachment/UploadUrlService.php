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

namespace Espo\Tools\Attachment;

use Espo\Core\Exceptions\Error;
use Espo\Core\Exceptions\ErrorSilent;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\ForbiddenSilent;
use Espo\Core\Utils\File\MimeType;
use Espo\Core\Utils\Metadata;
use Espo\Core\Utils\Security\UrlCheck;
use Espo\Entities\Attachment as Attachment;
use Espo\ORM\EntityManager;
use Espo\Repositories\Attachment as AttachmentRepository;

class UploadUrlService
{
    private AccessChecker $accessChecker;
    private Metadata $metadata;
    private EntityManager $entityManager;
    private MimeType $mimeType;
    private DetailsObtainer $detailsObtainer;

    public function __construct(
        AccessChecker $accessChecker,
        Metadata $metadata,
        EntityManager $entityManager,
        MimeType $mimeType,
        DetailsObtainer $detailsObtainer,
        private UrlCheck $urlCheck
    ) {
        $this->accessChecker = $accessChecker;
        $this->metadata = $metadata;
        $this->entityManager = $entityManager;
        $this->mimeType = $mimeType;
        $this->detailsObtainer = $detailsObtainer;
    }

    /**
     * Upload an image from and URL and store as attachment.
     *
     * @throws Forbidden
     * @throws Error
     */
    public function uploadImage(string $url, FieldData $data): Attachment
    {
        if (!$this->urlCheck->isNotInternalUrl($url)) {
            throw new ForbiddenSilent("Not allowed URL.");
        }

        $attachment = $this->getAttachmentRepository()->getNew();

        $this->accessChecker->check($data);

        [$type, $contents] = $this->getImageDataByUrl($url) ?? [null, null];

        if (!$type || !$contents) {
            throw new ErrorSilent("Bad image data.");
        }

        $attachment->set([
            'name' => $url,
            'type' => $type,
            'contents' => $contents,
            'role' => Attachment::ROLE_ATTACHMENT,
        ]);

        $attachment->set('parentType', $data->getParentType());
        $attachment->set('relatedType', $data->getRelatedType());
        $attachment->set('field', $data->getField());

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
     * @param string $url
     * @return ?array{string, string} A type and contents.
     */
    private function getImageDataByUrl(string $url): ?array
    {
        $type = null;

        if (!function_exists('curl_init')) {
            return null;
        }

        $opts = [];

        $httpHeaders = [];
        $httpHeaders[] = 'Expect:';

        $opts[\CURLOPT_URL]  = $url;
        $opts[\CURLOPT_HTTPHEADER] = $httpHeaders;
        $opts[\CURLOPT_CONNECTTIMEOUT] = 10;
        $opts[\CURLOPT_TIMEOUT] = 10;
        $opts[\CURLOPT_HEADER] = true;
        $opts[\CURLOPT_VERBOSE] = true;
        $opts[\CURLOPT_SSL_VERIFYPEER] = true;
        $opts[\CURLOPT_SSL_VERIFYHOST] = 2;
        $opts[\CURLOPT_RETURNTRANSFER] = true;
        // Prevents Server Side Request Forgery by redirecting to an internal host.
        $opts[\CURLOPT_FOLLOWLOCATION] = false;
        $opts[\CURLOPT_MAXREDIRS] = 2;
        $opts[\CURLOPT_IPRESOLVE] = \CURL_IPRESOLVE_V4;
        $opts[\CURLOPT_PROTOCOLS] = \CURLPROTO_HTTPS | \CURLPROTO_HTTP;
        $opts[\CURLOPT_REDIR_PROTOCOLS] = \CURLPROTO_HTTPS;

        $ch = curl_init();

        curl_setopt_array($ch, $opts);

        /** @var string|false $response */
        $response = curl_exec($ch);

        if ($response === false) {
            curl_close($ch);

            return null;
        }

        $headerSize = curl_getinfo($ch, \CURLINFO_HEADER_SIZE);

        $header = substr($response, 0, $headerSize);
        $body = substr($response, $headerSize);

        $headLineList = explode("\n", $header);

        foreach ($headLineList as $i => $line) {
            if ($i === 0) {
                continue;
            }

            if (strpos(strtolower($line), strtolower('Content-Type:')) === 0) {
                $part = trim(substr($line, 13));

                if ($part) {
                    $type = trim(explode(";", $part)[0]);
                }
            }
        }

        if (!$type) {
            /** @var string $extension */
            $extension = preg_replace('#\?.*#', '', pathinfo($url, \PATHINFO_EXTENSION));

            $type = $this->mimeType->getMimeTypeByExtension($extension);
        }

        curl_close($ch);

        if (!$type) {
            return null;
        }

        /** @var string[] $imageTypeList */
        $imageTypeList = $this->metadata->get(['app', 'image', 'allowedFileTypeList']) ?? [];

        if (!in_array($type, $imageTypeList)) {
            return null;
        }

        return [$type, $body];
    }

    private function getAttachmentRepository(): AttachmentRepository
    {
        /** @var AttachmentRepository */
        return $this->entityManager->getRepositoryByClass(Attachment::class);
    }
}
