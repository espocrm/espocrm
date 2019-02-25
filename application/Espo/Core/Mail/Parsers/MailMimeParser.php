<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

class MailMimeParser
{
    private $entityManager;

    private $parser = array();

    protected $messageHash = array();

    public function __construct($entityManager)
    {
        $this->entityManager = $entityManager;
    }

    protected function getEntityManager()
    {
        return $this->entityManager;
    }

    protected function getParser()
    {
        if (!$this->parser) {
            $this->parser = new \ZBateson\MailMimeParser\MailMimeParser();
        }

        return $this->parser;
    }

    protected function loadContent($message)
    {
        $raw = $message->getFullRawContent();
        $key = spl_object_hash($message);
        $this->messageHash[$key] = $this->getParser()->parse($raw);
    }

    protected function getMessage($message)
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

    public function checkMessageAttribute($message, $attribute)
    {
        return $this->getMessage($message)->getHeaderValue($attribute) !== null;
    }

    public function getMessageAttribute($message, $attribute)
    {
        if (!$this->checkMessageAttribute($message, $attribute)) return null;

        return $this->getMessage($message)->getHeaderValue($attribute);
    }

    public function getMessageMessageId($message)
    {
        return $this->getMessageAttribute($message, 'Message-ID');
    }

    public function getAddressNameMap($message)
    {
        $map = (object) [];

        foreach (['from', 'to', 'cc', 'reply-To'] as $type) {
            $header = $this->getMessage($message)->getHeader($type);
            if ($header) {
                $list = $header->getAddresses();
                foreach ($list as $item) {
                    $address = $item->getEmail();
                    $name = $item->getName();
                    if ($name && $address) {
                        $map->$address = $name;
                    }
                }
            }
        }

        return $map;
    }

    public function getAddressDataFromMessage($message, $type)
    {
        $addressList = [];
        $header = $this->getMessage($message)->getHeader($type);
        if ($header) {
            foreach ($header->getAddresses() as $item) {
                return [
                    'address' => $item->getEmail(),
                    'name' => $item->getName()
                ];
            }
        }
        return null;
    }

    public function getAddressListFromMessage($message, $type)
    {
        $addressList = [];
        $header = $this->getMessage($message)->getHeader($type);
        if ($header) {
            $list = $header->getAddresses();
            foreach ($list as $address) {
                $addressList[] = $address->getEmail();
            }
        }
        return $addressList;
    }

    public function fetchContentParts(\Espo\Entities\Email $email, $message, &$inlineAttachmentList = [])
    {
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
            if ($i) $bodyHtml .= "<br>";
            $inlinePart = $this->getMessage($message)->getHtmlPart($i);
            $bodyHtml .= $inlinePart->getContent();
        }

        for ($i = 0; $i < $textPartCount; $i++) {
            if ($i) $bodyPlain .= "\n";
            $inlinePart = $this->getMessage($message)->getTextPart($i);
            $bodyPlain .= $inlinePart->getContent();
        }

        if ($bodyHtml) {
            $email->set('isHtml', true);
            $email->set('body', $bodyHtml);
            $email->set('bodyPlain', $bodyPlain);
        } else {
            $email->set('isHtml', false);
            $email->set('body', $bodyPlain);
            $email->set('bodyPlain', $bodyPlain);
        }

        if (!$email->get('body') && $email->hasBodyPlain()) {
            $email->set('body', $email->get('bodyPlain'));
        }

        $attachmentObjList = $this->getMessage($message)->getAllAttachmentParts();
        $inlineIds = array();

        foreach ($attachmentObjList as $attachmentObj) {
            $attachment = $this->getEntityManager()->getEntity('Attachment');

            $content = $attachmentObj->getContent();

            $disposition = $attachmentObj->getHeaderValue('Content-Disposition');

            $attachment = $this->getEntityManager()->getEntity('Attachment');

            $filename = $attachmentObj->getHeaderParameter('Content-Disposition', 'filename', null);
            if ($filename === null) {
                $filename = $attachmentObj->getHeaderParameter('Content-Type', 'name', 'unnamed');
            }
            $attachment->set('name', $filename);
            $attachment->set('type', $attachmentObj->getHeaderValue('Content-Type'));

            $contentId = $attachmentObj->getHeaderValue('Content-ID');

            if ($contentId) {
                $contentId = trim($contentId, '<>');
            }

            if ($disposition == 'inline') {
                $attachment->set('role', 'Inline Attachment');
            } else {
                $disposition = 'attachment';
                $attachment->set('role', 'Attachment');
            }

            $attachment->set('contents', $content);

            $this->getEntityManager()->saveEntity($attachment);

            if ($disposition == 'attachment') {
                $email->addLinkMultipleId('attachments', $attachment->id);
                if ($contentId) {
                    $inlineIds[$contentId] = $attachment->id;
                }
            } else if ($disposition == 'inline') {
                if ($contentId) {
                    $inlineIds[$contentId] = $attachment->id;
                    $inlineAttachmentList[] = $attachment;
                } else {
                    $email->addLinkMultipleId('attachments', $attachment->id);
                }
            }
        }

        $body = $email->get('body');

        if (!empty($body)) {
            foreach ($inlineIds as $cid => $attachmentId) {
                if (strpos($body, 'cid:' . $cid) !== false) {
                    $body = str_replace('cid:' . $cid, '?entryPoint=attachment&amp;id=' . $attachmentId, $body);
                } else {
                    $email->addLinkMultipleId('attachments', $attachmentId);
                }
            }
            $email->set('body', $body);
        }
    }
}

