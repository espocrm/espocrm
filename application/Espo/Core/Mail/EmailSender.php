<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2021 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

use Espo\{
    Entities\Email,
    Entities\Attachment,
    Entities\InboundEmail,
    Services\InboundEmail as InboundEmailService,
};

use Laminas\Mail\Message;

use Espo\Core\{
    Utils\Config,
    ORM\EntityManager,
    ServiceFactory,
    Utils\Log,
};

/**
 * A service for email sending. Can send with SMTP parameters of the system email account or with specific parameters.
 * Uses a builder to send with specific parameters.
 */
class EmailSender
{
    private $systemInboundEmail = null;

    private $inboundEmailService = null;

    private $systemInboundEmailIsCached = false;

    private $config;

    private $entityManager;

    private $serviceFactory;

    private $transportFactory;

    private $log;

    public function __construct(
        Config $config,
        EntityManager $entityManager,
        ServiceFactory $serviceFactory,
        SmtpTransportFactory $transportFactory,
        Log $log
    ) {
        $this->config = $config;
        $this->entityManager = $entityManager;
        $this->serviceFactory = $serviceFactory;
        $this->transportFactory = $transportFactory;
        $this->log = $log;
    }

    private function createSender(): Sender
    {
        return new Sender(
            $this->config,
            $this->entityManager,
            $this->serviceFactory,
            $this->log,
            $this->transportFactory,
            $this->getInboundEmailService(),
            $this->getSystemInboundEmail()
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
     * @param SenderParams|array $params
     */
    public function withParams($params): Sender
    {
        return $this->createSender()->withParams($params);
    }

    /**
     * With specific SMTP parameters.
     *
     * @param SmtpParams|array $params
     */
    public function withSmtpParams($params): Sender
    {
        return $this->createSender()->withSmtpParams($params);
    }

    /**
     * With specific attachments.
     *
     * @param Attachment[] $attachmentList
     */
    public function withAttachments(iterable $attachmentList): Sender
    {
        return $this->createSender()->withAttachments($attachmentList);
    }

    /**
     * With envelope options.
     */
    public function withEnvelopeOptions(array $options): Sender
    {
        return $this->createSender()->withEnvelopeOptions($options);
    }

    /**
     * Set a message instance.
     */
    public function withMessage(Message $message): Sender
    {
        return $this->createSender()->withMessage($message);
    }

    /**
     * Whether system SMTP is configured.
     */
    public function hasSystemSmtp(): bool
    {
        if ($this->config->get('smtpServer')) {
            return true;
        }

        if ($this->getSystemInboundEmail()) {
            return true;
        }

        return false;
    }

    private function getSystemInboundEmail(): ?InboundEmail
    {
        $address = $this->config->get('outboundEmailFromAddress');

        if (!$this->systemInboundEmailIsCached && $address) {
            $this->systemInboundEmail = $this->entityManager
                ->getRDBRepository('InboundEmail')
                ->where([
                    'status' => 'Active',
                    'useSmtp' => true,
                    'emailAddress' => $address,
                ])
                ->findOne();
        }

        $this->systemInboundEmailIsCached = true;

        return $this->systemInboundEmail;
    }

    private function getInboundEmailService(): InboundEmailService
    {
        if (!$this->inboundEmailService) {
            $this->inboundEmailService = $this->serviceFactory->create('InboundEmail');
        }

        return $this->inboundEmailService;
    }

    /**
     * Send an email.
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
        return Sender::generateMessageId($email);
    }
}
