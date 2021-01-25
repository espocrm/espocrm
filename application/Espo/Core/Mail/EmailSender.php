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

use Espo\Entities\Email;

use Laminas\Mail\Message;

use Espo\Core\{
    Exceptions\Error,
    Utils\Config,
    ORM\EntityManager,
    ServiceFactory,
};

use Traversable;

/**
 * An util for email sending. Can send with SMTP parameters of the system email account or with specific parameters.
 * Uses a builder to send with specific parameters.
 */
class EmailSender
{
    private $systemInboundEmail = null;

    private $inboundEmailService = null;

    private $systemInboundEmailIsCached = false;

    protected $config;
    protected $entityManager;
    protected $serviceFactory;
    protected $transportFactory;

    public function __construct(
        Config $config,
        EntityManager $entityManager,
        ServiceFactory $serviceFactory,
        SmtpTransportFactory $transportFactory
    ) {
        $this->config = $config;
        $this->entityManager = $entityManager;
        $this->serviceFactory = $serviceFactory;
        $this->transportFactory = $transportFactory;
    }

    protected function createSender() : Sender
    {
        return new Sender(
            $this->config,
            $this->entityManager,
            $this->serviceFactory,
            $this->transportFactory,
            $this->getInboundEmailService(),
            $this->getSystemInboundEmail()
        );
    }

    /**
     * Create a builder.
     */
    public function create() : Sender
    {
        return $this->createSender();
    }

    /**
     * With parameters.
     *
     * Available parameters: fromAddress, fromName, replyToAddress, replyToName.
     */
    public function withParams(array $params = []) : Sender
    {
        return $this->createSender()->withParams($params);
    }

    /**
     * With specific SMTP parameters.
     */
    public function withSmtpParams(array $params = []) : Sender
    {
        return $this->createSender()->withSmtpParams($params);
    }

    /**
     * With specific attachments.
     */
    public function withAttachments(iterable $attachmentList) : Sender
    {
        return $this->createSender()->withAttachments($attachmentList);
    }

    /**
     * With envelope options.
     */
    public function withEnvelopeOptions(array $options) : Sender
    {
        return $this->createSender()->withEnvelopeOptions($options);
    }

    /**
     * Set a message instance.
     */
    public function withMessage(Message $message) : Sender
    {
        return $this->createSender()->message($message);
    }

    /**
     * Whether system STMP is configured.
     */
    public function hasSystemSmtp() : bool
    {
        if ($this->config->get('smtpServer')) {
            return true;
        }

        if ($this->getSystemInboundEmail()) {
            return true;
        }

        return false;
    }

    protected function getSystemInboundEmail()
    {
        $address = $this->config->get('outboundEmailFromAddress');

        if (!$this->systemInboundEmailIsCached && $address) {
            $this->systemInboundEmail = $this->entityManager
                ->getRepository('InboundEmail')
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

    protected function getInboundEmailService()
    {
        if (!$this->serviceFactory) return null;

        if (!$this->inboundEmailService) {
            $this->inboundEmailService = $this->serviceFactory->create('InboundEmail');
        }

        return $this->inboundEmailService;
    }

    /**
     * Send an email.
     */
    public function send(Email $email)
    {
        $this->createSender()->send($email);
    }

    /**
     * Generate a message ID.
     */
    static public function generateMessageId(Email $email) : string
    {
        return Sender::generateMessageId($email);
    }
}
