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

namespace Espo\Core\FieldProcessing\EmailAddress;

use Espo\Core\ORM\Repository\Option\SaveOption;
use Espo\Core\ORM\Type\FieldType;
use Espo\Entities\EmailAddress;
use Espo\Repositories\EmailAddress as EmailAddressRepository;
use Espo\ORM\Entity;
use Espo\Core\ApplicationState;
use Espo\Core\FieldProcessing\Saver as SaverInterface;
use Espo\Core\FieldProcessing\Saver\Params;
use Espo\Core\ORM\EntityManager;

/**
 * @implements SaverInterface<Entity>
 */
class Saver implements SaverInterface
{
    public function __construct(
        private EntityManager $entityManager,
        private ApplicationState $applicationState,
        private AccessChecker $accessChecker
    ) {}

    public function process(Entity $entity, Params $params): void
    {
        $entityType = $entity->getEntityType();

        $defs = $this->entityManager->getDefs()->getEntity($entityType);

        if (!$defs->hasField('emailAddress')) {
            return;
        }

        if ($defs->getField('emailAddress')->getType() !== FieldType::EMAIL) {
            return;
        }

        $emailAddressData = null;

        if ($entity->has('emailAddressData')) {
            $emailAddressData = $entity->get('emailAddressData');
        }

        if ($emailAddressData !== null && $entity->isAttributeChanged('emailAddressData')) {
            $this->storeData($entity);

            return;
        }

        if ($entity->has('emailAddress')) {
            $this->storePrimary($entity);
        }
    }

    private function storeData(Entity $entity): void
    {
        if (!$entity->has('emailAddressData')) {
            return;
        }

        $emailAddressValue = $entity->get('emailAddress');

        if (is_string($emailAddressValue)) {
            $emailAddressValue = trim($emailAddressValue);
        }

        $emailAddressData = $entity->get('emailAddressData');

        if (!is_array($emailAddressData)) {
            return;
        }

        $noPrimary = array_filter($emailAddressData, fn ($item) => !empty($item->primary)) === [];

        if ($noPrimary && $emailAddressData !== []) {
            $emailAddressData[0]->primary = true;
        }

        $keyList = [];
        $keyPreviousList = [];
        $previousEmailAddressData = [];

        if (!$entity->isNew()) {
            /** @var EmailAddressRepository $repository */
            $repository = $this->entityManager->getRepository(EmailAddress::ENTITY_TYPE);

            $previousEmailAddressData = $repository->getEmailAddressData($entity);
        }

        $hash = (object) [];
        $hashPrevious = (object) [];

        foreach ($emailAddressData as $row) {
            $key = trim($row->emailAddress);

            if (empty($key)) {
                continue;
            }

            $key = strtolower($key);

            $hash->$key = [
                'primary' => !empty($row->primary),
                'optOut' => !empty($row->optOut),
                'invalid' => !empty($row->invalid),
                'emailAddress' => trim($row->emailAddress),
            ];

            $keyList[] = $key;
        }

        if (
            $entity->has('emailAddressIsOptedOut') &&
            (
                $entity->isNew() ||
                (
                    $entity->hasFetched('emailAddressIsOptedOut') &&
                    $entity->get('emailAddressIsOptedOut') !== $entity->getFetched('emailAddressIsOptedOut')
                )
            ) &&
            $emailAddressValue
        ) {
            $key = strtolower($emailAddressValue);

            if ($key && isset($hash->$key)) {
                $hash->{$key}['optOut'] = (bool) $entity->get('emailAddressIsOptedOut');
            }
        }

        if (
            $entity->has('emailAddressIsInvalid') &&
            (
                $entity->isNew() ||
                (
                    $entity->hasFetched('emailAddressIsInvalid') &&
                    $entity->get('emailAddressIsInvalid') !== $entity->getFetched('emailAddressIsInvalid')
                )
            ) &&
            $emailAddressValue
        ) {
            $key = strtolower($emailAddressValue);

            if ($key && isset($hash->$key)) {
                $hash->{$key}['invalid'] = (bool) $entity->get('emailAddressIsInvalid');
            }
        }

        foreach ($previousEmailAddressData as $row) {
            $key = $row->lower;

            if (empty($key)) {
                continue;
            }

            $hashPrevious->$key = [
                'primary' => (bool) $row->primary,
                'optOut' => (bool) $row->optOut,
                'invalid' => (bool) $row->invalid,
                'emailAddress' => $row->emailAddress,
            ];

            $keyPreviousList[] = $key;
        }

        $primary = false;

        $toCreateList = [];
        $toUpdateList = [];
        $toRemoveList = [];

        $revertData = [];

        foreach ($keyList as $key) {
            $new = true;
            $changed = false;

            if ($hash->{$key}['primary']) {
                $primary = $key;
            }

            if (property_exists($hashPrevious, $key)) {
                $new = false;

                $changed =
                    $hash->{$key}['optOut'] != $hashPrevious->{$key}['optOut'] ||
                    $hash->{$key}['invalid'] != $hashPrevious->{$key}['invalid'] ||
                    $hash->{$key}['emailAddress'] !== $hashPrevious->{$key}['emailAddress'];

                if (
                    $hash->{$key}['primary'] &&
                    $hash->{$key}['primary'] === $hashPrevious->{$key}['primary']
                ) {
                    $primary = false;
                }
            }

            if ($new) {
                $toCreateList[] = $key;
            }

            if ($changed) {
                $toUpdateList[] = $key;
            }
        }

        foreach ($keyPreviousList as $key) {
            if (!property_exists($hash, $key)) {
                $toRemoveList[] = $key;
            }
        }

        foreach ($toRemoveList as $address) {
            $emailAddress = $this->getByAddress($address);

            if (!$emailAddress) {
                continue;
            }

            $delete = $this->entityManager
                ->getQueryBuilder()
                ->delete()
                ->from('EntityEmailAddress')
                ->where([
                    'entityId' => $entity->getId(),
                    'entityType' => $entity->getEntityType(),
                    'emailAddressId' => $emailAddress->getId(),
                ])
                ->build();

            $this->entityManager->getQueryExecutor()->execute($delete);
        }

        foreach ($toUpdateList as $address) {
            $emailAddress = $this->getByAddress($address);

            if ($emailAddress) {
                $skipSave = $this->checkChangeIsForbidden($emailAddress, $entity);

                if (!$skipSave) {
                    $emailAddress->set([
                        'optOut' => $hash->{$address}['optOut'],
                        'invalid' => $hash->{$address}['invalid'],
                        'name' => $hash->{$address}['emailAddress']
                    ]);

                    $this->entityManager->saveEntity($emailAddress);
                }
                else {
                    $revertData[$address] = [
                        'optOut' => $emailAddress->get('optOut'),
                        'invalid' => $emailAddress->get('invalid'),
                    ];
                }
            }
        }

        foreach ($toCreateList as $address) {
            $emailAddress = $this->getByAddress($address);

            if (!$emailAddress) {
                $emailAddress = $this->entityManager->getNewEntity(EmailAddress::ENTITY_TYPE);

                $emailAddress->set([
                    'name' => $hash->{$address}['emailAddress'],
                    'optOut' => $hash->{$address}['optOut'],
                    'invalid' => $hash->{$address}['invalid'],
                ]);

                $this->entityManager->saveEntity($emailAddress);
            }
            else {
                $skipSave = $this->checkChangeIsForbidden($emailAddress, $entity);

                if (!$skipSave) {
                    if (
                        $emailAddress->get('optOut') != $hash->{$address}['optOut'] ||
                        $emailAddress->get('invalid') != $hash->{$address}['invalid'] ||
                        $emailAddress->get('emailAddress') != $hash->{$address}['emailAddress']
                    ) {
                        $emailAddress->set([
                            'optOut' => $hash->{$address}['optOut'],
                            'invalid' => $hash->{$address}['invalid'],
                            'name' => $hash->{$address}['emailAddress']
                        ]);

                        $this->entityManager->saveEntity($emailAddress);
                    }
                }
                else {
                    $revertData[$address] = [
                        'optOut' => $emailAddress->get('optOut'),
                        'invalid' => $emailAddress->get('invalid')
                    ];
                }
            }

            $entityEmailAddress = $this->entityManager->getNewEntity('EntityEmailAddress');

            $entityEmailAddress->set([
                'entityId' => $entity->getId(),
                'entityType' => $entity->getEntityType(),
                'emailAddressId' => $emailAddress->getId(),
                'primary' => $address === $primary,
                'deleted' => false,
            ]);

            $mapper = $this->entityManager->getMapper();

            $mapper->insertOnDuplicateUpdate($entityEmailAddress, [
                'primary',
                'deleted',
            ]);
        }

        if ($primary) {
            $emailAddress = $this->getByAddress($primary);

            if ($emailAddress) {
                $update1 = $this->entityManager
                    ->getQueryBuilder()
                    ->update()
                    ->in('EntityEmailAddress')
                    ->set(['primary' => false])
                    ->where([
                        'entityId' => $entity->getId(),
                        'entityType' => $entity->getEntityType(),
                        'primary' => true,
                        'deleted' => false,
                    ])
                    ->build();

                $this->entityManager->getQueryExecutor()->execute($update1);

                $update2 = $this->entityManager
                    ->getQueryBuilder()
                    ->update()
                    ->in('EntityEmailAddress')
                    ->set(['primary' => true])
                    ->where([
                        'entityId' => $entity->getId(),
                        'entityType' => $entity->getEntityType(),
                        'emailAddressId' => $emailAddress->getId(),
                        'deleted' => false,
                    ])
                    ->build();

                $this->entityManager->getQueryExecutor()->execute($update2);
            }
        }

        if (!empty($revertData)) {
            foreach ($emailAddressData as $row) {
                if (empty($revertData[$row->emailAddress])) {
                    continue;
                }

                $row->optOut = $revertData[$row->emailAddress]['optOut'];
                $row->invalid = $revertData[$row->emailAddress]['invalid'];
            }

            $entity->set('emailAddressData', $emailAddressData);
        }
    }

    private function storePrimary(Entity $entity): void
    {
        if (!$entity->has('emailAddress')) {
            return;
        }

        $emailAddressValue = $entity->get('emailAddress');

        if (is_string($emailAddressValue)) {
            $emailAddressValue = trim($emailAddressValue);
        }

        if (!empty($emailAddressValue)) {
            $this->storePrimaryNotEmpty($entity, $emailAddressValue);

            return;
        }

        $emailAddressValueOld = $entity->getFetched('emailAddress');

        if (!empty($emailAddressValueOld)) {
            $emailAddressOld = $this->getByAddress($emailAddressValueOld);

            if ($emailAddressOld) {
                $this->entityManager
                    ->getRDBRepository($entity->getEntityType())
                    ->getRelation($entity, 'emailAddresses')
                    ->unrelate($emailAddressOld, [SaveOption::SKIP_HOOKS => true]);
            }
        }
    }

    private function storePrimaryNotEmpty(Entity $entity, string $emailAddressValue): void
    {
        if ($emailAddressValue === $entity->getFetched('emailAddress')) {
             if (
                $entity->has('emailAddressIsOptedOut') &&
                (
                    $entity->isNew() ||
                    (
                        $entity->hasFetched('emailAddressIsOptedOut') &&
                        $entity->get('emailAddressIsOptedOut') !== $entity->getFetched('emailAddressIsOptedOut')
                    )
                )
            ) {
                $this->markAddressOptedOut($emailAddressValue, (bool) $entity->get('emailAddressIsOptedOut'));
            }

            if (
                $entity->has('emailAddressIsInvalid') &&
                (
                    $entity->isNew() ||
                    (
                        $entity->hasFetched('emailAddressIsInvalid') &&
                        $entity->get('emailAddressIsInvalid') !== $entity->getFetched('emailAddressIsInvalid')
                    )
                )
            ) {
                $this->markAddressInvalid($emailAddressValue, (bool) $entity->get('emailAddressIsInvalid'));
            }

            return;
        }

        $entityRepository = $this->entityManager->getRDBRepository($entity->getEntityType());

        $emailAddressNew = $this->entityManager
            ->getRDBRepository(EmailAddress::ENTITY_TYPE)
            ->where([
                'lower' => strtolower($emailAddressValue),
            ])
            ->findOne();

        if (!$emailAddressNew) {
            $emailAddressNew = $this->entityManager->getNewEntity(EmailAddress::ENTITY_TYPE);

            $emailAddressNew->set('name', $emailAddressValue);

            if ($entity->has('emailAddressIsOptedOut')) {
                $emailAddressNew->set('optOut', (bool) $entity->get('emailAddressIsOptedOut'));
            }

            if ($entity->has('emailAddressIsInvalid')) {
                $emailAddressNew->set('invalid', (bool) $entity->get('emailAddressIsInvalid'));
            }

            $this->entityManager->saveEntity($emailAddressNew);
        }

        $emailAddressValueOld = $entity->getFetched('emailAddress');

        if (!empty($emailAddressValueOld)) {
            $emailAddressOld = $this->getByAddress($emailAddressValueOld);

            if ($emailAddressOld) {
                $entityRepository
                    ->getRelation($entity, 'emailAddresses')
                    ->unrelate($emailAddressOld, [SaveOption::SKIP_HOOKS => true]);
            }
        }

        $entityRepository
            ->getRelation($entity, 'emailAddresses')
            ->relate($emailAddressNew, null, [SaveOption::SKIP_HOOKS => true]);

        if ($entity->has('emailAddressIsOptedOut')) {
            $this->markAddressOptedOut($emailAddressValue, (bool) $entity->get('emailAddressIsOptedOut'));
        }

        if ($entity->has('emailAddressIsInvalid')) {
            $this->markAddressInvalid($emailAddressValue, (bool) $entity->get('emailAddressIsInvalid'));
        }

        $updateQuery = $this->entityManager
            ->getQueryBuilder()
            ->update()
            ->in('EntityEmailAddress')
            ->set(['primary' => true])
            ->where([
                'entityId' => $entity->getId(),
                'entityType' => $entity->getEntityType(),
                'emailAddressId' => $emailAddressNew->getId(),
            ])
            ->build();

        $this->entityManager->getQueryExecutor()->execute($updateQuery);
    }

    private function getByAddress(string $address): ?EmailAddress
    {
        /** @var EmailAddressRepository $repository */
        $repository = $this->entityManager->getRepository(EmailAddress::ENTITY_TYPE);

        return $repository->getByAddress($address);
    }

    private function markAddressOptedOut(string $address, bool $isOptedOut = true): void
    {
        /** @var EmailAddressRepository $repository */
        $repository = $this->entityManager->getRepository(EmailAddress::ENTITY_TYPE);

        $repository->markAddressOptedOut($address, $isOptedOut);
    }

    private function markAddressInvalid(string $address, bool $isInvalid = true): void
    {
        /** @var EmailAddressRepository $repository */
        $repository = $this->entityManager->getRepository(EmailAddress::ENTITY_TYPE);

        $repository->markAddressInvalid($address, $isInvalid);
    }

    private function checkChangeIsForbidden(EmailAddress $emailAddress, Entity $entity): bool
    {
        if (!$this->applicationState->hasUser()) {
            return true;
        }

        $user = $this->applicationState->getUser();

        // @todo Check if not modified by system.

        return !$this->accessChecker->checkEdit($user, $emailAddress, $entity);
    }
}
