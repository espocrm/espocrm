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

namespace Espo\Core\Mail\Importer;

use Espo\Core\Mail\Message;
use Espo\Core\Templates\Entities\Company;
use Espo\Core\Templates\Entities\Person;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\Metadata;
use Espo\Entities\Email;
use Espo\Entities\EmailAddress;
use Espo\Modules\Crm\Entities\Account;
use Espo\Modules\Crm\Entities\Contact;
use Espo\Modules\Crm\Entities\Lead;
use Espo\ORM\Entity;
use Espo\ORM\EntityManager;
use Espo\Repositories\EmailAddress as EmailAddressRepository;

class DefaultParentFinder implements ParentFinder
{
    /** @var string[] */
    private array $entityTypeList;

    public function __construct(
        private EntityManager $entityManager,
        private Config $config,
        private Metadata $metadata
    ) {
        $this->entityTypeList = $this->entityManager
            ->getDefs()
            ->getEntity(Email::ENTITY_TYPE)
            ->getField('parent')
            ->getParam('entityList') ?? [];
    }

    public function find(Email $email, Message $message): ?Entity
    {
        return
            $this->getByReferences($message) ??
            $this->getFromReplied($email) ??
            $this->getByFromAddress($email) ??
            $this->getByReplyToAddress($email) ??
            $this->getByToAddress($email);
    }

    private function isEntityTypeAllowed(string $entityType): bool
    {
        return in_array($entityType, $this->entityTypeList);
    }

    private function getByFromAddress(Email $email): ?Entity
    {
        $from = $email->getFromAddress();

        if (!$from) {
            return null;
        }

        return $this->getByAddress($from);
    }

    private function getByReplyToAddress(Email $email): ?Entity
    {
        $list = $email->getReplyToAddressList();

        if ($list === []) {
            return null;
        }

        return $this->getByAddress($list[0]);
    }

    private function getByToAddress(Email $email): ?Entity
    {
        $list = $email->getToAddressList();

        if ($list === []) {
            return null;
        }

        return $this->getByAddress($list[0]);
    }

    private function getByAddress(string $emailAddress): ?Entity
    {
        /** @var ?Contact $contact */
        $contact = $this->entityManager
            ->getRDBRepository(Contact::ENTITY_TYPE)
            ->where([
                'emailAddress' => $emailAddress
            ])
            ->findOne();

        if ($contact) {
            $accountLink = $contact->getAccount();

            if (
                !$this->config->get('b2cMode') &&
                $accountLink &&
                $this->isEntityTypeAllowed(Account::ENTITY_TYPE)
            ) {
                return $this->entityManager->getEntityById(Account::ENTITY_TYPE, $accountLink->getId());
            }

            if ($this->isEntityTypeAllowed(Contact::ENTITY_TYPE)) {
                return $contact;
            }
        }

        if ($this->isEntityTypeAllowed(Account::ENTITY_TYPE)) {
            $account = $this->entityManager
                ->getRDBRepository(Account::ENTITY_TYPE)
                ->where([
                    'emailAddress' => $emailAddress
                ])
                ->findOne();

            if ($account) {
                return $account;
            }
        }

        if ($this->isEntityTypeAllowed(Lead::ENTITY_TYPE)) {
            $lead = $this->entityManager
                ->getRDBRepository(Lead::ENTITY_TYPE)
                ->where(['emailAddress' => $emailAddress])
                ->findOne();

            if ($lead) {
                return $lead;
            }
        }

        $entityTypeList = array_filter(
            $this->entityTypeList,
            function ($entityType) {
                return
                    !in_array(
                        $entityType,
                        [Account::ENTITY_TYPE, Contact::ENTITY_TYPE, Lead::ENTITY_TYPE]
                    ) &&
                    in_array(
                        $this->metadata->get(['scopes', $entityType, 'type']),
                        [Company::TEMPLATE_TYPE, Person::TEMPLATE_TYPE]
                    );
            }
        );

        /** @var EmailAddressRepository $emailAddressRepository */
        $emailAddressRepository = $this->entityManager->getRepository(EmailAddress::ENTITY_TYPE);

        foreach ($entityTypeList as $entityType) {
            $entity = $emailAddressRepository->getEntityByAddress($emailAddress, $entityType);

            if ($entity) {
                return $entity;
            }
        }

        return null;
    }

    private function getFromReplied(Email $email): ?Entity
    {
        $repliedLink = $email->getReplied();

        if (!$repliedLink) {
            return null;
        }

        /** @var ?Email $repliedEmail */
        $repliedEmail = $this->entityManager
            ->getRDBRepositoryByClass(Email::class)
            ->getById($repliedLink->getId());

        if (!$repliedEmail) {
            return null;
        }

        $parentLink = $repliedEmail->getParent();

        if (!$parentLink) {
            return null;
        }

        if (!$this->entityManager->hasRepository($parentLink->getEntityType())) {
            return null;
        }

        return $this->entityManager->getEntityById($parentLink->getEntityType(), $parentLink->getId());
    }

    private function getByReferences(Message $message): ?Entity
    {
        $references = $message->getHeader('References');

        if (!$references) {
            return null;
        }

        $delimiter = strpos($references, '>,') ? ',' : ' ';

        foreach (explode($delimiter, $references) as $reference) {
            $reference = str_replace(['/', '@'], ' ', trim(trim($reference), '<>'));

            $parent = $this->getByReferencesItem($reference);

            if ($parent) {
                return $parent;
            }
        }

        return null;
    }

    private function getByReferencesItem(string $reference): ?Entity
    {
        $parentType = null;
        $parentId = null;
        $number = null;
        $emailSent = PHP_INT_MAX;

        $n = sscanf($reference, '%s %s %d %d espo', $parentType, $parentId, $emailSent, $number);

        if ($n !== 4) {
            $n = sscanf($reference, '%s %s %d %d espo-system', $parentType, $parentId, $emailSent, $number);
        }

        if ($n !== 4 || $emailSent >= time()) {
            return null;
        }

        if (!$parentType || !$parentId) {
            return null;
        }

        if (!is_string($parentType) || !is_string($parentId)) {
            return null;
        }

        if (!$this->entityManager->hasRepository($parentType)) {
            return null;
        }

        $parent = $this->entityManager->getEntityById($parentType, $parentId);

        if ($parent instanceof Lead) {
            return $this->getFromLead($parent) ?? $parent;
        }

        return $parent;
    }

    private function getFromLead(Lead $lead): ?Entity
    {
        if ($lead->getStatus() !== Lead::STATUS_CONVERTED) {
            return null;
        }

        $createdAccountLink = $lead->getCreatedAccount();

        if ($createdAccountLink) {
            return $this->entityManager->getEntityById(Account::ENTITY_TYPE, $createdAccountLink->getId());
        }

        $createdContactLink = $lead->getCreatedContact();

        if (
            $this->config->get('b2cMode') &&
            $createdContactLink
        ) {
            return $this->entityManager->getEntityById(Contact::ENTITY_TYPE, $createdContactLink->getId());
        }

        return null;
    }
}
