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

namespace Espo\Core\Mail;

use Espo\Core\FileStorage\Manager as FileStorageManager;
use Espo\Core\Mail\Exceptions\NoSmtp;
use Espo\Core\Mail\Sender\MessageContainer;
use Espo\Core\Mail\Sender\TransportPreparatorFactory;
use Espo\Core\ORM\Repository\Option\SaveOption;
use Espo\ORM\EntityCollection;
use Espo\Core\Field\DateTime;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\Log;
use Espo\Core\Mail\Account\SendingAccountProvider;
use Espo\Core\Mail\Exceptions\SendingError;
use Espo\Entities\Attachment;
use Espo\Entities\Email;
use Espo\ORM\EntityManager;

use Laminas\Mail\Headers;
use Laminas\Mail\Message as LaminasMessage;

use RuntimeException;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email as Message;
use Symfony\Component\Mime\Part\DataPart;

use Exception;
use LogicException;
use InvalidArgumentException;

/**
 * Sends emails. Builds parameters for sending. Should not be used directly.
 */
class Sender
{
    private ?TransportInterface $transport = null;
    private bool $isGlobal = false;
    /** @var array<string, mixed> */
    private array $params = [];
    /** @var array<string, mixed> */
    private array $overrideParams = [];
    private ?string $envelopeFromAddress = null;
    private ?LaminasMessage $laminasMessage = null;
    /** @var ?iterable<Attachment> */
    private $attachmentList = null;
    /** @var array{string, string}[] */
    private array $headers = [];
    private ?MessageContainer $messageContainer = null;

    private const ATTACHMENT_ATTR_CONTENTS = 'contents';

    public function __construct(
        private Config $config,
        private EntityManager $entityManager,
        private Log $log,
        private SendingAccountProvider $accountProvider,
        private FileStorageManager $fileStorageManager,
        private ConfigDataProvider $configDataProvider,
        private TransportPreparatorFactory $transportPreparatorFactory,
    ) {
        $this->useGlobal();
    }

    private function resetParams(): void
    {
        $this->params = [];
        $this->envelopeFromAddress = null;
        $this->laminasMessage = null;
        $this->attachmentList = null;
        $this->overrideParams = [];
        $this->headers = [];
        $this->messageContainer = null;
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
        } else if (!is_array($params)) {
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
        } else if (!is_array($params)) {
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
     * With an envelope from address.
     *
     * @since 9.1.0
     */
    public function withEnvelopeFromAddress(string $fromAddress): void
    {
        $this->envelopeFromAddress = $fromAddress;
    }

    /**
     * With envelope options.
     *
     * @param array{from: string} $options
     * @deprecated As of v9.1.
     * @todo Remove in v10.0. Use `withEnvelopeFromAddress`.
     */
    public function withEnvelopeOptions(array $options): self
    {
        /** @noinspection PhpDeprecationInspection */
        return $this->setEnvelopeOptions($options);
    }

    /**
     * @since 9.2.0
     * @internal
     */
    public function withMessageContainer(MessageContainer $messageContainer): self
    {
        $this->messageContainer = $messageContainer;

        return $this;
    }

    /**
     * Set a message instance.
     *
     * @deprecated As of v9.1. Use `withAddedHeader`.
     * @todo Remove in v10.0.
     */
    public function withMessage(LaminasMessage $message): self
    {
        $this->laminasMessage = $message;

        return $this;
    }

    /**
     * Add a header.
     *
     * @param string $name A header name.
     * @param string $value A header value.
     * @since 9.1.0
     */
    public function withAddedHeader(string $name, string $value): self
    {
        $this->headers[] = [$name, $value];

        return $this;
    }

    /**
     * @deprecated As of v6.0. Use withParams.
     * @param array<string, mixed> $params
     * @todo Remove in v10.0.
     */
    public function setParams(array $params = []): self
    {
        $this->params = array_merge($this->params, $params);

        return $this;
    }

    /**
     * @deprecated As of 6.0. Use withSmtpParams.
     * @param array<string, mixed> $params
     * @todo Make private in v10.0.
     */
    public function useSmtp(array $params = []): self
    {
        $this->isGlobal = false;

        $this->applySmtp($params);

        return $this;
    }

    private function useGlobal(): void
    {
        $this->params = [];
        $this->isGlobal = true;
    }

    /**
     * @param array<string, mixed> $params
     */
    private function applySmtp(array $params = []): void
    {
        $this->params = $params;

        $smtpParams = SmtpParams::fromArray($params);

        $preparator = $this->transportPreparatorFactory->create($smtpParams);

        $this->transport = $preparator->prepare($smtpParams);
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
     * Send an email.
     *
     * @throws SendingError
     */
    public function send(Email $email): void
    {
        if ($this->isGlobal) {
            $this->applyGlobal();
        }

        $message = new Message();

        $params = array_merge($this->params, $this->overrideParams);

        $this->applyHeaders($message);
        $this->applyFrom($email, $message, $params);
        $this->addRecipientAddresses($email, $message);
        $this->applyReplyTo($email, $message, $params);
        $this->applySubject($email, $message);
        $this->applyBody($email, $message);
        $this->applyMessageId($email, $message);

        $this->applyLaminasMessageHeaders($message);

        if (!$this->transport) {
            throw new LogicException();
        }

        $envelope = $this->prepareEnvelope($message);

        if ($this->messageContainer) {
            $this->messageContainer->message = new Sender\Message($message);
        }

        try {
            $this->transport->send($message, $envelope);
        } catch (Exception|TransportExceptionInterface $e) {
            $this->resetParams();
            $this->useGlobal();

            $this->handleException($e);
        }

        $email
            ->setStatus(Email::STATUS_SENT)
            ->setDateSent(DateTime::createNow())
            ->setSendAt(null);

        $this->resetParams();
        $this->useGlobal();
    }

    /**
     * @return DataPart[]
     */
    private function getAttachmentParts(Email $email): array
    {
        /** @var EntityCollection<Attachment> $collection */
        $collection = $this->entityManager
            ->getCollectionFactory()
            ->create(Attachment::ENTITY_TYPE);

        if (!$email->isNew()) {
            foreach ($email->getAttachments() as $attachment) {
                $collection[] = $attachment;
            }
        }

        if ($this->attachmentList !== null) {
            foreach ($this->attachmentList as $attachment) {
                $collection[] = $attachment;
            }
        }

        $list = [];

        foreach ($collection as $attachment) {
            $contents = $attachment->has(self::ATTACHMENT_ATTR_CONTENTS) ?
                $attachment->get(self::ATTACHMENT_ATTR_CONTENTS) :
                $this->fileStorageManager->getContents($attachment);

            $part = new DataPart(
                body: $contents,
                filename: $attachment->getName() ?? '',
                contentType: $attachment->getType(),
            );

            $list[] = $part;
        }

        return $list;
    }

    /**
     * @return DataPart[]
     */
    private function getInlineAttachmentParts(Email $email): array
    {
        $list = [];

        foreach ($email->getInlineAttachmentList() as $attachment) {
            $contents = $attachment->has(self::ATTACHMENT_ATTR_CONTENTS) ?
                $attachment->get(self::ATTACHMENT_ATTR_CONTENTS) :
                $this->fileStorageManager->getContents($attachment);

            $part = (new DataPart($contents, null, $attachment->getType()))
                ->asInline()
                ->setContentId($attachment->getId() . '@espo');

            $list[] = $part;
        }

        return $list;
    }

    /**
     * @throws SendingError
     */
    private function handleException(Exception|TransportExceptionInterface $e): never
    {
        if ($e instanceof TransportExceptionInterface) {
            $message = "unknownError";

            if (
                stripos($e->getMessage(), 'password') !== false ||
                stripos($e->getMessage(), 'credentials') !== false ||
                stripos($e->getMessage(), '5.7.8') !== false ||
                stripos($e->getMessage(), '5.7.3') !== false
            ) {
                $message = 'invalidCredentials';
            }

            $this->log->error("Email sending error: " . $e->getMessage(), ['exception' => $e]);

            throw new SendingError($message);
        }

        throw new SendingError($e->getMessage());
    }

    /**
     * @deprecated Since v9.1.0. Use EmailSender::generateMessageId.
     * @noinspection PhpUnused
     * @todo Remove in v10.0.
     */
    static public function generateMessageId(Email $email): string
    {
        return EmailSender::generateMessageId($email);
    }

    /**
     * @deprecated As of v6.0.
     *
     * @param array{from: string} $options
     * @todo Make private in v10.0. Use `withEnvelopeFromAddress`.
     */
    public function setEnvelopeOptions(array $options): self
    {
        $this->envelopeFromAddress = $options['from'];

        return $this;
    }

    private function addRecipientAddresses(Email $email, Message $message): void
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

    /**
     * @param array<string, mixed> $params
     * @throws NoSmtp
     */
    private function applyFrom(Email $email, Message $message, array $params): void
    {
        $fromName = $params['fromName'] ?? $this->config->get('outboundEmailFromName');

        $fromAddress = $email->get('from');

        if ($fromAddress) {
            $fromAddress = trim($fromAddress);
        } else {
            if (
                empty($params['fromAddress']) &&
                !$this->configDataProvider->getSystemOutboundAddress()
            ) {
                throw new NoSmtp('outboundEmailFromAddress is not specified in config.');
            }

            $fromAddress = $params['fromAddress'] ?? $this->configDataProvider->getSystemOutboundAddress();

            $email->setFromAddress($fromAddress);
        }

        $message->addFrom(new Address($fromAddress, $fromName ?? ''));

        $fromString = '<' . $fromAddress . '>';

        if ($fromName) {
            $fromString = $fromName . ' ' . $fromString;
        }

        $email->set('fromString', $fromString);

        $message->sender($fromAddress);
    }

    /**
     * @param array<string, mixed> $params
     */
    private function applyReplyTo(Email $email, Message $message, array $params): void
    {
        $address = $params['replyToAddress'] ?? null;
        $name = $params['replyToName'] ?? null;

        if (!$address) {
            return;
        }

        $message->replyTo(new Address($address, $name ?? ''));

        $email->setReplyToAddressList([$address]);
    }

    private function applyMessageId(Email $email, Message $message): void
    {
        $messageId = $email->getMessageId();

        if (
            !$messageId ||
            strlen($messageId) < 4 ||
            str_starts_with($messageId, 'dummy:')
        ) {
            $messageId = EmailSender::generateMessageId($email);

            $email->setMessageId('<' . $messageId . '>');

            if ($email->hasId()) {
                $this->entityManager->saveEntity($email, [SaveOption::SILENT => true]);
            }
        } else {
            $messageId = substr($messageId, 1, strlen($messageId) - 2);
        }

        $message->getHeaders()->addIdHeader('Message-ID', $messageId);
    }

    private function applyBody(Email $email, Message $message): void
    {
        $message->text($email->getBodyPlainForSending());

        if ($email->isHtml()) {
            $message->html($email->getBodyForSending());
        }

        foreach ($this->getAttachmentParts($email) as $part) {
            $message->addPart($part);
        }

        foreach ($this->getInlineAttachmentParts($email) as $part) {
            $message->addPart($part);
        }
    }

    private function applySubject(Email $email, Message $message): void
    {
        $message->subject($email->getSubject() ?? '');
    }

    private function applyHeaders(Message $message): void
    {
        foreach ($this->headers as $item) {
            $message->getHeaders()->addTextHeader($item[0], $item[1]);
        }

        if ($this->laminasMessage) {
            // For bc.
            foreach ($this->laminasMessage->getHeaders() as $it) {
                if ($it->getFieldName() === 'Date') {
                    continue;
                }

                $message->getHeaders()->addTextHeader($it->getFieldName(), $it->getFieldValue());
            }
        }
    }

    private function prepareEnvelope(Message $message): ?Envelope
    {
        if (!$this->envelopeFromAddress) {
            return null;
        }

        $recipients = [
            ...$message->getTo(),
            ...$message->getCc(),
            ...$message->getBcc(),
        ];

        return new Envelope(new Address($this->envelopeFromAddress), $recipients);
    }

    private function applyLaminasMessageHeaders(Message $message): void
    {
        if (!$this->laminasMessage) {
            return;
        }

        $parts = preg_split("/\R\R/", $message->toString(), 2);

        if (!is_array($parts) || count($parts) < 2) {
            throw new RuntimeException("Could not split email.");
        }

        /** @noinspection PhpMultipleClassDeclarationsInspection */
        $this->laminasMessage
            ->setHeaders(
                Headers::fromString($parts[0])
            )
            ->setBody($parts[1]);
    }
}
