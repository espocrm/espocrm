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

namespace Espo\Modules\Crm\Tools\Case;

use Espo\Core\Acl;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Field\EmailAddress;
use Espo\Core\Record\ServiceContainer;
use Espo\Core\Select\SelectBuilderFactory;
use Espo\Core\Templates\Entities\Company;
use Espo\Core\Templates\Entities\Person;
use Espo\Core\Utils\Metadata;
use Espo\Modules\Crm\Entities\Account;
use Espo\Modules\Crm\Entities\Contact;
use Espo\Modules\Crm\Entities\CaseObj;
use Espo\Modules\Crm\Entities\Lead;
use Espo\ORM\Collection;
use Espo\ORM\EntityManager;
use Espo\ORM\Type\RelationType;
use Espo\Tools\Email\EmailAddressEntityPair;
use RuntimeException;

class Service
{
    public function __construct(
        private ServiceContainer $serviceContainer,
        private Acl $acl,
        private EntityManager $entityManager,
        private SelectBuilderFactory $selectBuilderFactory,
        private Metadata $metadata
    ) {}

    /**
     * @throws Forbidden
     * @return EmailAddressEntityPair[]
     */
    public function getEmailAddressList(string $id): array
    {
        /** @var CaseObj $entity */
        $entity = $this->serviceContainer
            ->get(CaseObj::ENTITY_TYPE)
            ->getEntity($id);

        $list = [];

        if (
            $this->acl->checkField(CaseObj::ENTITY_TYPE, 'contacts') &&
            $this->acl->checkScope(Contact::ENTITY_TYPE)
        ) {
            foreach ($this->getContactEmailAddressList($entity) as $item) {
                $list[] = $item;
            }
        }

        if (
            $list === [] &&
            $this->acl->checkField(CaseObj::ENTITY_TYPE, 'account') &&
            $this->acl->checkScope(Account::ENTITY_TYPE)
        ) {
            $item = $this->getAccountEmailAddress($entity, $list);

            if ($item) {
                $list[] = $item;
            }
        }

        if (
            $list === [] &&
            $this->acl->checkField(CaseObj::ENTITY_TYPE, 'lead') &&
            $this->acl->checkScope(Lead::ENTITY_TYPE)
        ) {
            $item = $this->getLeadEmailAddress($entity, $list);

            if ($item) {
                $list[] = $item;
            }
        }

        if ($list === []) {
            $item = $this->findPersonEmailAddress($entity);

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
        $account = $entity->getAccount();

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

        if (!$this->acl->checkField(Account::ENTITY_TYPE, 'emailAddress')) {
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
        $lead = $entity->getLead();

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

        if (!$this->acl->checkField(Lead::ENTITY_TYPE, 'emailAddress')) {
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

        if (!$this->acl->checkField(Contact::ENTITY_TYPE, 'emailAddress')) {
            return[];
        }

        $dataList = [];

        $emailAddressList = [];

        try {
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
        } catch (BadRequest|Forbidden $e) {
            throw new RuntimeException($e->getMessage());
        }

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

        $primaryContact = $entity->getContact();

        if (!$primaryContact) {
            return $dataList;
        }

        usort(
            $dataList,
            function (
                EmailAddressEntityPair $o1,
                EmailAddressEntityPair $o2
            ) use ($primaryContact) {
                if ($o1->getEntity()->getId() === $primaryContact->getId()) {
                    return -1;
                }

                if ($o2->getEntity()->getId() === $primaryContact->getId()) {
                    return 1;
                }

                return 0;
            }
        );

        return $dataList;
    }

    private function findPersonEmailAddress(CaseObj $entity): ?EmailAddressEntityPair
    {
        $relations = $this->entityManager
            ->getDefs()
            ->getEntity(CaseObj::ENTITY_TYPE)
            ->getRelationList();

        foreach ($relations as $relation) {
            if (
                $relation->getType() !== RelationType::BELONGS_TO &&
                $relation->getType() !== RelationType::HAS_ONE
            ) {
                continue;
            }

            $foreignEntityType = $relation->getForeignEntityType();

            if (
                $this->metadata->get("scopes.$foreignEntityType.type") !== Person::TEMPLATE_TYPE &&
                $this->metadata->get("scopes.$foreignEntityType.type") !== Company::TEMPLATE_TYPE
            ) {
                continue;
            }

            $address = $this->getPersonEmailAddress($entity, $relation->getName());

            if ($address) {
                return $address;
            }
        }

        return null;
    }

    private function getPersonEmailAddress(CaseObj $entity, string $link): ?EmailAddressEntityPair
    {
        $foreignEntity = $this->entityManager
            ->getRDBRepositoryByClass(CaseObj::class)
            ->getRelation($entity, $link)
            ->findOne();

        if (!$foreignEntity) {
            return null;
        }

        if (!$this->acl->checkEntityRead($foreignEntity)) {
            return null;
        }

        if (!$this->acl->checkField($foreignEntity->getEntityType(), 'emailAddress')) {
            return null;
        }

        /** @var ?string $address */
        $address = $foreignEntity->get('emailAddress');

        if (!$address) {
            return null;
        }

        $emailAddress = EmailAddress::create($address);

        return new EmailAddressEntityPair($emailAddress, $foreignEntity);
    }
}
