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

class ZendMail
{
    private $entityManager;

    public function __construct($entityManager)
    {
        $this->entityManager = $entityManager;
    }

    protected function getEntityManager()
    {
        return $this->entityManager;
    }

    public function checkMessageAttribute($message, $attribute)
    {
        $zendMessage = $message->getZendMessage();

        return isset($zendMessage->$attribute);
    }

    public function getMessageAttribute($message, $attribute)
    {
        $zendMessage = $message->getZendMessage();

        if (!isset($zendMessage->$attribute)) return null;

        return $zendMessage->$attribute;
    }

    public function getMessageMessageId($message)
    {
        $zendMessage = $message->getZendMessage();

        if (!isset($zendMessage->messageId)) return null;

        $messageId = $zendMessage->messageId;
        $messageId = str_replace('<<', '<', $messageId);
        $messageId = str_replace('>>', '>', $messageId);

        return $messageId;
    }

    public function getAddressNameMap($message)
    {
        $map = (object) [];

        $zendMessage = $message->getZendMessage();

        foreach (['from', 'to', 'cc', 'reply-To'] as $type) {
            if (isset($zendMessage->$type)) {
                $list = $this->normilizeHeader($zendMessage->getHeader($type))->getAddressList();
                foreach ($list as $item) {
                    $name = $item->getName();
                    $address = $item->getEmail();
                    if ($name && $address && $name !== $address) {
                        $map->$address = $name;
                    }
                }
            }
        }

        return $map;
    }

    public function getAddressDataFromMessage($message, $type)
    {
        $zendMessage = $message->getZendMessage();

        $addressList = array();
        if (isset($zendMessage->$type)) {
            $list = $this->normilizeHeader($zendMessage->getHeader($type))->getAddressList();
            foreach ($list as $address) {
                return [
                    'address' => $address->getEmail(),
                    'name' => $address->getName()
                ];
            }
        }
        return null;
    }

    public function getAddressListFromMessage($message, $type)
    {
        $zendMessage = $message->getZendMessage();

        $addressList = array();
        if (isset($zendMessage->$type)) {
            $list = $this->normilizeHeader($zendMessage->getHeader($type))->getAddressList();
            foreach ($list as $address) {
                $addressList[] = $address->getEmail();
            }
        }
        return $addressList;
    }

    public function fetchContentParts(\Espo\Entities\Email $email, $message, &$inlineAttachmentList = [])
    {
        $zendMessage = $message->getZendMessage();

        $inlineIds = array();

        if ($zendMessage->isMultipart()) {
            foreach (new \RecursiveIteratorIterator($zendMessage) as $part) {
                $this->importPartDataToEmail($email, $part, $inlineIds, null, $inlineAttachmentList);
            }
        } else {
            $this->importPartDataToEmail($email, $zendMessage, $inlineIds, 'text/plain', $inlineAttachmentList);
        }

        if (!$email->get('body') && $email->hasBodyPlain()) {
            $email->set('body', $email->get('bodyPlain'));
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

    protected function importPartDataToEmail(\Espo\Entities\Email $email, $part, &$inlineIds = array(), $defaultContentType = null, &$inlineAttachmentList = [])
    {
        try {
            $type = null;

            if ($part->getHeaders() && isset($part->contentType)) {
                $type = strtok($part->contentType, ';');
            }

            $contentDisposition = false;
            if (isset($part->ContentDisposition)) {
                if (strpos(strtolower($part->ContentDisposition), 'attachment') === 0) {
                    $contentDisposition = 'attachment';
                } else if (strpos(strtolower($part->ContentDisposition), 'inline') === 0) {
                    $contentDisposition = 'inline';
                }
            } else if (isset($part->contentID)) {
                $contentDisposition = 'inline';
            }

            if (empty($type)) {
                if (!empty($defaultContentType)) {
                    $type = $defaultContentType;
                } else {
                    return;
                }
            }

            $encoding = null;
            $isAttachment = true;
            if ($type == 'text/plain' || $type == 'text/html') {
                if ($contentDisposition !== 'attachment') {
                    $isAttachment = false;
                    $content = $this->getContentFromPart($part);
                    if ($type == 'text/plain') {
                        $bodyPlain = '';
                        if ($email->hasBodyPlain()) {
                            $bodyPlain .= $email->get('bodyPlain') . "\n";
                        }
                        $bodyPlain .= $content;
                        $email->set('bodyPlain', $bodyPlain);
                    } else if ($type == 'text/html') {
                        $body = '';
                        if ($email->get('body')) {
                            $body .= $email->get('body') . "<br>";
                        }
                        $body .= $content;
                        $email->set('isHtml', true);
                        $email->set('body', $body);
                    }
                }
            }

            if ($isAttachment) {
                $content = $part->getContent();

                $disposition = null;

                $fileName = null;
                $contentId = null;

                if ($contentDisposition) {
                    if ($contentDisposition === 'attachment') {
                        $fileName = $this->fetchFileNameFromContentDisposition($part->ContentDisposition);
                        if ($fileName) {
                            $disposition = 'attachment';
                        }
                    } else if ($contentDisposition ===  'inline') {
                        if (isset($part->contentID)) {
                            $contentId = trim($part->contentID, '<>');
                            $fileName = $contentId;
                            $disposition = 'inline';
                        } else {
                            // for iOS attachments
                            if (empty($fileName)) {
                                $fileName = $this->fetchFileNameFromContentDisposition($part->ContentDisposition);
                                if ($fileName) {
                                    $disposition = 'attachment';
                                }
                            }
                        }
                    }
                }

                if (isset($part->contentTransferEncoding)) {
                    $encoding = strtolower($this->normilizeHeader($part->getHeader('Content-Transfer-Encoding'))->getTransferEncoding());
                }

                $attachment = $this->getEntityManager()->getEntity('Attachment');
                $attachment->set('name', $fileName);
                $attachment->set('type', $type);

                if ($disposition == 'inline') {
                    $attachment->set('role', 'Inline Attachment');
                } else {
                    $attachment->set('role', 'Attachment');
                }

                if ($encoding == 'base64') {
                    $content = base64_decode($content);
                }

                $attachment->set('contents', $content);

                $this->getEntityManager()->saveEntity($attachment);

                if ($disposition == 'attachment') {
                    $attachmentsIds = $email->get('attachmentsIds');
                    $attachmentsIds[] = $attachment->id;
                    $email->set('attachmentsIds', $attachmentsIds);

                    if (isset($part->contentID)) {
                        $contentId = trim($part->contentID, '<>');
                        if ($contentId) {
                            $inlineIds[$contentId] = $attachment->id;
                        }
                    }
                } else if ($disposition == 'inline') {
                    $inlineIds[$contentId] = $attachment->id;
                    $inlineAttachmentList[] = $attachment;
                }
            }
        } catch (\Exception $e) {}
    }

    protected function getContentFromPart($part)
    {
        if ($part instanceof \Zend\Mime\Part) {
            $content = $part->getRawContent();
            if (strtolower($part->charset) != 'utf-8') {
                $content = mb_convert_encoding($content, 'UTF-8', $part->charset);
            }
        } else {
            $content = $part->getContent();

            $encoding = null;

            if (isset($part->contentTransferEncoding)) {
                $cteHeader = $this->normilizeHeader($part->getHeader('Content-Transfer-Encoding'));
                $encoding = strtolower($cteHeader->getTransferEncoding());
            }

            if ($encoding == 'base64') {
                $content = base64_decode($content);
            }

            $charset = 'UTF-8';

            if (isset($part->contentType)) {
                $ctHeader = $this->normilizeHeader($part->getHeader('Content-Type'));
                $charsetParamValue = $ctHeader->getParameter('charset');
                if (!empty($charsetParamValue)) {
                    $charset = strtoupper($charsetParamValue);
                }
            }

            if (isset($part->contentTransferEncoding)) {
                $cteHeader = $this->normilizeHeader($part->getHeader('Content-Transfer-Encoding'));
                if ($cteHeader->getTransferEncoding() == 'quoted-printable') {
                    $content = quoted_printable_decode($content);
                }
            }

            if ($charset !== 'UTF-8') {
                $content = mb_convert_encoding($content, 'UTF-8', $charset);
            }
        }
        return $content;
    }

    protected function normilizeHeader($header)
    {
        if (is_a($header, 'ArrayIterator')) {
            return $header->current();
        } else {
            return $header;
        }
    }

    protected function fetchFileNameFromContentDisposition($contentDisposition)
    {
        $contentDisposition = preg_replace('/\\\\"/', "{{_!Q!U!O!T!E!_}}", $contentDisposition);

        $fileName = false;
        $m = array();

        if (preg_match('/filename="([^"]+)";?/i', $contentDisposition, $m)) {
            $fileName = $m[1];
        } else if (preg_match('/filename=([^";]+);?/i', $contentDisposition, $m)) {
            $fileName = $m[1];
        } else if (preg_match('/filename\*="([^"]+)";?/i', $contentDisposition, $m)) {
            $fileName = $m[1];
            $fileName = $this->decodeAttachmentFileName($fileName);
        } else if (preg_match('/filename\*=([^";]+);?/i', $contentDisposition, $m)) {
            $fileName = $m[1];
            $fileName = $this->decodeAttachmentFileName($fileName);
        } else {
            $fileName = '';
            foreach (['0', '1'] as $i) {
                if (preg_match('/filename\*'.$i.'[\*]?="([^"]+)";?/i', $contentDisposition, $m)) {
                    $part = $m[1];
                    $fileName .= $part;
                } else if (preg_match('/filename\*'.$i.'[\*]?=([^";]+);?/i', $contentDisposition, $m)) {
                    $part = $m[1];
                    $fileName .= $part;
                }
            }

            if ($fileName === '') {
                $fileName = null;
            } else {
                $fileName = $this->decodeAttachmentFileName($fileName);
            }
        }

        if ($fileName) {
            $fileName = str_replace('{{_!Q!U!O!T!E!_}}', '"', $fileName);
        }

        return $fileName;
    }

    protected function decodeAttachmentFileName($fileName)
    {
        if ($fileName && stripos($fileName, "''") !== false) {
            list($encoding, $fileName) = explode("''", $fileName);
            $fileName = rawurldecode($fileName);
            if (strtoupper($encoding) !== 'UTF-8') {
                if ($encoding) {
                    $fileName = mb_convert_encoding($fileName, 'UTF-8', $encoding);
                }
            }
        }
        return $fileName;
    }

}

