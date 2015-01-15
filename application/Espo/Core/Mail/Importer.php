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
 ************************************************************************/

namespace Espo\Core\Mail;

use \Zend\Mime\Mime as Mime;

class Importer
{
    private $entityManager;
    
    private $fileManager;

    private $config;
    
    public function __construct($entityManager, $fileManager, $config)
    {
        $this->entityManager = $entityManager;
        $this->fileManager = $fileManager;
        $this->config = $config;
    }
    
    protected function getEntityManager()
    {
        return $this->entityManager;
    }
    protected function getConfig()
    {
        return $this->config;
    }
    
    protected function getFileManager()
    {
        return $this->fileManager;
    }
    
    public function importMessage($message, $userId, $teamsIds = array())
    {
        try {
            $email = $this->getEntityManager()->getEntity('Email');
            
            $subject = $message->subject;
            if ($subject !== '0' && empty($subject)) {
                $subject = '--empty--';
            }
            
            $email->set('isHtml', false);
            $email->set('name', $subject);
            $email->set('status', 'Archived');
            $email->set('attachmentsIds', array());
            $email->set('assignedUserId', $userId);
            $email->set('teamsIds', $teamsIds);
            
            $fromArr = $this->getAddressListFromMessage($message, 'from');
            if (isset($message->from)) {
                $email->set('fromName', $message->from);
            }
            $email->set('from', $fromArr[0]);
            $email->set('to', implode(';', $this->getAddressListFromMessage($message, 'to')));
            $email->set('cc', implode(';', $this->getAddressListFromMessage($message, 'cc')));
            
            if (isset($message->messageId) && !empty($message->messageId)) {
                $email->set('messageId', $message->messageId);
                if (isset($message->deliveredTo)) {
                    $email->set('messageIdInternal', $message->messageId . '-' . $message->deliveredTo);
                }
            }
            
            if ($this->checkIsDuplicate($email)) {
                return false;
            }

            if (isset($message->date)) {
                $dt = new \DateTime($message->date);
                if ($dt) {
                    $dateSent = $dt->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');
                    $email->set('dateSent', $dateSent);
                }
            }
            if (isset($message->deliveryDate)) {
                $dt = new \DateTime($message->deliveryDate);
                if ($dt) {
                    $deliveryDate = $dt->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');
                    $email->set('deliveryDate', $deliveryDate);
                }
            }
            
            $inlineIds = array();
    
            if ($message->isMultipart()) {
                foreach (new \RecursiveIteratorIterator($message) as $part) {
                    $this->importPartDataToEmail($email, $part, $inlineIds);
                }
            } else {
                $this->importPartDataToEmail($email, $message, $inlineIds);
            }
            
            $body = $email->get('body');
            if (!empty($body)) {
                foreach ($inlineIds as $cid => $attachmentId) {
                    $body = str_replace('cid:' . $cid, '?entryPoint=attachment&amp;id=' . $attachmentId, $body);
                }
                $email->set('body', $body);
            }

            if (isset($message->references) && !empty($message->references)) {
                $reference = str_replace(array('/', '@'), " ", trim($message->references, '<>'));
                $parentType = $parentId = null;
                $emailSent = PHP_INT_MAX;
                $n = sscanf($reference, '%s %s %d %d espo', $parentType, $parentId, $emailSent, $number);
                if ($n == 4 && $emailSent < time()) {
                    if (!empty($parentType) && !empty($parentId)) {
                        $email->set('parentType', $parentType);
                        $email->set('parentId', $parentId);
                    }
                }
            }

            if (!$email->has('parentId')) {
                $from = $email->get('from');
                if ($from) {
                    $contact = $this->getEntityManager()->getRepository('Contact')->where(array(
                        'emailAddress' => $from
                    ))->findOne();
                    if ($contact) {
                        if (!$this->getConfig()->get('b2cMode')) {
                            if ($contact->get('accountId')) {
                                $email->set('parentType', 'Account');
                                $email->set('parentId', $contact->get('accountId'));
                            }
                        } else {
                            $email->set('parentType', 'Contact');
                            $email->set('parentId', $contact->id);
                        }
                    }
                }
            }

            $this->getEntityManager()->saveEntity($email);
            
            return $email;
                    
        } catch (\Exception $e) {}
    }
    
    protected function checkIsDuplicate($email)
    {
        if ($email->get('messageIdInternal')) {
            $duplicate = $this->getEntityManager()->getRepository('Email')->where(array(
                'messageIdInternal' => $email->get('messageIdInternal')
            ))->findOne();
            if ($duplicate) {
                return true;
            }
        }
    }
    
    protected function getAddressListFromMessage($message, $type)
    {
        $addressList = array();
        if (isset($message->$type)) {
            
            $list = $message->getHeader($type)->getAddressList();
            foreach ($list as $address) {
                $addressList[] = $address->getEmail();
            }
        }
        return $addressList;
    }
    
    protected function importPartDataToEmail(\Espo\Entities\Email $email, $part, &$inlineIds = array())
    {
        try {
            $type = strtok($part->contentType, ';');
            $encoding = null;
            
            switch ($type) {
                case 'text/plain':
                    $content = $this->getContentFromPart($part);
                    if (!$email->get('body')) {
                        $email->set('body', $content);
                    }
                    $email->set('bodyPlain', $content);
                    break;
                case 'text/html':
                    $content = $this->getContentFromPart($part);
                    $email->set('body', $content);
                    $email->set('isHtml', true);
                    break;
                default:
                    $content = $part->getContent();
                    $disposition = null;
                    
                    $fileName = null;
                    $contentId = null;
                            
                    if (isset($part->ContentDisposition)) {
                        if (strpos($part->ContentDisposition, 'attachment') === 0) {
                            if (preg_match('/filename="?([^"]+)"?/i', $part->ContentDisposition, $m)) {
                                $fileName = $m[1];
                                $disposition = 'attachment';
                            }
                        } else if (strpos($part->ContentDisposition, 'inline') === 0) {
                            $contentId = trim($part->contentID, '<>');
                            $fileName = $contentId;
                            $disposition = 'inline';
                        }
                    }
                    
                    if (isset($part->contentTransferEncoding)) {
                        $encoding = strtolower($part->getHeader('Content-Transfer-Encoding')->getTransferEncoding());
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
                    
                    $attachment->set('size', strlen($content));
                            
                    $this->getEntityManager()->saveEntity($attachment);
                                                
                    $path = 'data/upload/' . $attachment->id;
                    $this->getFileManager()->putContents($path, $content);
                    
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
                $cteHeader = $part->getHeader('Content-Transfer-Encoding');
                $encoding = strtolower($cteHeader->getTransferEncoding());
            }
            
            if ($encoding == 'base64') {
                $content = base64_decode($content);
            }
            
            $charset = 'UTF-8';
            
            if (isset($part->contentType)) {
                $ctHeader = $part->getHeader('Content-Type');
                $charsetParamValue = $ctHeader->getParameter('charset');
                if (!empty($charsetParamValue)) {
                    $charset = strtoupper($charsetParamValue);
                }
            }
            
            if ($charset !== 'UTF-8') {
                $content = mb_convert_encoding($content, 'UTF-8', $charset);
            }
            
            if (isset($part->contentTransferEncoding)) {
                $cteHeader = $part->getHeader('Content-Transfer-Encoding');
                if ($cteHeader->getTransferEncoding() == 'quoted-printable') {
                    $content = quoted_printable_decode($content);
                }
            }
        }
        return $content;
    }
}
