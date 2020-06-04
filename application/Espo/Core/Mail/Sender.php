<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2020 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

namespace Espo\Core\Mail;

use Espo\Entities\Email;

use Laminas\Mime\Message as MimeMessage;
use Laminas\Mime\Part as MimePart;
use Laminas\Mime\Mime as Mime;

use Laminas\Mail\Message;
use Laminas\Mail\Transport\Smtp as SmtpTransport;
use Laminas\Mail\Transport\SmtpOptions;
use Laminas\Mail\Transport\Envelope;

use Espo\Core\Exceptions\Error;

class Sender
{
    protected $config;

    protected $entityManager;

    protected $serviceFactory;

    protected $transport;

    protected $isGlobal = false;

    protected $params = [];

    private $systemInboundEmail = null;

    private $inboundEmailService = null;

    private $systemInboundEmailIsCached = false;

    private $envelope = null;

    public function __construct($config, $entityManager, $serviceFactory = null)
    {
        $this->config = $config;
        $this->entityManager = $entityManager;
        $this->serviceFactory = $serviceFactory;

        $this->useGlobal();
    }

    protected function getConfig()
    {
        return $this->config;
    }

    protected function getEntityManager()
    {
        return $this->entityManager;
    }

    public function resetParams() : self
    {
        $this->params = [];
        $this->envelope = null;
        return $this;
    }

    public function setParams(array $params = []) : self
    {
        $this->params = array_merge($this->params, $params);
        return $this;
    }

    public function useSmtp(array $params = []) : self
    {
        $this->isGlobal = false;
        $this->applySmtp($params);
        return $this;
    }

    public function useGlobal()
    {
        $this->params = [];
        $this->isGlobal = true;
        return $this;
    }

    protected function applySmtp(array $params = [])
    {
        $this->params = $params;

        $this->transport = new SmtpTransport();

        $config = $this->config;

        $localHostName = $config->get('smtpLocalHostName', gethostname());

        $options = [
            'name' => $localHostName,
            'host' => $params['server'],
            'port' => $params['port'],
            'connectionConfig' => [],
        ];

        $connectionOptions = $params['connectionOptions'] ?? [];
        foreach ($connectionOptions as $key => $value) {
            $options['connectionConfig'][$key] = $value;
        }

        if ($params['auth'] ?? false) {
            $authMechanism = $params['authMechanism'] ?? $params['smtpAuthMechanism'] ?? null;
            if ($authMechanism) {
                $authMechanism = preg_replace("([\.]{2,})", '', $authMechanism);
                if (in_array($authMechanism, ['login', 'crammd5', 'plain'])) {
                    $options['connectionClass'] = $authMechanism;
                } else {
                    $options['connectionClass'] = 'login';
                }
            } else {
                $options['connectionClass'] = 'login';
            }
            $options['connectionConfig']['username'] = $params['username'];
            $options['connectionConfig']['password'] = $params['password'];
        }

        $authClassName = $params['authClassName'] ?? $params['smtpAuthClassName'] ?? null;
        if ($authClassName) {
            $options['connectionClass'] = $authClassName;
        }

        if ($params['security'] ?? null) {
            $options['connectionConfig']['ssl'] = strtolower($params['security']);
        }

        if (array_key_exists('fromName', $params)) {
            $this->params['fromName'] = $params['fromName'];
        }
        if (array_key_exists('fromAddress', $params)) {
            $this->params['fromAddress'] = $params['fromAddress'];
        }

        $smtpOptions = new SmtpOptions($options);
        $this->transport->setOptions($smtpOptions);

        if ($this->envelope) {
            $this->transport->setEnvelope($this->envelope);
        }
    }

    protected function applyGlobal()
    {
        $config = $this->config;

        if (!$config->get('smtpServer') && $config->get('outboundEmailFromAddress')) {
            $inboundEmail = $this->getSystemInboundEmail();
            if ($inboundEmail) {
                $service = $this->getInboundEmailService();
                if ($service) {
                    $params = $service->getSmtpParamsFromAccount($inboundEmail);
                    $this->applySmtp($params);
                    return;
                }
            }
        }

        $this->applySmtp([
            'server' => $config->get('smtpServer'),
            'port' => $config->get('smtpPort'),
            'auth' => $config->get('smtpAuth'),
            'authMechanism' => $config->get('smtpAuthMechanism', 'login'),
            'username' => $config->get('smtpUsername'),
            'password' => $config->get('smtpPassword'),
            'security' => $config->get('smtpSecurity'),
        ]);
    }

    public function hasSystemSmtp()
    {
        if ($this->config->get('smtpServer')) return true;
        if ($this->getSystemInboundEmail()) return true;

        return false;
    }

    protected function getSystemInboundEmail()
    {
        $address = $this->config->get('outboundEmailFromAddress');

        if (!$this->systemInboundEmailIsCached && $address) {
            $this->systemInboundEmail = $this->getEntityManager()->getRepository('InboundEmail')->where([
                'status' => 'Active',
                'useSmtp' => true,
                'emailAddress' => $address,
            ])->findOne();
            $this->systemInboundEmailIsCached = true;
        }

        return $this->systemInboundEmail;
    }

    protected function getInboundEmailService()
    {
        if (!$this->serviceFactory) return null;

        if (!$this->inboundEmailService) {
            $this->inboundEmailService = $this->serviceFactory->create('InboundEmail');
        }

        return $this->inboundEmailService;
    }

    public function send(Email $email, ?array $params = [], $message = null, $attachmentList = [])
    {
        if ($this->isGlobal) {
            $this->applyGlobal();
        }

        if (!$message) {
            $message = new Message();
        }
        $params = $params ?? [];

        $config = $this->config;
        $params = $params + $this->params;

        if ($email->get('from')) {
            $fromName = null;
            if (!empty($params['fromName'])) {
                $fromName = $params['fromName'];
            } else {
                $fromName = $config->get('outboundEmailFromName');
            }

            $message->addFrom(trim($email->get('from')), $fromName);

            $fromAddress = trim($email->get('from'));
        } else {
            if (!empty($params['fromAddress'])) {
                $fromAddress = $params['fromAddress'];
            } else {
                if (!$config->get('outboundEmailFromAddress')) {
                    throw new Error('outboundEmailFromAddress is not specified in config.');
                }
                $fromAddress = $config->get('outboundEmailFromAddress');
            }

            if (!empty($params['fromName'])) {
                $fromName = $params['fromName'];
            } else {
                $fromName = $config->get('outboundEmailFromName');
            }

            $message->addFrom($fromAddress, $fromName);

            $email->set('from', $fromAddress);
        }

        $fromString = '<' . $fromAddress . '>';
        if ($fromName) {
            $fromString = $fromName . ' ' . $fromString;
        }
        $email->set('fromString', $fromString);

        $sender = new \Laminas\Mail\Header\Sender();
        $sender->setAddress($email->get('from'));
        $message->getHeaders()->addHeader($sender);

        if (!empty($params['replyToAddress'])) {
            $replyToName = null;
            if (!empty($params['replyToName'])) {
                $replyToName = $params['replyToName'];
            }
            $message->setReplyTo($params['replyToAddress'], $replyToName);
        }

        $value = $email->get('to');
        if ($value) {
            $arr = explode(';', $value);
            if (is_array($arr)) {
                foreach ($arr as $address) {
                    $message->addTo(trim($address));
                }
            }
        }

        $value = $email->get('cc');
        if ($value) {
            $arr = explode(';', $value);
            if (is_array($arr)) {
                foreach ($arr as $address) {
                    $message->addCC(trim($address));
                }
            }
        }

        $value = $email->get('bcc');
        if ($value) {
            $arr = explode(';', $value);
            if (is_array($arr)) {
                foreach ($arr as $address) {
                    $message->addBCC(trim($address));
                }
            }
        }

        $value = $email->get('replyTo');
        if ($value) {
            $arr = explode(';', $value);
            if (is_array($arr)) {
                foreach ($arr as $address) {
                    $message->addReplyTo(trim($address));
                }
            }
        }

        $attachmentPartList = array();
        $attachmentCollection = $email->get('attachments');
        $attachmentInlineCollection = $email->getInlineAttachments();

        foreach ($attachmentList as $attachment) {
            $attachmentCollection[] = $attachment;
        }

        if (!empty($attachmentCollection)) {
            foreach ($attachmentCollection as $a) {
                if ($a->get('contents')) {
                    $contents = $a->get('contents');
                } else {
                    $fileName = $this->getEntityManager()->getRepository('Attachment')->getFilePath($a);
                    if (!is_file($fileName)) continue;
                    $contents = file_get_contents($fileName);
                }
                $attachment = new MimePart($contents);
                $attachment->disposition = Mime::DISPOSITION_ATTACHMENT;
                $attachment->encoding = Mime::ENCODING_BASE64;
                $attachment->filename ='=?utf-8?B?' . base64_encode($a->get('name')) . '?=';
                if ($a->get('type')) {
                    $attachment->type = $a->get('type');
                }
                $attachmentPartList[] = $attachment;
            }
        }

        if (!empty($attachmentInlineCollection)) {
            foreach ($attachmentInlineCollection as $a) {
                if ($a->get('contents')) {
                    $contents = $a->get('contents');
                } else {
                    $fileName = $this->getEntityManager()->getRepository('Attachment')->getFilePath($a);
                    if (!is_file($fileName)) continue;
                    $contents = file_get_contents($fileName);
                }
                $attachment = new MimePart($contents);
                $attachment->disposition = Mime::DISPOSITION_INLINE;
                $attachment->encoding = Mime::ENCODING_BASE64;
                $attachment->id = $a->id;
                if ($a->get('type')) {
                    $attachment->type = $a->get('type');
                }
                $attachmentPartList[] = $attachment;
            }
        }


        $message->setSubject($email->get('name'));

        $body = new MimeMessage();

        $textPart = new MimePart($email->getBodyPlainForSending());
        $textPart->type = 'text/plain';
        $textPart->encoding = Mime::ENCODING_QUOTEDPRINTABLE;
        $textPart->charset = 'utf-8';

        if ($email->get('isHtml')) {
            $htmlPart = new MimePart($email->getBodyForSending());
            $htmlPart->encoding = Mime::ENCODING_QUOTEDPRINTABLE;
            $htmlPart->type = 'text/html';
            $htmlPart->charset = 'utf-8';
        }

        if (!empty($attachmentPartList)) {
            $messageType = 'multipart/related';
            if ($email->get('isHtml')) {
                $content = new MimeMessage();
                $content->addPart($textPart);
                $content->addPart($htmlPart);

                $messageType = 'multipart/mixed';

                $contentPart = new MimePart($content->generateMessage());
                $contentPart->type = "multipart/alternative;\n boundary=\"" . $content->getMime()->boundary() . '"';

                $body->addPart($contentPart);
            } else {
                $body->addPart($textPart);
            }

            foreach ($attachmentPartList as $attachmentPart) {
                $body->addPart($attachmentPart);
            }

        } else {
            if ($email->get('isHtml')) {
                $body->setParts([$textPart, $htmlPart]);
                $messageType = 'multipart/alternative';
            } else {
                $body = $email->getBodyPlainForSending();
                $messageType = 'text/plain';
            }
        }

        $message->setBody($body);

        if ($messageType == 'text/plain') {
            if ($message->getHeaders()->has('content-type')) {
                $message->getHeaders()->removeHeader('content-type');
            }
            $message->getHeaders()->addHeaderLine('Content-Type', 'text/plain; charset=UTF-8');
        } else {
            if (!$message->getHeaders()->has('content-type')) {
                $contentTypeHeader = new \Laminas\Mail\Header\ContentType();
                $message->getHeaders()->addHeader($contentTypeHeader);
            }
            $message->getHeaders()->get('content-type')->setType($messageType);
        }

        $message->setEncoding('UTF-8');

        try {
            $messageId = $email->get('messageId');
            if (empty($messageId) || !is_string($messageId) || strlen($messageId) < 4 || strpos($messageId, 'dummy:') === 0) {
                $messageId = $this->generateMessageId($email);
                $email->set('messageId', '<' . $messageId . '>');
                if ($email->id) {
                    $this->getEntityManager()->saveEntity($email, ['silent' => true]);
                }
            } else {
                $messageId = substr($messageId, 1, strlen($messageId) - 2);
            }

            $messageIdHeader = new \Laminas\Mail\Header\MessageId();
            $messageIdHeader->setId($messageId);
            $message->getHeaders()->addHeader($messageIdHeader);

            $this->transport->send($message);

            $email->set('status', 'Sent');
            $email->set('dateSent', date("Y-m-d H:i:s"));
        } catch (\Exception $e) {
            $this->resetParams();
            $this->useGlobal();
            throw new Error($e->getMessage(), 500);
        }

        $this->resetParams();
        $this->useGlobal();
    }

    static public function generateMessageId(Email $email)
    {
        $rand = mt_rand(1000, 9999);

        if ($email->get('parentType') && $email->get('parentId')) {
            $messageId = '' . $email->get('parentType') .'/' . $email->get('parentId') . '/' . time() . '/' . $rand . '@espo';
        } else {
            $messageId = '' . md5($email->get('name')) . '/' . time() . '/' . $rand .  '@espo';
        }
        if ($email->get('isSystem')) {
            $messageId .= '-system';
        }

        return $messageId;
    }

    public function setEnvelopeOptions(array $options) : self
    {
        $this->envelope = new Envelope($options);

        return $this;
    }
}
