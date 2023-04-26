<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2023 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

namespace Espo\Core\Mail\Parsers;

use Psr\Http\Message\StreamInterface;

use Espo\Entities\Email;
use Espo\Entities\Attachment;

use Espo\Core\Mail\Message;
use Espo\Core\Mail\Parser;
use Espo\Core\Mail\Message\Part;
use Espo\Core\Mail\Message\MailMimeParser\Part as WrapperPart;

use Espo\ORM\EntityManager;

use ZBateson\MailMimeParser\Header\AddressHeader;
use ZBateson\MailMimeParser\MailMimeParser as WrappeeParser;
use ZBateson\MailMimeParser\Message\Part\MessagePart;
use ZBateson\MailMimeParser\Message\Part\MimePart;
use ZBateson\MailMimeParser\Message as ParserMessage;

use stdClass;

/**
 * An adapter for MailMimeParser library.
 */
class MailMimeParser implements Parser
{
    /** @var array<string, string> */
    private array $extMimeTypeMap = [
        'jpg' => 'image/jpg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
    ];

    private EntityManager $entityManager;
    private ?WrappeeParser $parser = null;

    /**
     * @var array<string, ParserMessage>
     */
    private array $messageHash = [];

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    protected function getParser(): WrappeeParser
    {
        if (!$this->parser) {
            $this->parser = new WrappeeParser();
        }

        return $this->parser;
    }

    protected function loadContent(Message $message): void
    {
        $raw = $message->getFullRawContent();

        $key = spl_object_hash($message);

        $this->messageHash[$key] = $this->getParser()->parse($raw);
    }


    /**
     * @return ParserMessage
     */
    protected function getMessage(Message $message)
    {
        $key = spl_object_hash($message);

        if (!array_key_exists($key, $this->messageHash)) {
            $raw = $message->getRawHeader();

            if (!$raw) {
                $raw = $message->getFullRawContent();
            }

            $this->messageHash[$key] = $this->getParser()->parse($raw);
        }

        return $this->messageHash[$key];
    }

    public function hasHeader(Message $message, string $name): bool
    {
        return $this->getMessage($message)->getHeaderValue($name) !== null;
    }

    public function getHeader(Message $message, string $name): ?string
    {
        if (!$this->hasHeader($message, $name)) {
            return null;
        }

        return $this->getMessage($message)->getHeaderValue($name);
    }

    public function getMessageId(Message $message): ?string
    {
        $messageId = $this->getHeader($message, 'Message-ID');

        if (!$messageId) {
            return null;
        }

        if ($messageId[0] !== '<') {
            $messageId = '<' . $messageId . '>';
        }

        return $messageId;
    }

    public function getAddressNameMap(Message $message): stdClass
    {
        $map = (object) [];

        foreach (['from', 'to', 'cc', 'reply-To'] as $type) {
            $header = $this->getMessage($message)->getHeader($type);

            if (!$header || !method_exists($header, 'getAddresses')) {
                continue;
            }

            /** @var AddressHeader $header */

            $list = $header->getAddresses();

            foreach ($list as $item) {
                $address = $item->getEmail();
                $name = $item->getName();

                if ($name && $address) {
                    $map->$address = $name;
                }
            }
        }

        return $map;
    }

    public function getAddressData(Message $message, string $type): ?stdClass
    {
        $header = $this->getMessage($message)->getHeader($type);

        /** @var ?AddressHeader $header */

        if ($header && method_exists($header, 'getAddresses')) {
            foreach ($header->getAddresses() as $item) {
                return (object) [
                    'address' => $item->getEmail(),
                    'name' => $item->getName(),
                ];
            }
        }

        return null;
    }

    /**
     * @return string[]
     */
    public function getAddressList(Message $message, string $type): array
    {
        $addressList = [];

        $header = $this->getMessage($message)->getHeader($type);

        /** @var ?AddressHeader $header */

        if ($header && method_exists($header, 'getAddresses')) {
            $list = $header->getAddresses();

            foreach ($list as $address) {
                $addressList[] = $address->getEmail();
            }
        }

        return $addressList;
    }

    /**
     * @return Part[]
     */
    public function getPartList(Message $message): array
    {
        $wrappeeList = $this->getMessage($message)->getChildParts();

        $partList = [];

        foreach ($wrappeeList as $wrappee) {
            $partList[] = new WrapperPart($wrappee);
        }

        return $partList;
    }

    /**
     * @return Attachment[]
     */
    public function getInlineAttachmentList(Message $message, Email $email): array
    {
        $inlineAttachmentList = [];

        $this->loadContent($message);

        $bodyPlain = '';
        $bodyHtml = '';

        $htmlPartCount = $this->getMessage($message)->getHtmlPartCount();
        $textPartCount = $this->getMessage($message)->getTextPartCount();

        if (!$htmlPartCount) {
            $bodyHtml = $this->getMessage($message)->getHtmlContent();
        }

        if (!$textPartCount) {
            $bodyPlain = $this->getMessage($message)->getTextContent();
        }

        for ($i = 0; $i < $htmlPartCount; $i++) {
            if ($i) {
                $bodyHtml .= "<br>";
            }

            $inlinePart = $this->getMessage($message)->getHtmlPart($i);

            $bodyHtml .= $inlinePart->getContent();
        }

        for ($i = 0; $i < $textPartCount; $i++) {
            if ($i) {
                $bodyPlain .= "\n";
            }

            $inlinePart = $this->getMessage($message)->getTextPart($i);

            $bodyPlain .= $inlinePart->getContent();
        }

        if ($bodyHtml) {
            $email->setIsHtml(true);
            $email->setBody($bodyHtml);

            if ($bodyPlain) {
                $email->setBodyPlain($bodyPlain);
            }
        }
        else {
            $email->setIsHtml(false);
            $email->setBody($bodyPlain);
            $email->setBodyPlain($bodyPlain);
        }

        if (!$email->getBody() && $email->hasBodyPlain()) {
            $email->setBody($email->getBodyPlain());
        }

        $attachmentPartList = $this->getMessage($message)->getAllAttachmentParts();

        $inlineIds = [];

        foreach ($attachmentPartList as $attachmentPart) {
            if (!$attachmentPart instanceof MimePart) {
                continue;
            }

            /** @var Attachment $attachment */
            $attachment = $this->entityManager->getNewEntity(Attachment::ENTITY_TYPE);

            $contentType = $this->detectAttachmentContentType($attachmentPart);

            $disposition = $attachmentPart->getHeaderValue('Content-Disposition');

            /** @var ?string $filename */
            $filename = $attachmentPart->getHeaderParameter('Content-Disposition', 'filename', null);

            if ($filename === null) {
                $filename = $attachmentPart->getHeaderParameter('Content-Type', 'name', 'unnamed');
            }

            if ($contentType) {
                $contentType = strtolower($contentType);
            }

            $attachment->setName($filename);
            $attachment->setType($contentType);

            $content = '';

            /** @var StreamInterface|null $binaryContentStream */
            $binaryContentStream = $attachmentPart->getBinaryContentStream();

            if ($binaryContentStream) {
                $content = $binaryContentStream->getContents();
            }

            $contentId = $attachmentPart->getHeaderValue('Content-ID');

            if ($contentId) {
                $contentId = trim($contentId, '<>');
            }

            if ($disposition === 'inline') {
                $attachment->setRole(Attachment::ROLE_INLINE_ATTACHMENT);
                $attachment->setTargetField('body');
            }
            else {
                $disposition = 'attachment';

                $attachment->setRole(Attachment::ROLE_ATTACHMENT);
                $attachment->setTargetField('attachments');
            }

            $attachment->setContents($content);

            $this->entityManager->saveEntity($attachment);

            if ($disposition === 'attachment') {
                $email->addLinkMultipleId('attachments', $attachment->getId());

                if ($contentId) {
                    $inlineIds[$contentId] = $attachment->getId();
                }

                continue;
            }

            // inline disposition
            if ($contentId) {
                $inlineIds[$contentId] = $attachment->getId();

                $inlineAttachmentList[] = $attachment;

                continue;
            }

            $email->addLinkMultipleId('attachments', $attachment->getId());
        }

        $body = $email->getBody();

        if (!empty($body)) {
            foreach ($inlineIds as $cid => $attachmentId) {
                if (str_contains($body, 'cid:' . $cid)) {
                    $body = str_replace('cid:' . $cid, '?entryPoint=attachment&amp;id=' . $attachmentId, $body);

                    continue;
                }

                $email->addLinkMultipleId('attachments', $attachmentId);
            }

            $email->setBody($body);
        }

        /** @var ?MessagePart $textCalendarPart */
        $textCalendarPart =
            $this->getMessage($message)->getAllPartsByMimeType('text/calendar')[0] ??
            $this->getMessage($message)->getAllPartsByMimeType('application/ics')[0] ??
            null;

        if ($textCalendarPart && $textCalendarPart->hasContent()) {
            $email->set('icsContents', $textCalendarPart->getContent());
        }

        return $inlineAttachmentList;
    }

    private function detectAttachmentContentType(MimePart $part): ?string
    {
        $contentType = $part->getHeaderValue('Content-Type');

        if ($contentType && strtolower($contentType) !== 'application/octet-stream') {
            return $contentType;
        }

        $ext = $this->getAttachmentFilenameExtension($part);

        if (!$ext) {
            return null;
        }

        return $this->extMimeTypeMap[$ext] ?? null;
    }

    private function getAttachmentFilenameExtension(MimePart $part): ?string
    {
        /** @var ?string $filename */
        $filename = $part->getHeaderParameter('Content-Disposition', 'filename', null);

        if ($filename === null) {
            $filename = $part->getHeaderParameter('Content-Type', 'name', 'unnamed');
        }

        if (!$filename) {
            return null;
        }

        $ext = explode('.', $filename)[1] ?? null;

        if (!$ext) {
            return null;
        }

        return strtolower($ext);
    }
}
