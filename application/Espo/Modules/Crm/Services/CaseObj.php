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

class CaseObj extends \Espo\Services\Record
{
    protected $mergeLinkList = [
        'tasks',
        'meetings',
        'calls',
        'emails'
    ];

    protected $readOnlyAttributeList = [
        'inboundEmailId'
    ];

    protected $noEditAccessRequiredLinkList = [
        'articles'
    ];

    public function beforeCreateEntity(Entity $entity, $data)
    {
        parent::beforeCreateEntity($entity, $data);

        if ($this->getUser()->isPortal()) {
            if (!$entity->has('accountId')) {
                if ($this->getUser()->get('contactId')) {
                    $contact = $this->getEntityManager()->getEntity('Contact', $this->getUser()->get('contactId'));
                    if ($contact && $contact->get('accountId')) {
                        $entity->set('accountId', $contact->get('accountId'));
                    }
                }
            }
            if (!$entity->has('contactId')) {
                if ($this->getUser()->get('contactId')) {
                    $entity->set('contactId', $this->getUser()->get('contactId'));
                }
            }
        }
    }

    public function afterCreateEntity(Entity $entity, $data)
    {
        parent::afterCreateEntity($entity, $data);
        if (!empty($data->emailId)) {
            $email = $this->getEntityManager()->getEntity('Email', $data->emailId);
            if ($email && !$email->get('parentId')) {
                $email->set(array(
                    'parentType' => 'Case',
                    'parentId' => $entity->id
                ));
                $this->getEntityManager()->saveEntity($email);
            }
        }
    }

    public function getEmailAddressList($id)
    {
        $entity = $this->getEntity($id);
        $forbiddenFieldList = $this->getAcl()->getScopeForbiddenFieldList($this->getEntityType());

        $list = [];
        $emailAddressList = [];

        if (!in_array('contact', $forbiddenFieldList) && $this->getAcl()->checkScope('Contact')) {
            if ($entity->get('contactId')) {
                $contact = $this->getEntityManager()->getEntity('Contact', $entity->get('contactId'));
                if ($contact && $contact->get('emailAddress')) {
                    $emailAddress = $contact->get('emailAddress');
                    if ($this->getAcl()->checkEntity($contact)) {
                        $list[] = (object) [
                            'emailAddress' => $emailAddress,
                            'name' => $contact->get('name'),
                            'entityType' => 'Contact'
                        ];
                        $emailAddressList[] = $emailAddress;
                    }
                }
            }
        }

        if (!in_array('contacts', $forbiddenFieldList) && $this->getAcl()->checkScope('Contact')) {
            $contactIdList = $entity->getLinkMultipleIdList('contacts');
            if (count($contactIdList)) {
                $contactForbiddenFieldList = $this->getAcl()->getScopeForbiddenFieldList('Contact');
                if (!in_array('emailAddress', $contactForbiddenFieldList)) {
                    $selectManager = $this->getSelectManagerFactory()->create('Contact');
                    $selectParams = $selectManager->getEmptySelectParams();
                    $selectManager->applyAccess($selectParams);
                    $contactList = $this->getEntityManager()->getRepository('Contact')->select(['id', 'emailAddress', 'name'])->where([
                        'id' => $contactIdList
                    ])->find($selectParams);

                    foreach ($contactList as $contact) {
                        $emailAddress = $contact->get('emailAddress');
                        if ($emailAddress && !in_array($emailAddress, $emailAddressList)) {
                            $list[] = (object) [
                                'emailAddress' => $emailAddress,
                                'name' => $contact->get('name'),
                                'entityType' => 'Contact'
                            ];
                            $emailAddressList[] = $emailAddress;
                        }
                    }
                }
            }
        }

        if (empty($list)) {
            if (!in_array('account', $forbiddenFieldList) && $this->getAcl()->checkScope('Account')) {
                if ($entity->get('accountId')) {
                    $account = $this->getEntityManager()->getEntity('Account', $entity->get('accountId'));
                    if ($account && $account->get('emailAddress')) {
                        $emailAddress = $account->get('emailAddress');
                        if ($this->getAcl()->checkEntity($account)) {
                            $list[] = (object) [
                                'emailAddress' => $emailAddress,
                                'name' => $account->get('name'),
                                'entityType' => 'Account'
                            ];
                            $emailAddressList[] = $emailAddress;
                        }
                    }
                }
            }
        }

        if (empty($list)) {
            if (!in_array('lead', $forbiddenFieldList) && $this->getAcl()->checkScope('Lead')) {
                if ($entity->get('leadId')) {
                    $lead = $this->getEntityManager()->getEntity('Lead', $entity->get('leadId'));
                    if ($lead && $lead->get('emailAddress')) {
                        $emailAddress = $lead->get('emailAddress');
                        if ($this->getAcl()->checkEntity($lead)) {
                            $list[] = (object) [
                                'emailAddress' => $emailAddress,
                                'name' => $lead->get('name'),
                                'entityType' => 'Lead'
                            ];
                            $emailAddressList[] = $emailAddress;
                        }
                    }
                }
            }
        }

        return $list;
    }
}

