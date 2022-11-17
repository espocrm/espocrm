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

namespace Espo\Modules\Crm\Tools\Meeting;

use Espo\Core\Acl;
use Espo\Core\Binding\BindingContainerBuilder;
use Espo\Core\Exceptions\Error;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\NotFound;
use Espo\Core\InjectableFactory;
use Espo\Core\Mail\Exceptions\SendingError;
use Espo\Core\Mail\SmtpParams;
use Espo\Core\Record\ServiceContainer as RecordServiceContainer;
use Espo\Core\Utils\Config;
use Espo\Entities\User;
use Espo\Modules\Crm\Business\Event\Invitations;
use Espo\Modules\Crm\Entities\Contact;
use Espo\Modules\Crm\Entities\Lead;
use Espo\Modules\Crm\Entities\Meeting;
use Espo\ORM\Collection;
use Espo\ORM\Entity;
use Espo\ORM\EntityManager;
use Espo\Tools\Email\SendService;


class InvitationService
{
    private RecordServiceContainer $recordServiceContainer;
    private SendService $sendService;
    private User $user;
    private InjectableFactory $injectableFactory;
    private Acl $acl;
    private EntityManager $entityManager;
    private Config $config;

    public function __construct(
        RecordServiceContainer $recordServiceContainer,
        SendService $sendService,
        User $user,
        InjectableFactory $injectableFactory,
        Acl $acl,
        EntityManager $entityManager,
        Config $config
    ) {
        $this->recordServiceContainer = $recordServiceContainer;
        $this->sendService = $sendService;
        $this->user = $user;
        $this->injectableFactory = $injectableFactory;
        $this->acl = $acl;
        $this->entityManager = $entityManager;
        $this->config = $config;
    }

    /**
     * Send invitations for a meeting (or call). Checks access. Uses user's SMTP if available.
     *
     * @return Entity[] Entities an invitation was sent to.
     * @throws NotFound
     * @throws Forbidden
     * @throws Error
     * @throws SendingError
     */
    public function send(string $entityType, string $id): array
    {
        $entity = $this->recordServiceContainer
            ->get($entityType)
            ->getEntity($id);

        if (!$entity) {
            throw new NotFound();
        }

        if (!$this->acl->checkEntityEdit($entity)) {
            throw new Forbidden("No edit access.");
        }

        $sender = $this->getSender();

        $sentAddressList = [];
        $resultEntityList = [];

        foreach ($this->getUsers($entity) as $user) {
            $emailAddress = $user->getEmailAddress();

            if ($emailAddress) {
                $sender->sendInvitation($entity, $user, 'users');

                $sentAddressList[] = $emailAddress;
                $resultEntityList[] = $user;
            }
        }

        /** @var Collection<Contact> $contacts */
        $contacts = $this->entityManager
            ->getRDBRepository($entity->getEntityType())
            ->getRelation($entity, 'contacts')
            ->find();

        /** @var Collection<Lead> $leads */
        $leads = $this->entityManager
            ->getRDBRepository($entity->getEntityType())
            ->getRelation($entity, 'leads')
            ->find();

        foreach ($contacts as $contact) {
            $emailAddress = $contact->getEmailAddress();

            if ($emailAddress && !in_array($emailAddress, $sentAddressList)) {
                $sender->sendInvitation($entity, $contact, 'contacts');

                $sentAddressList[] = $emailAddress;
                $resultEntityList[] = $contact;
            }
        }

        foreach ($leads as $lead) {
            $emailAddress = $lead->getEmailAddress();

            if ($emailAddress && !in_array($emailAddress, $sentAddressList)) {
                $sender->sendInvitation($entity, $lead, 'leads');

                $sentAddressList[] = $emailAddress;
                $resultEntityList[] = $lead;
            }
        }

        return $resultEntityList;
    }

    /**
     * @return Collection<User>
     */
    private function getUsers(Entity $entity): Collection
    {
        /** @var Collection<User> */
        return $this->entityManager
            ->getRDBRepository($entity->getEntityType())
            ->getRelation($entity, 'users')
            ->where([
                'OR' => [
                    [
                        'id=' => $this->user->getId(),
                        '@relation.status!=' => Meeting::ATTENDEE_STATUS_ACCEPTED,
                    ],
                    [
                        'id!=' => $this->user->getId(),
                    ]
                ]
            ])
            ->find();
    }

    private function getSender(): Invitations
    {
        $smtpParams = !$this->config->get('eventInvitationForceSystemSmtp') ?
            $this->sendService->getUserSmtpParams($this->user->getId()) :
            null;

        $builder = BindingContainerBuilder::create();

        if ($smtpParams) {
            $builder->bindInstance(SmtpParams::class, $smtpParams);
        }

        return $this->injectableFactory->createWithBinding(Invitations::class, $builder->build());
    }
}
