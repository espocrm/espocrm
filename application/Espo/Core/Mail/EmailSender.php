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

use Espo\Core\Binding\BindingContainerBuilder;
use Espo\Core\InjectableFactory;
use Espo\Core\Mail\Account\SendingAccountProvider;
use Espo\Core\Name\Field;
use Espo\Core\Utils\Config;
use Espo\Entities\Attachment;
use Espo\Entities\Email;

use Laminas\Mail\Message;

/**
 * A service for email sending. Can send with SMTP parameters of the system email account or with specific parameters.
 * Uses a builder to send with specific parameters.
 */
class EmailSender
{
    public function __construct(
        private Config $config,
        private SendingAccountProvider $accountProvider,
        private InjectableFactory $injectableFactory
    ) {}

    private function createSender(): Sender
    {
        return $this->injectableFactory->createWithBinding(
            Sender::class,
            BindingContainerBuilder
                ::create()
                ->bindInstance(SendingAccountProvider::class, $this->accountProvider)
                ->build()
        );
    }

    /**
     * Create a builder.
     */
    public function create(): Sender
    {
        return $this->createSender();
    }

    /**
     * With parameters.
     *
     * @param SenderParams|array<string, mixed> $params
     */
    public function withParams($params): Sender
    {
        return $this->createSender()->withParams($params);
    }

    /**
     * With specific SMTP parameters.
     *
     * @param SmtpParams|array<string, mixed> $params
     */
    public function withSmtpParams($params): Sender
    {
        return $this->createSender()->withSmtpParams($params);
    }

    /**
     * With specific attachments.
     *
     * @param iterable<Attachment> $attachmentList
     * @noinspection PhpUnused
     */
    public function withAttachments(iterable $attachmentList): Sender
    {
        return $this->createSender()->withAttachments($attachmentList);
    }

    /**
     * With an envelope from address.
     *
     * @since 9.1.0
     * @noinspection PhpUnused
     */
    public function withEnvelopeFromAddress(string $fromAddress): void
    {
        $this->createSender()->withEnvelopeFromAddress($fromAddress);
    }

    /**
     * With envelope options.
     *
     * @param array{from: string} $options
     * @deprecated As of v9.1.
     * @todo Remove in v10.0. Use `withEnvelopeFromAddress`.
     */
    public function withEnvelopeOptions(array $options): Sender
    {
        return $this->createSender()->withEnvelopeOptions($options);
    }

    /**
     * Set a message instance.
     *
     * @deprecated As of v9.1. Use `withAddedHeader`.
     *  @todo Remove in v10.0.
     */
    public function withMessage(Message $message): Sender
    {
        return $this->createSender()->withMessage($message);
    }

    /**
     * Add a header.
     *
     * @param string $name A header name.
     * @param string $value A header value.
     * @since 9.1.0
     */
    public function withAddedHeader(string $name, string $value): Sender
    {
        return $this->createSender()->withAddedHeader($name, $value);
    }

    /**
     * Whether system SMTP is configured.
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
     * @throws Exceptions\SendingError
     */
    public function send(Email $email): void
    {
        $this->createSender()->send($email);
    }

    /**
     * Generate a message ID.
     */
    static public function generateMessageId(Email $email): string
    {
        $rand = mt_rand(1000, 9999);

        $messageId = $email->getParentType() && $email->getParentId() ?
            sprintf("%s/%s/%s/%s@espo", $email->getParentType(), $email->getParentId(), time(), $rand) :
            sprintf("%s/%s/%s@espo", md5($email->get(Field::NAME)), time(), $rand);

        if ($email->get('isSystem')) {
            $messageId .= '-system';
        }

        return $messageId;
    }
}
