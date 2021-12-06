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

use Espo\{
    ORM\Entity,
    Services\Record,
    Modules\Crm\Entities\CaseObj as CaseEntity,
};

use stdClass;

class CaseObj extends Record
{
    protected $noEditAccessRequiredLinkList = [
        'articles',
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

            if ($email && !$email->get('parentId') && $this->getAcl()->check($email)) {
                $email->set([
                    'parentType' => 'Case',
                    'parentId' => $entity->getId(),
                ]);

                $this->getEntityManager()->saveEntity($email);
            }
        }
    }

    public function getEmailAddressList(string $id): array
    {
        /** @var CaseEntity */
        $entity = $this->getEntity($id);

        $forbiddenFieldList = $this->acl->getScopeForbiddenFieldList($this->getEntityType());

        $list = [];

        if (
            !in_array('contacts', $forbiddenFieldList) &&
            $this->acl->checkScope('Contact')
        ) {
            foreach ($this->getContactEmailAddressList($entity) as $item) {
                $list[] = $item;
            }
        }

        if (
            empty($list) &&
            !in_array('account', $forbiddenFieldList) &&
            $this->acl->checkScope('Account') &&
            $entity->get('accountId')
        ) {
            $item = $this->getAccountEmailAddress($entity, $list);

            if ($item) {
                $list[] = $item;
            }
        }

        if (
            empty($list) &&
            !in_array('lead', $forbiddenFieldList) &&
            $this->acl->checkScope('Lead') &&
            $entity->get('leadId')
        ) {
            $item = $this->getLeadEmailAddress($entity, $list);

            if ($item) {
                $list[] = $item;
            }
        }

        return $list;
    }

    protected function getAccountEmailAddress(CaseEntity $entity, array $dataList): ?stdClass
    {
        $account = $this->entityManager->getEntity('Account', $entity->get('accountId'));

        if (!$account || !$account->get('emailAddress')) {
            return null;
        }

        $emailAddress = $account->get('emailAddress');

        if (!$this->acl->checkEntity($account)) {
            return null;
        }

        foreach ($dataList as $item) {
            if ($item->emailAddress === $emailAddress) {
                return null;
            }
        }

        return (object) [
            'emailAddress' => $emailAddress,
            'name' => $account->get('name'),
            'entityType' => 'Account',
        ];
    }

    protected function getLeadEmailAddress(CaseEntity $entity, array $dataList): ?stdClass
    {
        $lead = $this->entityManager->getEntity('Account', $entity->get('leadId'));

        if (!$lead || !$lead->get('emailAddress')) {
            return null;
        }

        $emailAddress = $lead->get('emailAddress');

        if (!$this->acl->checkEntity($lead)) {
            return null;
        }

        foreach ($dataList as $item) {
            if ($item->emailAddress === $emailAddress) {
                return null;
            }
        }

        return (object) [
            'emailAddress' => $emailAddress,
            'name' => $lead->get('name'),
            'entityType' => 'Lead',
        ];
    }

    protected function getContactEmailAddressList(CaseEntity $entity): array
    {
        $contactIdList = $entity->getLinkMultipleIdList('contacts');

        if (!count($contactIdList)) {
            return [];
        }

        $contactForbiddenFieldList = $this->acl->getScopeForbiddenFieldList('Contact');

        if (in_array('emailAddress', $contactForbiddenFieldList)) {
            return [];
        }

        $dataList = [];

        $emailAddressList = [];

        $query = $this->selectBuilderFactory
            ->create()
            ->from('Contact')
            ->withStrictAccessControl()
            ->buildQueryBuilder()
            ->select([
                'id',
                'emailAddress',
                'name',
            ])
            ->where([
                'id' => $contactIdList,
            ])
            ->build();

        $contactCollection = $this->entityManager
            ->getRDBRepository('Contact')
            ->clone($query)
            ->find();

        foreach ($contactCollection as $contact) {
            $emailAddress = $contact->get('emailAddress');

            if (!$emailAddress) {
                continue;
            }

            if (in_array($emailAddress, $emailAddressList)) {
                continue;
            }

            $emailAddressList[] = $emailAddress;

            $dataList[] = (object) [
                'emailAddress' => $emailAddress,
                'name' => $contact->get('name'),
                'entityType' => 'Contact',
            ];
        }

        return $dataList;
    }
}
