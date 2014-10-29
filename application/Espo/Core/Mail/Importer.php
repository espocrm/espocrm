<?php

namespace Espo\Core\Mail;

use Espo\Core\ORM\EntityManager;
use Espo\Core\Utils\File\Manager;
use Espo\Entities\Attachment;
use Espo\Entities\Email;
use Zend\Mail\Header\AbstractAddressList;
use Zend\Mail\Header\ContentTransferEncoding;
use Zend\Mail\Header\ContentType;
use Zend\Mail\Storage\Message;
use Zend\Mail\Storage\Part;

class Importer
{

    private $entityManager;

    private $fileManager;

    public function __construct($entityManager, $fileManager)
    {
        $this->entityManager = $entityManager;
        $this->fileManager = $fileManager;
    }

    /**
     * @param Message $message
     * @param int     $userId
     * @param array   $teamsIds
     *
     * @return Email

     */
    public function importMessage($message, $userId, $teamsIds = array())
    {
        /**
         * @var Email $email
         */
        try{
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
            $this->getEntityManager()->saveEntity($email);
            return $email;
        } catch(\Exception $e){
        }
    }

    /**
     * @return EntityManager

     */
    protected function getEntityManager()
    {
        return $this->entityManager;
    }

    /**
     * @param Message $message
     * @param         $type
     *
     * @return array

     */
    protected function getAddressListFromMessage($message, $type)
    {
        /**
         * @var AbstractAddressList $header
         */
        $addressList = array();
        if (isset($message->$type)) {
            $header = $message->getHeader($type);
            $list = $header->getAddressList();
            foreach ($list as $address) {
                $addressList[] = $address->getEmail();
            }
        }
        return $addressList;
    }

    /**
     * @param Email $email
     *
     * @return bool

     */
    protected function checkIsDuplicate($email)
    {
        /**
         * @var \Espo\Repositories\Email $emailRepo
         */
        if ($email->get('messageIdInternal')) {
            $emailRepo = $this->getEntityManager()->getRepository('Email');
            $duplicate = $emailRepo->where(array(
                'messageIdInternal' => $email->get('messageIdInternal')
            ))->findOne();
            if ($duplicate) {
                return true;
            }
        }
    }

    /**
     * @param Email $email
     * @param Part  $part
     * @param array $inlineIds
     *

     */
    protected function importPartDataToEmail(Email $email, $part, &$inlineIds = array())
    {
        /**
         * @var Attachment              $attachment
         * @var ContentTransferEncoding $encodingHeader
         */
        try{
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
                        $encodingHeader = $part->getHeader('Content-Transfer-Encoding');
                        $encoding = strtolower($encodingHeader->getTransferEncoding());
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
        } catch(\Exception $e){
        }
    }

    /**
     * @param \Zend\Mime\Part|Part $part
     *
     * @return string

     */
    protected function getContentFromPart($part)
    {
        /**
         * @var ContentTransferEncoding $cteHeader
         * @var ContentType $ctHeader
         */
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

    /**
     * @return Manager

     */
    protected function getFileManager()
    {
        return $this->fileManager;
    }
}
