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

namespace Espo\Modules\Crm\Tools\Case;

use Espo\Core\Acl;
use Espo\Core\Exceptions\ForbiddenSilent;
use Espo\Core\Field\EmailAddress;
use Espo\Core\Record\ServiceContainer;
use Espo\Core\Select\SelectBuilderFactory;
use Espo\Modules\Crm\Entities\Account;
use Espo\Modules\Crm\Entities\Contact;
use Espo\Modules\Crm\Entities\CaseObj;
use Espo\Modules\Crm\Entities\Lead;
use Espo\ORM\Collection;
use Espo\ORM\EntityManager;
use Espo\Tools\Email\EmailAddressEntityPair;

class Service
{
    private ServiceContainer $serviceContainer;
    private Acl $acl;
    private EntityManager $entityManager;
    private SelectBuilderFactory $selectBuilderFactory;

    public function __construct(
        ServiceContainer $serviceContainer,
        Acl $acl,
        EntityManager $entityManager,
        SelectBuilderFactory $selectBuilderFactory
    ) {
        $this->serviceContainer = $serviceContainer;
        $this->acl = $acl;
        $this->entityManager = $entityManager;
        $this->selectBuilderFactory = $selectBuilderFactory;
    }

    /**
     * @throws ForbiddenSilent
     * @return EmailAddressEntityPair[]
     */
    public function getEmailAddressList(string $id): array
    {
        /** @var CaseObj $entity */
        $entity = $this->serviceContainer
            ->get(CaseObj::ENTITY_TYPE)
            ->getEntity($id);

        $forbiddenFieldList = $this->acl->getScopeForbiddenFieldList(CaseObj::ENTITY_TYPE);

        $list = [];

        if (
            !in_array('contacts', $forbiddenFieldList) &&
            $this->acl->checkScope(Contact::ENTITY_TYPE)
        ) {
            foreach ($this->getContactEmailAddressList($entity) as $item) {
                $list[] = $item;
            }
        }

        if (
            $list === [] &&
            !in_array('account', $forbiddenFieldList) &&
            $this->acl->checkScope(Account::ENTITY_TYPE)
        ) {
            $item = $this->getAccountEmailAddress($entity, $list);

            if ($item) {
                $list[] = $item;
            }
        }

        if (
            $list === [] &&
            !in_array('lead', $forbiddenFieldList) &&
            $this->acl->checkScope(Lead::ENTITY_TYPE)
        ) {
            $item = $this->getLeadEmailAddress($entity, $list);

            if ($item) {
                $list[] = $item;
            }
        }

        return $list;
    }

    /**
     * @param EmailAddressEntityPair[] $dataList
     */
    private function getAccountEmailAddress(CaseObj $entity, array $dataList): ?EmailAddressEntityPair
    {
        $accountLink = $entity->getAccount();

        if (!$accountLink) {
            return null;
        }

        /** @var ?Account $account */
        $account = $this->entityManager->getEntityById(Account::ENTITY_TYPE, $accountLink->getId());

        if (!$account) {
            return null;
        }

        $emailAddress = $account->getEmailAddress();

        if (!$emailAddress) {
            return null;
        }

        if (!$this->acl->checkEntity($account)) {
            return null;
        }

        foreach ($dataList as $item) {
            if ($item->getEmailAddress()->getAddress() === $emailAddress) {
                return null;
            }
        }

        return new EmailAddressEntityPair(EmailAddress::create($emailAddress), $account);
    }

    /**
     * @param EmailAddressEntityPair[] $dataList
     */
    private function getLeadEmailAddress(CaseObj $entity, array $dataList): ?EmailAddressEntityPair
    {
        $leadLink = $entity->getLead();

        if (!$leadLink) {
            return null;
        }

        /** @var ?Lead $lead */
        $lead = $this->entityManager->getEntityById(Lead::ENTITY_TYPE, $leadLink->getId());

        if (!$lead) {
            return null;
        }

        $emailAddress = $lead->getEmailAddress();

        if (!$emailAddress) {
            return null;
        }

        if (!$this->acl->checkEntity($lead)) {
            return null;
        }

        foreach ($dataList as $item) {
            if ($item->getEmailAddress()->getAddress() === $emailAddress) {
                return null;
            }
        }

        return new EmailAddressEntityPair(EmailAddress::create($emailAddress), $lead);
    }

    /**
     * @return EmailAddressEntityPair[]
     */
    private function getContactEmailAddressList(CaseObj $entity): array
    {
        $contactsLinkMultiple = $entity->getContacts();

        $contactIdList = $contactsLinkMultiple->getIdList();

        if (!count($contactIdList)) {
            return [];
        }

        $contactForbiddenFieldList = $this->acl->getScopeForbiddenFieldList(Contact::ENTITY_TYPE);

        if (in_array('emailAddress', $contactForbiddenFieldList)) {
            return [];
        }

        $dataList = [];

        $emailAddressList = [];

        $query = $this->selectBuilderFactory
            ->create()
            ->from(Contact::ENTITY_TYPE)
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

        /** @var Collection<Contact> $contactCollection */
        $contactCollection = $this->entityManager
            ->getRDBRepositoryByClass(Contact::class)
            ->clone($query)
            ->find();

        foreach ($contactCollection as $contact) {
            $emailAddress = $contact->getEmailAddress();

            if (!$emailAddress) {
                continue;
            }

            if (in_array($emailAddress, $emailAddressList)) {
                continue;
            }

            $emailAddressList[] = $emailAddress;

            $dataList[] = new EmailAddressEntityPair(EmailAddress::create($emailAddress), $contact);
        }

        $contactLink = $entity->getContact();

        if (!$contactLink) {
            return $dataList;
        }

        usort(
            $dataList,
            function (
                EmailAddressEntityPair $o1,
                EmailAddressEntityPair $o2
            ) use ($contactLink) {
                if ($o1->getEntity()->getId() === $contactLink->getId()) {
                    return -1;
                }

                if ($o2->getEntity()->getId() === $contactLink->getId()) {
                    return 1;
                }

                return 0;
            }
        );

        return $dataList;
    }
}
