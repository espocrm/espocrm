<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

use \Espo\ORM\Entity;
use \Espo\Modules\Crm\Business\Event\Invitations;

use \Espo\Core\Exceptions\Error;
use \Espo\Core\Exceptions\Forbidden;
use \Espo\Core\Exceptions\BadRequest;

class Meeting extends \Espo\Services\Record
{
    protected $validateRequiredSkipFieldList = [
        'dateEnd'
    ];

    protected function init()
    {
        $this->addDependencyList([
            'preferences',
            'language',
            'dateTime',
            'container',
            'fileManager',
            'number'
        ]);
    }

    protected $exportSkipFieldList = ['duration'];

    protected $duplicateIgnoreAttributeList = ['usersColumns', 'contactsColumns', 'leadsColumns'];

    protected function getMailSender()
    {
        return $this->getInjection('container')->get('mailSender');
    }

    protected function getPreferences()
    {
        return $this->getInjection('preferences');
    }

    protected function getLanguage()
    {
        return $this->getInjection('language');
    }

    protected function getDateTime()
    {
        return $this->getInjection('dateTime');
    }

    public function checkAssignment(Entity $entity)
    {
        $result = parent::checkAssignment($entity);
        if (!$result) return false;

        $userIdList = $entity->get('usersIds');
        if (!is_array($userIdList)) {
            $userIdList = [];
        }

        $newIdList = [];
        if (!$entity->isNew()) {
            $existingIdList = [];
            foreach ($entity->get('users') as $user) {
                $existingIdList[] = $user->id;
            }
            foreach ($userIdList as $id) {
                if (!in_array($id, $existingIdList)) {
                    $newIdList[] = $id;
                }
            }
        } else {
            $newIdList = $userIdList;
        }

        foreach ($newIdList as $userId) {
            if (!$this->getAcl()->checkAssignmentPermission($userId)) {
                return false;
            }
        }

        return true;
    }

    protected function getInvitationManager($useUserSmtp = true)
    {
        $smtpParams = null;
        if ($useUserSmtp) {
            $smtpParams = $this->getServiceFactory()->create('Email')->getUserSmtpParams($this->getUser()->id);
        }

        $templateFileManager = $this->getInjection('container')->get('templateFileManager');

        return new Invitations(
            $this->getEntityManager(),
            $smtpParams,
            $this->getMailSender(),
            $this->getConfig(),
            $this->getInjection('fileManager'),
            $this->getDateTime(),
            $this->getInjection('number'),
            $this->getLanguage(),
            $templateFileManager
        );
    }

    public function sendInvitations(Entity $entity, $useUserSmtp = true)
    {
        $invitationManager = $this->getInvitationManager($useUserSmtp);

        $emailHash = array();

        $sentCount = 0;

        $users = $entity->get('users');
        foreach ($users as $user) {
            if ($user->id === $this->getUser()->id) {
                if ($entity->getLinkMultipleColumn('users', 'status', $user->id) === 'Accepted') {
                    continue;
                }
            }
            if ($user->get('emailAddress') && !array_key_exists($user->get('emailAddress'), $emailHash)) {
                $invitationManager->sendInvitation($entity, $user, 'users');
                $emailHash[$user->get('emailAddress')] = true;
                $sentCount ++;
            }
        }

        $contacts = $entity->get('contacts');
        foreach ($contacts as $contact) {
            if ($contact->get('emailAddress') && !array_key_exists($contact->get('emailAddress'), $emailHash)) {
                $invitationManager->sendInvitation($entity, $contact, 'contacts');
                $emailHash[$user->get('emailAddress')] = true;
                $sentCount ++;
            }
        }

        $leads = $entity->get('leads');
        foreach ($leads as $lead) {
            if ($lead->get('emailAddress') && !array_key_exists($lead->get('emailAddress'), $emailHash)) {
                $invitationManager->sendInvitation($entity, $lead, 'leads');
                $emailHash[$user->get('emailAddress')] = true;
                $sentCount ++;
            }
        }

        if (!$sentCount) return false;

        return true;
    }

    public function loadAdditionalFields(Entity $entity)
    {
        parent::loadAdditionalFields($entity);
        $this->loadRemindersField($entity);
    }

    protected function loadRemindersField(Entity $entity)
    {
        $reminders = $this->getRepository()->getEntityReminderList($entity);
        $entity->set('reminders', $reminders);
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

    public function getSelectAttributeList($params)
    {
        $attributeList = parent::getSelectAttributeList($params);
        if (is_array($attributeList)) {
            if (array_key_exists('select', $params)) {
                $passedAttributeList = $params['select'];
                if (in_array('duration', $passedAttributeList)) {
                    if (!in_array('dateStart', $attributeList)) {
                        $attributeList[] = 'dateStart';
                    }
                    if (!in_array('dateEnd', $attributeList)) {
                        $attributeList[] = 'dateEnd';
                    }
                }
            }
        }
        return $attributeList;
    }

    public function setAcceptanceStatus(string $id, string $status, ?string $userId = null)
    {
        $userId = $userId ?? $this->getUser()->id;

        $statusList = $this->getMetadata()->get(['entityDefs', $this->entityType, 'fields', 'acceptanceStatus', 'options'], []);
        if (!in_array($status, $statusList)) throw new BadRequest();

        $entity = $this->getEntityManager()->getEntity($this->entityType, $id);
        if (!$entity) throw new NotFound();
        if (!$entity->hasLinkMultipleId('users', $userId));


        $this->getEntityManager()->getRepository($this->entityType)->updateRelation(
            $entity, 'users', $userId, (object) ['status' => $status]
        );

        $actionData = [
            'eventName' => $entity->get('name'),
            'eventType' => $entity->getEntityType(),
            'eventId' => $entity->id,
            'dateStart' => $entity->get('dateStart'),
            'status' => $status,
            'link' => 'users',
            'inviteeType' => 'User',
            'inviteeId' => $userId,
        ];

        $this->getEntityManager()->getHookManager()->process($this->entityType, 'afterConfirmation', $entity, [], $actionData);

        return true;
    }
}
