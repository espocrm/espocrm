<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2024 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

namespace Espo\Core\Mail;

use Espo\Core\FileStorage\Manager as FileStorageManager;
use Espo\Core\Mail\Exceptions\NoSmtp;
use Espo\Core\Mail\Smtp\TransportFactory;
use Espo\Core\ORM\Repository\Option\SaveOption;
use Espo\ORM\Collection;
use Espo\ORM\EntityCollection;

use Espo\Core\Field\DateTime;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\Log;
use Espo\Core\Mail\Account\SendingAccountProvider;
use Espo\Core\Mail\Exceptions\SendingError;
use Espo\Entities\Attachment;
use Espo\Entities\Email;
use Espo\ORM\EntityManager;

use Laminas\Mail\Header\ContentType as ContentTypeHeader;
use Laminas\Mail\Header\MessageId as MessageIdHeader;
use Laminas\Mail\Header\Sender as SenderHeader;
use Laminas\Mail\Message;
use Laminas\Mail\Protocol\Exception\RuntimeException as ProtocolRuntimeException;
use Laminas\Mail\Transport\Envelope;
use Laminas\Mail\Transport\Smtp as SmtpTransport;
use Laminas\Mail\Transport\SmtpOptions;
use Laminas\Mime\Message as MimeMessage;
use Laminas\Mime\Mime as Mime;
use Laminas\Mime\Part as MimePart;

use Exception;
use InvalidArgumentException;

/**
 * Sends emails. Builds parameters for sending. Should not be used directly.
 */
class Sender
{
    private ?SmtpTransport $transport = null;
    private bool $isGlobal = false;
    /** @var array<string, mixed>  */
    private array $params = [];
    /** @var array<string, mixed> */
    private array $overrideParams = [];
    private ?Envelope $envelope = null;
    private ?Message $message = null;
    /** @var ?iterable<Attachment> */
    private $attachmentList = null;

    public function __construct(
        private Config $config,
        private EntityManager $entityManager,
        private Log $log,
        private TransportFactory $transportFactory,
        private SendingAccountProvider $accountProvider,
        private FileStorageManager $fileStorageManager
    ) {

        /** @noinspection PhpDeprecationInspection */
        $this->useGlobal();
    }

    /**
     * @deprecated As of 6.0. EmailSender should be used as an access point
     * for email sending functionality. Sender instances are not meant to be reused.
     */
    public function resetParams(): self
    {
        $this->params = [];
        $this->envelope = null;
        $this->message = null;
        $this->attachmentList = null;
        $this->overrideParams = [];

        return $this;
    }

    /**
     * With parameters.
     *
     * @param SenderParams|array<string, mixed> $params
     */
    public function withParams($params): self
    {
        if ($params instanceof SenderParams) {
            $params = $params->toArray();
        }
        else if (!is_array($params)) {
            throw new InvalidArgumentException();
        }

        $paramList = [
            'fromAddress',
            'fromName',
            'replyToAddress',
            'replyToName',
        ];

        foreach (array_keys($params) as $key) {
            if (!in_array($key, $paramList)) {
                unset($params[$key]);
            }
        }

        $this->overrideParams = array_merge($this->overrideParams, $params);

        return $this;
    }

    /**
     * With specific SMTP parameters.
     *
     * @param SmtpParams|array<string, mixed> $params
     */
    public function withSmtpParams($params): self
    {
        if ($params instanceof SmtpParams) {
            $params = $params->toArray();
        }
        else if (!is_array($params)) {
            throw new InvalidArgumentException();
        }

        /** @noinspection PhpDeprecationInspection */
        return $this->useSmtp($params);
    }

    /**
     * With specific attachments.
     *
     * @param iterable<Attachment> $attachmentList
     */
    public function withAttachments(iterable $attachmentList): self
    {
        $this->attachmentList = $attachmentList;

        return $this;
    }

    /**
     * With envelope options.
     *
     * @param array<string, mixed> $options
     */
    public function withEnvelopeOptions(array $options): self
    {
        /** @noinspection PhpDeprecationInspection */
        return $this->setEnvelopeOptions($options);
    }

    /**
     * Set a message instance.
     */
    public function withMessage(Message $message): self
    {
        $this->message = $message;

        return $this;
    }

    /**
     * @deprecated As of v6.0. Use withParams.
     * @param array<string, mixed> $params
     */
    public function setParams(array $params = []): self
    {
        $this->params = array_merge($this->params, $params);

        return $this;
    }


    /**
     * @deprecated As of 6.0. Use withSmtpParams.
     * @param array<string, mixed> $params
     */
    public function useSmtp(array $params = []): self
    {
        $this->isGlobal = false;

        $this->applySmtp($params);

        return $this;
    }

    /**
     * @deprecated As of v6.0. Sender class not meant to be reused. Global params is applied by default.
     * No need to reset it back.
     */
    public function useGlobal(): self
    {
        $this->params = [];

        $this->isGlobal = true;

        return $this;
    }

    /**
     * @param array<string, mixed> $params
     */
    private function applySmtp(array $params = []): void
    {
        $this->params = $params;

        $this->transport = $this->transportFactory->create();

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
                $authMechanism = preg_replace("([.]{2,})", '', $authMechanism);

                /** @noinspection SpellCheckingInspection */
                if (in_array($authMechanism, ['login', 'crammd5', 'plain'])) {
                    $options['connectionClass'] = $authMechanism;
                }
                else {
                    $options['connectionClass'] = 'login';
                }
            }
            else {
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

        $this->transport->setOptions(
            new SmtpOptions($options)
        );

        if ($this->envelope) {
            $this->transport->setEnvelope($this->envelope);
        }
    }

    /**
     * @throws NoSmtp
     */
    private function applyGlobal(): void
    {
        $systemAccount = $this->accountProvider->getSystem();

        if (!$systemAccount) {
            throw new NoSmtp("No system SMTP settings.");
        }

        $smtpParams = $systemAccount->getSmtpParams();

        if (!$smtpParams) {
            throw new NoSmtp("No system SMTP settings.");
        }

        $this->applySmtp($smtpParams->toArray());
    }

    /**
     * @deprecated As of v6.0. Use EmailSender::hasSystemSmtp.
     */
    public function hasSystemSmtp(): bool
    {
        if ($this->config->get('smtpServer')) {
            return true;
        }

        if ($this->accountProvider->getSystem()) {
            return true;
        }

        return false;
    }

    /**
     * Send an email.
     *
     * @param ?array<string, mixed> $params @deprecated As of v6.0. Use withParams.
     * @param ?Message $message @deprecated As of v6.0. Use withMessage.
     * @param iterable<Attachment> $attachmentList @deprecated As of v6.0. Use withAttachments.
     * @throws SendingError
     */
    public function send(
        Email $email,
        ?array $params = [],
        ?Message $message = null,
        iterable $attachmentList = []
    ): void {

        if ($this->isGlobal) {
            $this->applyGlobal();
        }

        $message = $this->message ?? $message ?? new Message();

        $params = $params ?? [];

        $config = $this->config;

        $params = array_merge(
            $this->params,
            $params,
            $this->overrideParams
        );

        $fromName = $params['fromName'] ?? $config->get('outboundEmailFromName');

        $fromAddress = $email->get('from');

        if ($fromAddress) {
            $fromAddress = trim($fromAddress);
        }
        else {
            if (empty($params['fromAddress']) && !$config->get('outboundEmailFromAddress')) {
                throw new NoSmtp('outboundEmailFromAddress is not specified in config.');
            }

            $fromAddress = $params['fromAddress'] ?? $config->get('outboundEmailFromAddress');

            $email->setFromAddress($fromAddress);
        }

        $message->addFrom($fromAddress, $fromName);

        $fromString = '<' . $fromAddress . '>';

        if ($fromName) {
            $fromString = $fromName . ' ' . $fromString;
        }

        $email->set('fromString', $fromString);

        $senderHeader = new SenderHeader();

        $senderHeader->setAddress($fromAddress);

        $message->getHeaders()->addHeader($senderHeader);

        if (!empty($params['replyToAddress'])) {
            $message->setReplyTo(
                $params['replyToAddress'],
                $params['replyToName'] ?? null
            );
        }

        $this->addAddresses($email, $message);

        $attachmentPartList = [];

        /** @var EntityCollection<Attachment> $attachmentCollection */
        $attachmentCollection = $this->entityManager
            ->getCollectionFactory()
            ->create(Attachment::ENTITY_TYPE);

        if (!$email->isNew()) {
            /** @var Collection<Attachment> $relatedAttachmentCollection */
            $relatedAttachmentCollection = $this->entityManager
                ->getRDBRepository(Email::ENTITY_TYPE)
                ->getRelation($email, 'attachments')
                ->find();

            foreach ($relatedAttachmentCollection as $attachment) {
                $attachmentCollection[] = $attachment;
            }
        }

        if ($this->attachmentList !== null) {
            $attachmentList = $this->attachmentList;
        }

        foreach ($attachmentList as $attachment) {
            $attachmentCollection[] = $attachment;
        }

        foreach ($attachmentCollection as $a) {
            $contents = $a->has('contents') ?
                $a->get('contents') :
                $this->fileStorageManager->getContents($a);

            $attachment = new MimePart($contents);

            $attachment->disposition = Mime::DISPOSITION_ATTACHMENT;
            $attachment->encoding = Mime::ENCODING_BASE64;
            $attachment->filename ='=?utf-8?B?' . base64_encode($a->getName() ?? '') . '?=';

            if ($a->getType()) {
                $attachment->type = $a->getType();
            }

            $attachmentPartList[] = $attachment;
        }

        $inlineAttachmentPartList = $this->getInlineAttachmentPartList($email);

        $message->setSubject($email->getSubject() ?? '');

        $body = new MimeMessage();

        $textPart = (new MimePart($email->getBodyPlainForSending()))
            ->setType('text/plain')
            ->setEncoding(Mime::ENCODING_QUOTEDPRINTABLE)
            ->setCharset('utf-8');

        $htmlPart = $email->isHtml() ?
            (new MimePart($email->getBodyForSending()))
                ->setEncoding(Mime::ENCODING_QUOTEDPRINTABLE)
                ->setType('text/html')
                ->setCharset('utf-8') :
            null;

        $messageType = null;

        $hasAttachments = count($attachmentPartList) !== 0;
        $hasInlineAttachments = count($inlineAttachmentPartList) !== 0;

        if ($hasAttachments || $hasInlineAttachments) {
            if ($htmlPart) {
                $messageType = 'multipart/mixed';

                $alternative = (new MimeMessage())
                    ->addPart($textPart)
                    ->addPart($htmlPart);

                $alternativePart = (new MimePart($alternative->generateMessage()))
                    ->setType('multipart/alternative')
                    ->setBoundary($alternative->getMime()->boundary());

                if ($hasInlineAttachments && $hasAttachments) {
                    $related = (new MimeMessage())->addPart($alternativePart);

                    foreach ($inlineAttachmentPartList as $attachmentPart) {
                        $related->addPart($attachmentPart);
                    }

                    $body->addPart(
                        (new MimePart($related->generateMessage()))
                            ->setType('multipart/related')
                            ->setBoundary($related->getMime()->boundary())
                    );
                }

                if ($hasInlineAttachments && !$hasAttachments) {
                    $messageType = 'multipart/related';

                    $body->addPart($alternativePart);

                    foreach ($inlineAttachmentPartList as $attachmentPart) {
                        $body->addPart($attachmentPart);
                    }
                }

                if (!$hasInlineAttachments) {
                    $body->addPart($alternativePart);
                }
            }

            if (!$htmlPart) {
                $messageType = 'multipart/related';

                $body->addPart($textPart);

                foreach ($inlineAttachmentPartList as $attachmentPart) {
                    $body->addPart($attachmentPart);
                }
            }

            foreach ($attachmentPartList as $attachmentPart) {
                $body->addPart($attachmentPart);
            }
        }
        else {
            if ($email->isHtml()) {
                $body->setParts([$textPart, $htmlPart]);

                $messageType = 'multipart/alternative';
            }
            else {
                $body = $email->getBodyPlainForSending();

                $messageType = 'text/plain';
            }
        }

        $message->setBody($body);

        if ($messageType === 'text/plain') {
            if ($message->getHeaders()->has('content-type')) {
                $message->getHeaders()->removeHeader('content-type');
            }

            $message->getHeaders()->addHeaderLine('Content-Type', 'text/plain; charset=UTF-8');
        }
        else {
            if (!$message->getHeaders()->has('content-type')) {
                $contentTypeHeader = new ContentTypeHeader();

                $message->getHeaders()->addHeader($contentTypeHeader);
            }

            /** @phpstan-ignore-next-line */
            $message->getHeaders()->get('content-type')->setType($messageType);
        }

        $message->setEncoding('UTF-8');

        try {
            $messageId = $email->getMessageId();

            if (
                empty($messageId) ||
                !is_string($messageId) ||
                strlen($messageId) < 4 ||
                str_starts_with($messageId, 'dummy:')
            ) {
                $messageId = $this->generateMessageId($email);

                $email->setMessageId('<' . $messageId . '>');

                if ($email->hasId()) {
                    $this->entityManager->saveEntity($email, [SaveOption::SILENT => true]);
                }
            }
            else {
                $messageId = substr($messageId, 1, strlen($messageId) - 2);
            }

            $message->getHeaders()->addHeader(
                (new MessageIdHeader())->setId($messageId)
            );

            assert($this->transport !== null);

            $this->transport->send($message);

            $email->setStatus(Email::STATUS_SENT);
            $email->set('dateSent', DateTime::createNow()->toString());
        }
        catch (Exception $e) {
            /** @noinspection PhpDeprecationInspection */
            $this->resetParams();
            /** @noinspection PhpDeprecationInspection */
            $this->useGlobal();

            $this->handleException($e);
        }

        /** @noinspection PhpDeprecationInspection */
        $this->resetParams();
        /** @noinspection PhpDeprecationInspection */
        $this->useGlobal();
    }

    /**
     * @return MimePart[]
     */
    private function getInlineAttachmentPartList(Email $email): array
    {
        $list = [];

        foreach ($email->getInlineAttachmentList() as $a) {
            $contents = $a->has('contents') ?
                $a->get('contents') :
                $this->fileStorageManager->getContents($a);

            $attachment = new MimePart($contents);

            $attachment->disposition = Mime::DISPOSITION_INLINE;
            $attachment->encoding = Mime::ENCODING_BASE64;
            $attachment->id = $a->getId();

            if ($a->getType()) {
                $attachment->type = $a->getType();
            }

            $list[] = $attachment;
        }

        return $list;
    }

    /**
     * @throws SendingError
     */
    private function handleException(Exception $e): never
    {
        if ($e instanceof ProtocolRuntimeException) {
            $message = "unknownError";

            if (
                stripos($e->getMessage(), 'password') !== false ||
                stripos($e->getMessage(), 'credentials') !== false ||
                stripos($e->getMessage(), '5.7.8') !== false
            ) {
                $message = 'invalidCredentials';
            }

            $this->log->error("Email sending error: " . $e->getMessage());

            throw new SendingError($message);
        }

        throw new SendingError($e->getMessage());
    }

    static public function generateMessageId(Email $email): string
    {
        $rand = mt_rand(1000, 9999);

        $messageId = $email->getParentType() && $email->getParentId() ?
            sprintf("%s/%s/%s/%s@espo", $email->getParentType(), $email->getParentId(), time(), $rand) :
            sprintf("%s/%s/%s@espo", md5($email->get('name')), time(), $rand);

        if ($email->get('isSystem')) {
            $messageId .= '-system';
        }

        return $messageId;
    }

    /**
     * @deprecated As of v6.0. Use withEnvelopeOptions.
     *
     * @param array<string, mixed> $options
     */
    public function setEnvelopeOptions(array $options): self
    {
        $this->envelope = new Envelope($options);

        return $this;
    }

    private function addAddresses(Email $email, Message $message): void
    {
        $value = $email->get('to');

        if ($value) {
            foreach (explode(';', $value) as $address) {
                $message->addTo(trim($address));
            }
        }

        $value = $email->get('cc');

        if ($value) {
            foreach (explode(';', $value) as $address) {
                $message->addCC(trim($address));
            }
        }

        $value = $email->get('bcc');

        if ($value) {
            foreach (explode(';', $value) as $address) {
                $message->addBCC(trim($address));
            }
        }

        $value = $email->get('replyTo');

        if ($value) {
            foreach (explode(';', $value) as $address) {
                $message->addReplyTo(trim($address));
            }
        }
    }
}
