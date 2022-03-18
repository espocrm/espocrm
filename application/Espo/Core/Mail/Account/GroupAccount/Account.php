<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2022 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

namespace Espo\Core\Mail\Account\GroupAccount;

use Espo\Core\Field\Date;
use Espo\Core\Field\Link;
use Espo\Core\Field\LinkMultiple;
use Espo\Core\Utils\Config;

use Espo\Entities\InboundEmail;
use Espo\Entities\User;
use Espo\Entities\Email;

use Espo\ORM\EntityManager;

use Espo\Core\Mail\Account\Account as AccountInterface;
use Espo\Core\Mail\Account\FetchData;

class Account implements AccountInterface
{
    private InboundEmail $entity;

    private EntityManager $entityManager;

    private Config $config;

    private const PORTION_LIMIT = 20;

    public function __construct(InboundEmail $entity, EntityManager $entityManager, Config $config)
    {
        $this->entity = $entity;
        $this->entityManager = $entityManager;
        $this->config = $config;
    }

    public function updateFetchData(FetchData $fetchData): void
    {
        $this->entity->set('fetchData', $fetchData->getRaw());

        $this->entityManager->saveEntity($this->entity, ['silent' => true]);
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

    public function getHost(): ?string
    {
        return $this->entity->getHost();
    }

    public function getPort(): ?int
    {
        return $this->entity->getPort();
    }

    public function getUsername(): ?string
    {
        return $this->entity->getUsername();
    }

    public function getPassword(): ?string
    {
        return $this->entity->getPassword();
    }

    public function getSecurity(): ?string
    {
        return $this->entity->getSecurity();
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
}
