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

namespace Espo\Modules\Crm\Services;

use Espo\ORM\Entity;
use Espo\Modules\Crm\Business\Event\Invitations;
use Espo\Services\Record;

use Espo\Services\Email as EmailService;
use Espo\Core\ORM\Entity as CoreEntity;

use Espo\Core\Exceptions\NotFound;
use Espo\Core\Exceptions\BadRequest;

use Espo\Core\Di;

class Meeting extends Record implements
    Di\HookManagerAware
{
    use Di\HookManagerSetter;

    protected $validateRequiredSkipFieldList = [
        'dateEnd',
    ];

    protected $exportSkipFieldList = ['duration'];

    protected $duplicateIgnoreAttributeList = ['usersColumns', 'contactsColumns', 'leadsColumns'];

    public function checkAssignment(Entity $entity): bool
    {
        $result = parent::checkAssignment($entity);

        if (!$result) {
            return false;
        }

        $userIdList = $entity->get('usersIds');

        if (!is_array($userIdList)) {
            $userIdList = [];
        }

        $newIdList = [];

        if (!$entity->isNew()) {
            $existingIdList = [];

            $usersCollection = $this->getEntityManager()
                ->getRDBRepository($entity->getEntityType())
                ->getRelation($entity, 'users')
                ->select('id')
                ->find();

            foreach ($usersCollection as $user) {
                $existingIdList[] = $user->getId();
            }

            foreach ($userIdList as $id) {
                if (!in_array($id, $existingIdList)) {
                    $newIdList[] = $id;
                }
            }
        }
        else {
            $newIdList = $userIdList;
        }

        foreach ($newIdList as $userId) {
            if (!$this->getAcl()->checkAssignmentPermission($userId)) {
                return false;
            }
        }

        return true;
    }

    protected function getInvitationManager(bool $useUserSmtp = true)
    {
        $smtpParams = null;

        if ($useUserSmtp) {
            $smtpParams = $this->getEmailService()->getUserSmtpParams($this->user->getId());
        }

        return $this->injectableFactory->createWith(Invitations::class, [
            'smtpParams' => $smtpParams,
        ]);
    }

    public function sendInvitations(CoreEntity $entity, bool $useUserSmtp = true)
    {
        $invitationManager = $this->getInvitationManager($useUserSmtp);

        $emailHash = [];

        $sentCount = 0;

        $users = $this->getEntityManager()
            ->getRDBRepository($entity->getEntityType())
            ->getRelation($entity, 'users')
            ->find();

        foreach ($users as $user) {
            if (
                $user->getId() === $this->getUser()->getId() &&
                $entity->getLinkMultipleColumn('users', 'status', $user->getId()) === 'Accepted'
            ) {
                continue;
            }

            if ($user->get('emailAddress') && !array_key_exists($user->get('emailAddress'), $emailHash)) {
                $invitationManager->sendInvitation($entity, $user, 'users');

                $emailHash[$user->get('emailAddress')] = true;

                $sentCount ++;
            }
        }

        $contacts = $this->getEntityManager()
            ->getRDBRepository($entity->getEntityType())
            ->getRelation($entity, 'contacts')
            ->find();

        foreach ($contacts as $contact) {
            if (
                $contact->get('emailAddress') &&
                !array_key_exists($contact->get('emailAddress'), $emailHash)
            ) {
                $invitationManager->sendInvitation($entity, $contact, 'contacts');

                $emailHash[$contact->get('emailAddress')] = true;

                $sentCount ++;
            }
        }

        $leads = $this->getEntityManager()
            ->getRDBRepository($entity->getEntityType())
            ->getRelation($entity, 'leads')
            ->find();

        foreach ($leads as $lead) {
            if (
                $lead->get('emailAddress') &&
                !array_key_exists($lead->get('emailAddress'), $emailHash)
            ) {
                $invitationManager->sendInvitation($entity, $lead, 'leads');

                $emailHash[$lead->get('emailAddress')] = true;

                $sentCount ++;
            }
        }

        if (!$sentCount) {
            return false;
        }

        return true;
    }

    public function massSetHeld(array $ids)
    {
        foreach ($ids as $id) {
            $entity = $this->getEntityManager()->getEntity($this->entityType, $id);

            if ($entity && $this->getAcl()->check($entity, 'edit')) {
                $entity->set('status', 'Held');
                $this->getEntityManager()->saveEntity($entity);
            }
        }

        return true;
    }

    public function massSetNotHeld(array $ids)
    {
        foreach ($ids as $id) {
            $entity = $this->getEntityManager()->getEntity($this->entityType, $id);

            if ($entity && $this->getAcl()->check($entity, 'edit')) {
                $entity->set('status', 'Not Held');

                $this->getEntityManager()->saveEntity($entity);
            }
        }

        return true;
    }

    public function setAcceptanceStatus(string $id, string $status, ?string $userId = null)
    {
        $userId = $userId ?? $this->getUser()->getId();

        $statusList = $this->getMetadata()
                ->get(['entityDefs', $this->entityType, 'fields', 'acceptanceStatus', 'options'], []);

        if (!in_array($status, $statusList)) {
            throw new BadRequest();
        }

        $entity = $this->getEntityManager()->getEntity($this->entityType, $id);

        if (!$entity) {
            throw new NotFound();
        }

        assert($entity instanceof CoreEntity);

        if (!$entity->hasLinkMultipleId('users', $userId)) {
            return;
        }

        $this->getEntityManager()
            ->getRDBRepository($this->entityType)
            ->updateRelation(
                $entity,
                'users',
                $userId,
                (object) ['status' => $status]
            );

        $actionData = [
            'eventName' => $entity->get('name'),
            'eventType' => $entity->getEntityType(),
            'eventId' => $entity->getId(),
            'dateStart' => $entity->get('dateStart'),
            'status' => $status,
            'link' => 'users',
            'inviteeType' => 'User',
            'inviteeId' => $userId,
        ];

        $this->hookManager->process($this->entityType, 'afterConfirmation', $entity, [], $actionData);

        return true;
    }

    private function getEmailService(): EmailService
    {
        return $this->injectableFactory->create(EmailService::class);
    }
}
