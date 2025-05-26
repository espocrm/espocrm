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

namespace Espo\Tools\Email;

use Espo\Core\Acl;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Error;
use Espo\Core\Exceptions\ErrorSilent;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\NotFound;
use Espo\Core\FieldValidation\FieldValidationManager;
use Espo\Core\Mail\Account\Account;
use Espo\Core\Mail\Account\GroupAccount\Account as GroupAccount;
use Espo\Core\Mail\Account\GroupAccount\AccountFactory as GroupAccountFactory;
use Espo\Core\Mail\Account\GroupAccount\Service as GroupAccountService;
use Espo\Core\Mail\Account\PersonalAccount\Account as PersonalAccount;
use Espo\Core\Mail\Account\PersonalAccount\AccountFactory as PersonalAccountFactory;
use Espo\Core\Mail\Account\PersonalAccount\Service as PersonalAccountService;
use Espo\Core\Mail\Account\SendingAccountProvider;
use Espo\Core\Mail\ConfigDataProvider;
use Espo\Core\Mail\EmailSender;
use Espo\Core\Mail\Exceptions\NoSmtp;
use Espo\Core\Mail\Exceptions\SendingError;
use Espo\Core\Mail\Sender;
use Espo\Core\Mail\SenderParams;
use Espo\Core\Mail\Smtp\HandlerProcessor;
use Espo\Core\Mail\SmtpParams;
use Espo\Core\ORM\Repository\Option\SaveOption;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\Json;
use Espo\Core\Utils\Log;
use Espo\Entities\Email;
use Espo\Entities\EmailAccount;
use Espo\Entities\EmailAddress;
use Espo\Entities\InboundEmail;
use Espo\Entities\User;
use Espo\Modules\Crm\Entities\CaseObj;
use Espo\ORM\Collection;
use Espo\ORM\Entity;
use Espo\ORM\EntityManager;
use Espo\Tools\Stream\Service as StreamService;
use Exception;
use Laminas\Mail\Message;
use LogicException;

use const FILTER_VALIDATE_EMAIL;

/**
 * Email sending service.
 */
class SendService
{
    /** @var string[] */
    private array $notAllowedStatusList = [
        Email::STATUS_ARCHIVED,
        Email::STATUS_SENT,
        Email::STATUS_BEING_IMPORTED,
    ];

    public function __construct(
        private User $user,
        private EntityManager $entityManager,
        private FieldValidationManager $fieldValidationManager,
        private EmailSender $emailSender,
        private StreamService $streamService,
        private Config $config,
        private Log $log,
        private Acl $acl,
        private SendingAccountProvider $accountProvider,
        private PersonalAccountService $personalAccountService,
        private GroupAccountService $groupAccountService,
        private HandlerProcessor $handlerProcessor,
        private PersonalAccountFactory $personalAccountFactory,
        private GroupAccountFactory $groupAccountFactory,
        private ConfigDataProvider $configDataProvider,
    ) {}

    /**
     * Send an email entity.
     *
     * @params Email $entity An email entity.
     * @params ?User $user A user from what to send.
     *
     * @throws BadRequest If not valid.
     * @throws SendingError On error while sending.
     * @throws NoSmtp No SMTP settings.
     * @throws Error An error.
     */
    public function send(Email $entity, ?User $user = null): void
    {
        if (in_array($entity->getStatus(), $this->notAllowedStatusList)) {
            throw new Error("Can't send email with status `{$entity->getStatus()}`.");
        }

        if (!$this->fieldValidationManager->check($entity, 'to', 'required')) {
            $entity->setStatus(Email::STATUS_DRAFT);

            $this->entityManager->saveEntity($entity, [SaveOption::SILENT => true]);

            throw new BadRequest("Empty To address.");
        }

        $systemIsShared = $this->config->get('outboundEmailIsShared');
        $systemFromName = $this->config->get('outboundEmailFromName');
        $systemFromAddress = $this->configDataProvider->getSystemOutboundAddress();

        $sender = $this->emailSender->create();

        $userAddressList = [];

        if ($user) {
            // @todo Use getEmailAddressGroup.
            /** @var Collection<EmailAddress> $emailAddressCollection */
            $emailAddressCollection = $this->entityManager
                ->getRelation($user, 'emailAddresses')
                ->find();

            foreach ($emailAddressCollection as $ea) {
                $userAddressList[] = $ea->getLower();
            }
        }

        $originalFromAddress = $entity->getFromAddress();

        if (!$originalFromAddress) {
            throw new Error("Email sending: Can't send with empty 'from' address.");
        }

        $fromAddress = strtolower($originalFromAddress);

        $isUserAddress = in_array($fromAddress, $userAddressList);
        $isSystemAddress = $fromAddress === strtolower($systemFromAddress ?? '');

        $smtpParams = null;
        $personalAccount = null;
        $groupAccount = null;
        $params = SenderParams::create();

        if ($user && $isUserAddress) {
            [$smtpParams, $personalAccount] = $this->getPersonalAccount($user, $originalFromAddress);
        }

        if ($user && $smtpParams) {
            $sender->withSmtpParams($smtpParams);
        }

        if (!$smtpParams) {
            [$smtpParams, $groupAccount] = $this->getGroupAccount($user, $originalFromAddress);

            if ($smtpParams) {
                $sender->withSmtpParams($smtpParams);
            }
        }

        if (!$smtpParams && $isSystemAddress) {
            $params = $params->withFromName($systemFromName);
        }

        // Otherwise, allow users to send from the system SMTP with their own from-address.
        if (!$smtpParams && !$systemIsShared) {
            if ($isSystemAddress) {
                throw new NoSmtp("Can not use system SMTP. System SMTP is not shared.");
            }

            throw new NoSmtp("No SMTP params for $fromAddress.");
        }

        if (
            !$smtpParams &&
            $user &&
            in_array($fromAddress, $userAddressList)
        ) {
            $params = $params->withFromName($user->getName());
        }

        $parent = null;
        $parentId = $entity->getParentId();
        $parentType = $entity->getParentType();

        if ($parentType && $parentId) {
            $parent = $this->entityManager->getEntityById($parentType, $parentId);

            $params = $this->applyParent($parent, $params);
        }

        $this->validateEmailAddresses($entity);

        $message = new Message();

        if (
            $groupAccount instanceof GroupAccount && $groupAccount->storeSentEmails() ||
            $personalAccount instanceof PersonalAccount && $personalAccount->storeSentEmails()
        ) {
            $sender->withMessage($message);
        }

        $this->applyReplied($entity, $sender);

        $sender->withParams($params);

        try {
            $sender->send($entity);
        } catch (Exception $e) {
            $entity->setStatus(Email::STATUS_DRAFT);

            $this->entityManager->saveEntity($entity, [SaveOption::SILENT => true]);

            $this->log->error("Email sending: " . $e->getMessage(), ['exception' => $e]);

            $errorData = [
                'id' => $entity->getId(),
                'message' => $e->getMessage(),
            ];

            throw ErrorSilent::createWithBody('sendingFail', Json::encode($errorData));
        }

        if ($groupAccount) {
            $groupAccountId = $groupAccount->getId();

            if ($groupAccountId) {
                $entity->addLinkMultipleId('inboundEmails', $groupAccountId);
            }
        }

        if ($personalAccount) {
            $personalAccountId = $personalAccount->getId();

            if ($personalAccountId) {
                $entity->addLinkMultipleId('emailAccounts', $personalAccountId);
            }
        }

        $this->entityManager->saveEntity($entity, [Email::SAVE_OPTION_IS_JUST_SENT => true]);

        $this->store($message, $groupAccount, $personalAccount);

        if ($parent) {
            $this->streamService->noteEmailSent($parent, $entity);
        }
    }

    private function applyParent(?Entity $parent, SenderParams $params): SenderParams
    {
        // @todo Refactor. Move to a separate class? Make extensible?
        if ($parent instanceof CaseObj) {
            $inboundEmailId = $parent->getInboundEmailId();

            if (!$inboundEmailId) {
                return $params;
            }

            $inboundEmail = $this->entityManager
                ->getRDBRepositoryByClass(InboundEmail::class)
                ->getById($inboundEmailId);

            if (!$inboundEmail || !$inboundEmail->getReplyToAddress()) {
                return $params;
            }

            $params = $params->withReplyToAddress($inboundEmail->getReplyToAddress());
        }

        return $params;
    }

    private function store(Message $message, ?Account $groupAccount, ?Account $personalAccount): void
    {
        if ($groupAccount instanceof GroupAccount && $groupAccount->storeSentEmails()) {
            $id = $groupAccount->getId() ?? null;

            if (!$id) {
                throw new LogicException();
            }

            try {
                $this->groupAccountService->storeSentMessage($id, $message);
            } catch (Exception $e) {
                $text = "Could not store sent email; group account {$groupAccount->getId()}); " .
                    $e->getMessage() . ".";

                $this->log->error($text, ['exception' => $e]);
            }
        }

        if ($personalAccount instanceof PersonalAccount && $personalAccount->storeSentEmails()) {
            $id = $personalAccount->getId() ?? null;

            if (!$id) {
                throw new LogicException();
            }

            try {
                $this->personalAccountService->storeSentMessage($id, $message);
            } catch (Exception $e) {
                $text = "Could not store sent email; personal account {$personalAccount->getId()}; " .
                    $e->getMessage() . ".";

                $this->log->error($text, ['exception' => $e]);
            }
        }
    }

    /**
     * @return array{?SmtpParams, ?Account}
     */
    private function getPersonalAccount(User $user, string $emailAddress): array
    {
        $personalAccount = $this->accountProvider->getPersonal($user, $emailAddress);

        if (!$personalAccount) {
            return [null, null];
        }

        if (!$personalAccount->isAvailableForSending()) {
            return [null, null];
        }

        $smtpParams = $personalAccount->getSmtpParams();

        if (!$smtpParams) {
            return [null, null];
        }

        $smtpParams = $smtpParams->withFromName($user->getName());

        return [$smtpParams, $personalAccount];
    }

    /**
     * @return array{?SmtpParams, ?Account}
     */
    private function getGroupAccount(?User $user, string $emailAddress): array
    {
        $groupAccount = $user ?
            $this->accountProvider->getShared($user, $emailAddress) :
            $this->accountProvider->getGroup($emailAddress);

        if (!$groupAccount) {
            return [null, null];
        }

        $smtpParams = $groupAccount->getSmtpParams();

        if (!$smtpParams) {
            return [null, null];
        }

        return [$smtpParams, $groupAccount];
    }

    /**
     * @throws Forbidden
     * @throws Error
     * @throws NotFound
     * @throws NoSmtp
     */
    public function sendTestEmail(SmtpParams $params, TestSendData $data): void
    {
        $emailAddress = $data->getEmailAddress();
        $userId = $data->getUserId();
        $type = $data->getType();
        $id = $data->getId();

        if ($params->getPassword() === null) {
            $params = $params->withPassword(
                $this->obtainSendTestEmailPassword($type, $id)
            );
        }

        if (
            $userId &&
            $userId !== $this->user->getId() &&
            !$this->user->isAdmin()
        ) {
            throw new Forbidden();
        }

        /** @var ?User $user */
        $user = $userId ?
            $this->entityManager->getRDBRepositoryByClass(User::class)->getById($userId) :
            null;

        if ($userId && !$user) {
            throw new NotFound("User not found.");
        }

        /** @var Email $email */
        $email = $this->entityManager->getNewEntity(Email::ENTITY_TYPE);

        $email
            ->setSubject('EspoCRM: Test Email')
            ->setIsHtml(false)
            ->addToAddress($emailAddress);

        $handlerClassName = null;

        if ($type === 'emailAccount' && $id) {
            /** @var ?EmailAccount $emailAccount */
            $emailAccount = $this->entityManager->getEntityById(EmailAccount::ENTITY_TYPE, $id);

            $handlerClassName = $emailAccount?->getSmtpHandlerClassName();
        }

        if ($type === 'inboundEmail' && $id) {
            /** @var ?InboundEmail $inboundEmail */
            $inboundEmail = $this->entityManager->getEntityById(InboundEmail::ENTITY_TYPE, $id);

            if ($inboundEmail) {
                $handlerClassName = $inboundEmail->getSmtpHandlerClassName();
            }
        }

        if ($handlerClassName && $id) {
            $params = $this->handlerProcessor->handle($handlerClassName, $params, $id);
        }

        try {
            $this->emailSender
                ->withSmtpParams($params)
                ->send($email);
        } catch (Exception $e) {
            $this->log->warning("Email sending:" . $e->getMessage() . "; " . $e->getCode());

            if ($e instanceof SendingError) {
                throw ErrorSilent::createWithBody(
                    'sendingFail',
                    Error\Body::create()
                        ->withMessageTranslation($e->getMessage(), Email::ENTITY_TYPE)
                );
            }

            $errorData = ['message' => $e->getMessage()];

            throw ErrorSilent::createWithBody('sendingFail', Json::encode($errorData));
        }
    }

    /**
     * @throws Error
     */
    public function validateEmailAddresses(Email $entity): void
    {
        $from = $entity->getFromAddress();

        if ($from) {
            if (!filter_var($from, FILTER_VALIDATE_EMAIL)) {
                throw new Error('From email address is not valid.');
            }
        }

        foreach ($entity->getToAddressList() as $address) {
            if (!filter_var($address, FILTER_VALIDATE_EMAIL)) {
                throw new Error('To email address is not valid.');
            }
        }

        foreach ($entity->getCcAddressList() as $address) {
            if (!filter_var($address, FILTER_VALIDATE_EMAIL)) {
                throw new Error('CC email address is not valid.');
            }
        }

        foreach ($entity->getBccAddressList() as $address) {
            if (!filter_var($address, FILTER_VALIDATE_EMAIL)) {
                throw new Error('BCC email address is not valid.');
            }
        }
    }

    /**
     * Get a user personal SMTP params.
     */
    public function getUserSmtpParams(string $userId): ?SmtpParams
    {
        /** @var ?User $user */
        $user = $this->entityManager->getRDBRepositoryByClass(User::class)->getById($userId);

        if (!$user) {
            return null;
        }

        $address = $user->getEmailAddress();

        if (!$address) {
            return null;
        }

        $account = $this->accountProvider->getPersonal($user, $address);

        if (!$account) {
            return null;
        }

        $smtpParams = $account->getSmtpParams();

        return $smtpParams
            ?->withFromName($user->getName())
            ->withFromAddress($address);

    }

    /**
     * @throws Forbidden
     * @throws Error
     * @throws NoSmtp
     */
    private function obtainSendTestEmailPassword(?string $type, ?string $id): ?string
    {
        if ($type === 'emailAccount') {
            if (!$this->acl->checkScope(EmailAccount::ENTITY_TYPE)) {
                throw new Forbidden();
            }

            if (!$id) {
                return null;
            }

            $personalAccount = $this->personalAccountFactory->create($id);

            if (
                !$this->user->isAdmin() &&
                $personalAccount->getUser()->getId() !== $this->user->getId()
            ) {
                throw new Forbidden();
            }

            $smtpParams = $personalAccount->getSmtpParams();

            return $smtpParams?->getPassword();
        }

        if (!$this->user->isAdmin()) {
            throw new Forbidden();
        }

        if ($type === 'inboundEmail') {
            if (!$id) {
                return null;
            }

            $smtpParams = $this->groupAccountFactory
                ->create($id)
                ->getSmtpParams();

            return $smtpParams?->getPassword();
        }

        return $this->config->get('smtpPassword');
    }

    private function applyReplied(Email $entity, Sender $sender): void
    {
        $replied = $entity->getReplied();

        if ($replied && $replied->getMessageId()) {
            $sender->withAddedHeader('In-Reply-To', $replied->getMessageId());
            $sender->withAddedHeader('References', $replied->getMessageId());
        }

        if ($replied && $replied->getGroupFolder()) {
            $entity->setGroupFolder($replied->getGroupFolder());
        }
    }
}
