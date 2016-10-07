<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2015 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
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

namespace Espo\Core\Mail;

use \Zend\Mime\Mime as Mime;

use \Espo\ORM\Entity;
use \Espo\ORM\Email;

class Importer
{
    private $entityManager;

    private $config;

    private $filtersMatcher;

    public function __construct($entityManager, $config)
    {
        $this->entityManager = $entityManager;
        $this->config = $config;
        $this->filtersMatcher = new FiltersMatcher();
    }

    protected function getEntityManager()
    {
        return $this->entityManager;
    }

    protected function getConfig()
    {
        return $this->config;
    }

    protected function getFiltersMatcher()
    {
        return $this->filtersMatcher;
    }

    public function importMessage($message, $assignedUserId = null, $teamsIdList = [], $userIdList = [], $filterList = [], $fetchOnlyHeader = false, $folderData = null)
    {
        try {
            $email = $this->getEntityManager()->getEntity('Email');

            $email->set('isBeingImported', true);

            $subject = '';
            if (isset($message->subject)) {
                $subject = $message->subject;
            }
            if (!empty($subject) && is_string($subject)) {
                $subject = trim($subject);
            }
            if ($subject !== '0' && empty($subject)) {
                $subject = '(No Subject)';
            }

            $email->set('isHtml', false);
            $email->set('name', $subject);
            $email->set('status', 'Archived');
            $email->set('attachmentsIds', []);
            if ($assignedUserId) {
                $email->set('assignedUserId', $assignedUserId);
                $email->addLinkMultipleId('assignedUsers', $assignedUserId);
            }
            $email->set('teamsIds', $teamsIdList);

            if (!empty($userIdList)) {
                foreach ($userIdList as $uId) {
                    $email->addLinkMultipleId('users', $uId);
                }
            }

            $fromArr = $this->getAddressListFromMessage($message, 'from');
            if (isset($message->from)) {
                $email->set('fromString', $message->from);
            }
            if (isset($message->replyTo)) {
                $email->set('replyToString', $message->replyTo);
            }

            $toArr = $this->getAddressListFromMessage($message, 'to');
            $ccArr = $this->getAddressListFromMessage($message, 'cc');
            $replyToArr = $this->getAddressListFromMessage($message, 'replyTo');

            $email->set('from', $fromArr[0]);
            $email->set('to', implode(';', $toArr));
            $email->set('cc', implode(';', $ccArr));
            $email->set('replyTo', implode(';', $replyToArr));

            if ($folderData) {
                foreach ($folderData as $uId => $folderId) {
                    $email->setLinkMultipleColumn('users', 'folderId', $uId, $folderId);
                }
            }

            if ($this->getFiltersMatcher()->match($email, $filterList, true)) {
                return false;
            }

            if (isset($message->messageId) && !empty($message->messageId)) {
                $email->set('messageId', $message->messageId);
                if (isset($message->deliveredTo)) {
                    $email->set('messageIdInternal', $message->messageId . '-' . $message->deliveredTo);
                }
                if (stripos($message->messageId, '@espo-system') !== false) {
                    return;
                }
            }

            if ($duplicate = $this->findDuplicate($email)) {
                if ($assignedUserId) {
                    $duplicate->addLinkMultipleId('users', $assignedUserId);
                    $duplicate->addLinkMultipleId('assignedUsers', $assignedUserId);
                }
                if (!empty($userIdList)) {
                    foreach ($userIdList as $uId) {
                        $duplicate->addLinkMultipleId('users', $uId);
                    }
                }

                if ($folderData) {
                    foreach ($folderData as $uId => $folderId) {
                        $email->setLinkMultipleColumn('users', 'folderId', $uId, $folderId);
                    }
                }

                $duplicate->set('isBeingImported', true);

                $this->getEntityManager()->saveEntity($duplicate);

                if (!empty($teamsIdList)) {
                    foreach ($teamsIdList as $teamId) {
                        $this->getEntityManager()->getRepository('Email')->relate($duplicate, 'teams', $teamId);
                    }
                }
                return $duplicate;
            }

            if (isset($message->date)) {
                $dt = new \DateTime($message->date);
                if ($dt) {
                    $dateSent = $dt->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');
                    $email->set('dateSent', $dateSent);
                }
            } else {
                $email->set('dateSent', date('Y-m-d H:i:s'));
            }
            if (isset($message->deliveryDate)) {
                $dt = new \DateTime($message->deliveryDate);
                if ($dt) {
                    $deliveryDate = $dt->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');
                    $email->set('deliveryDate', $deliveryDate);
                }
            }

            $inlineIds = array();

            if (!$fetchOnlyHeader) {
                if ($message->isMultipart()) {
                    foreach (new \RecursiveIteratorIterator($message) as $part) {
                        $this->importPartDataToEmail($email, $part, $inlineIds);
                    }
                } else {
                    $this->importPartDataToEmail($email, $message, $inlineIds, 'text/plain');
                }

                if (!$email->get('body') && $email->get('bodyPlain')) {
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

                if ($this->getFiltersMatcher()->match($email, $filterList)) {
                    return false;
                }
            } else {
                $email->set('body', '(Not fetched)');
                $email->set('isHtml', false);
            }

            $parentFound = false;

            $replied = null;
            if (isset($message->inReplyTo) && !empty($message->inReplyTo)) {
                $arr = explode(' ', $message->inReplyTo);
                $inReplyTo = $arr[0];
                $replied = $this->getEntityManager()->getRepository('Email')->where(array(
                    'messageId' => $inReplyTo
                ))->findOne();
                if ($replied) {
                    $email->set('repliedId', $replied->id);
                }
            }

            if (isset($message->references) && !empty($message->references)) {
                $arr = explode(' ', $message->references);
                $reference = $arr[0];
                $reference = str_replace(array('/', '@'), " ", trim($reference, '<>'));
                $parentType = $parentId = null;
                $emailSent = PHP_INT_MAX;
                $number = null;
                $n = sscanf($reference, '%s %s %d %d espo', $parentType, $parentId, $emailSent, $number);
                if ($n == 4 && $emailSent < time()) {
                    if (!empty($parentType) && !empty($parentId)) {
                        if ($parentType == 'Lead') {
                            $parent = $this->getEntityManager()->getEntity('Lead', $parentId);
                            if ($parent && $parent->get('status') == 'Converted') {
                                if ($parent->get('createdAccountId')) {
                                    $account = $this->getEntityManager()->getEntity('Account', $parent->get('createdAccountId'));
                                    if ($account) {
                                        $parentType = 'Account';
                                        $parentId = $account->id;
                                    }
                                } else {
                                    if ($this->getConfig()->get('b2cMode')) {
                                        if ($parent->get('createdContactId')) {
                                            $contact = $this->getEntityManager()->getEntity('Contact', $parent->get('createdContactId'));
                                            if ($contact) {
                                                $parentType = 'Contact';
                                                $parentId = $contact->id;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                        $email->set('parentType', $parentType);
                        $email->set('parentId', $parentId);
                        $parentFound = true;
                    }
                }
            }

            if (!$parentFound) {
                if ($replied && $replied->get('parentId') && $replied->get('parentType')) {
                    $parentFound = $this->getEntityManager()->getEntity($replied->get('parentType'), $replied->get('parentId'));
                    if ($parentFound) {
                        $email->set('parentType', $replied->get('parentType'));
                        $email->set('parentId', $replied->get('parentId'));
                    }
                }
            }
            if (!$parentFound) {
                $from = $email->get('from');
                if ($from) {
                    $parentFound = $this->findParent($email, $from);
                }
            }
            if (!$parentFound) {
                if (!empty($replyToArr)) {
                    $parentFound = $this->findParent($email, $replyToArr[0]);
                }
            }
            if (!$parentFound) {
                if (!empty($toArr)) {
                    $parentFound = $this->findParent($email, $toArr[0]);
                }
            }

            $this->getEntityManager()->saveEntity($email);

            return $email;

        } catch (\Exception $e) {}
    }

    protected function findParent(Entity $email, $emailAddress)
    {
        $contact = $this->getEntityManager()->getRepository('Contact')->where(array(
            'emailAddress' => $emailAddress
        ))->findOne();
        if ($contact) {
            if (!$this->getConfig()->get('b2cMode')) {
                if ($contact->get('accountId')) {
                    $email->set('parentType', 'Account');
                    $email->set('parentId', $contact->get('accountId'));
                    return true;
                }
            } else {
                $email->set('parentType', 'Contact');
                $email->set('parentId', $contact->id);
                return true;
            }
        } else {
            $account = $this->getEntityManager()->getRepository('Account')->where(array(
                'emailAddress' => $emailAddress
            ))->findOne();
            if ($account) {
                $email->set('parentType', 'Account');
                $email->set('parentId', $account->id);
                return true;
            } else {
                $lead = $this->getEntityManager()->getRepository('Lead')->where(array(
                    'emailAddress' => $emailAddress
                ))->findOne();
                if ($lead) {
                    $email->set('parentType', 'Lead');
                    $email->set('parentId', $lead->id);
                    return true;
                }
            }
        }
    }

    protected function findDuplicate(Entity $email)
    {
        if ($email->get('messageId')) {
            $duplicate = $this->getEntityManager()->getRepository('Email')->where(array(
                'messageId' => $email->get('messageId')
            ))->findOne();
            if ($duplicate) {
                return $duplicate;
            }
        }
    }

    protected function normilizeHeader($header)
    {
        if (is_a($header, 'ArrayIterator')) {
            return $header->current();
        } else {
            return $header;
        }
    }

    protected function getAddressListFromMessage($message, $type)
    {
        $addressList = array();
        if (isset($message->$type)) {
            $list = $this->normilizeHeader($message->getHeader($type))->getAddressList();
            foreach ($list as $address) {
                $addressList[] = $address->getEmail();
            }
        }
        return $addressList;
    }

    protected function importPartDataToEmail(\Espo\Entities\Email $email, $part, &$inlineIds = array(), $defaultContentType = null)
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
                        if ($email->get('bodyPlain')) {
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
                } else if ($disposition == 'inline') {
                    $inlineIds[$contentId] = $attachment->id;
                }
            }
        } catch (\Exception $e) {}
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
}
