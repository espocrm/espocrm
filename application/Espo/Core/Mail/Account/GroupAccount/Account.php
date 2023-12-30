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

namespace Espo\Core\Mail\Account\GroupAccount;

use Espo\Core\Field\Date;
use Espo\Core\Field\Link;
use Espo\Core\Field\LinkMultiple;
use Espo\Core\Mail\Exceptions\NoSmtp;
use Espo\Core\Mail\Account\ImapParams;
use Espo\Core\Mail\Smtp\HandlerProcessor;
use Espo\Core\Mail\SmtpParams;
use Espo\Core\ORM\Repository\Option\SaveOption;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\Crypt;
use Espo\Entities\InboundEmail;
use Espo\Entities\User;
use Espo\Entities\Email;
use Espo\ORM\EntityManager;
use Espo\Core\Mail\Account\Account as AccountInterface;
use Espo\Core\Mail\Account\FetchData;

use RuntimeException;

class Account implements AccountInterface
{
    private const PORTION_LIMIT = 20;

    public function __construct(
        private InboundEmail $entity,
        private EntityManager $entityManager,
        private Config $config,
        private HandlerProcessor $handlerProcessor,
        private Crypt $crypt
    ) {}

    public function updateFetchData(FetchData $fetchData): void
    {
        $this->entity->set('fetchData', $fetchData->getRaw());

        $this->entityManager->saveEntity($this->entity, [SaveOption::SILENT => true]);
    }

    public function relateEmail(Email $email): void
    {
        $this->entityManager
            ->getRDBRepository(InboundEmail::ENTITY_TYPE)
            ->getRelation($this->entity, 'emails')
            ->relate($email);
    }

    public function getEntity(): InboundEmail
    {
        return $this->entity;
    }

    public function getPortionLimit(): int
    {
        return $this->config->get('inboundEmailMaxPortionSize', self::PORTION_LIMIT);
    }

    public function isAvailableForFetching(): bool
    {
        return $this->entity->isAvailableForFetching();
    }

    public function getEmailAddress(): ?string
    {
        return $this->entity->getEmailAddress();
    }

    public function getUsers(): LinkMultiple
    {
        if (!$this->entity->addAllTeamUsers()) {
            return LinkMultiple::create();
        }

        if (!$this->entity->getTeams()->getCount()) {
            return LinkMultiple::create();
        }

        $userList = $this->entityManager
            ->getRDBRepository(User::ENTITY_TYPE)
            ->select(['id'])
            ->distinct()
            ->join('teams')
            ->where([
                'isActive' => true,
                'teamsMiddle.teamId' => $this->entity->getTeams()->getIdList(),
            ])
            ->find();

        $linkMultiple = LinkMultiple::create();

        foreach ($userList as $user) {
            $linkMultiple = $linkMultiple->withAddedId($user->getId());
        }

        return $linkMultiple;
    }

    public function getUser(): ?Link
    {
        return null;
    }

    public function getAssignedUser(): ?Link
    {
        return $this->entity->getAssignToUser();
    }

    public function getTeams(): LinkMultiple
    {
        $value = $this->entity->getTeams();

        if ($this->entity->getTeam()) {
            $value = $value->withAddedId($this->entity->getTeam()->getId());
        }

        return $value;
    }

    public function keepFetchedEmailsUnread(): bool
    {
        return $this->entity->keepFetchedEmailsUnread();
    }

    public function getFetchData(): FetchData
    {
        return FetchData::fromRaw(
            $this->entity->getFetchData()
        );
    }

    public function getFetchSince(): ?Date
    {
        return $this->entity->getFetchSince();
    }

    public function getEmailFolder(): ?Link
    {
        return $this->entity->getEmailFolder();
    }

    /**
     * @return string[]
     */
    public function getMonitoredFolderList(): array
    {
        return $this->entity->getMonitoredFolderList();
    }

    public function getId(): ?string
    {
        return $this->entity->getId();
    }

    public function getEntityType(): string
    {
        return $this->entity->getEntityType();
    }

    /**
     * @return ?class-string<object>
     */
    public function getImapHandlerClassName(): ?string
    {
        return $this->entity->getImapHandlerClassName();
    }

    public function createCase(): bool
    {
        return $this->entity->createCase();
    }

    public function autoReply(): bool
    {
        return $this->entity->autoReply();
    }

    public function getSentFolder(): ?string
    {
        return $this->entity->getSentFolder();
    }

    public function getGroupEmailFolder(): ?Link
    {
        return $this->entity->getGroupEmailFolder();
    }

    public function isAvailableForSending(): bool
    {
        return $this->entity->isAvailableForSending();
    }

    /**
     * @throws NoSmtp
     */
    public function getSmtpParams(): ?SmtpParams
    {
        $host = $this->entity->getSmtpHost();

        if (!$host) {
            return null;
        }

        $port = $this->entity->getSmtpPort();

        if ($port === null) {
            throw new NoSmtp("Empty port.");
        }

        $smtpParams = SmtpParams::create($host, $port)
            ->withSecurity($this->entity->getSmtpSecurity())
            ->withAuth($this->entity->getSmtpAuth());

        if ($this->entity->getSmtpAuth()) {
            $password = $this->entity->getSmtpPassword();

            if ($password !== null) {
                $password = $this->crypt->decrypt($password);
            }

            $smtpParams = $smtpParams
                ->withUsername($this->entity->getSmtpUsername())
                ->withPassword($password)
                ->withAuthMechanism($this->entity->getSmtpAuthMechanism());
        }

        if ($this->entity->getFromName()) {
            $smtpParams = $smtpParams->withFromName($this->entity->getFromName());
        }

        $handlerClassName = $this->entity->getSmtpHandlerClassName();

        if (!$handlerClassName) {
            return $smtpParams;
        }

        return $this->handlerProcessor->handle($handlerClassName, $smtpParams, $this->getId());
    }

    public function storeSentEmails(): bool
    {
        return $this->entity->storeSentEmails();
    }

    public function getImapParams(): ?ImapParams
    {
        $host = $this->entity->getHost();
        $port = $this->entity->getPort();
        $username = $this->entity->getUsername();
        $password = $this->entity->getPassword();
        $security = $this->entity->getSecurity();

        if (!$host) {
            return null;
        }

        if ($port === null) {
            throw new RuntimeException("No port.");
        }

        if ($username === null) {
            throw new RuntimeException("No username.");
        }

        if ($password !== null) {
            $password = $this->crypt->decrypt($password);
        }

        return new ImapParams(
            $host,
            $port,
            $username,
            $password,
            $security
        );
    }
}
