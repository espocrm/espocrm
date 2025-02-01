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

namespace Espo\Core\FieldProcessing\PhoneNumber;

use Espo\Core\ORM\Repository\Option\SaveOption;
use Espo\Core\ORM\Type\FieldType;
use Espo\Entities\PhoneNumber;
use Espo\ORM\Name\Attribute;
use Espo\Repositories\PhoneNumber as PhoneNumberRepository;
use Espo\ORM\Entity;
use Espo\ORM\EntityManager;
use Espo\ORM\Mapper\BaseMapper;
use Espo\Core\ApplicationState;
use Espo\Core\FieldProcessing\Saver as SaverInterface;
use Espo\Core\FieldProcessing\Saver\Params;
use Espo\Core\Utils\Metadata;

/**
 * @implements SaverInterface<Entity>
 */
class Saver implements SaverInterface
{
    private const ATTR_PHONE_NUMBER = 'phoneNumber';
    private const ATTR_PHONE_NUMBER_DATA = 'phoneNumberData';
    private const ATTR_PHONE_NUMBER_IS_OPTED_OUT = 'phoneNumberIsOptedOut';
    private const ATTR_PHONE_NUMBER_IS_INVALID = 'phoneNumberIsInvalid';

    public function __construct(
        private EntityManager $entityManager,
        private ApplicationState $applicationState,
        private AccessChecker $accessChecker,
        private Metadata $metadata
    ) {}

    public function process(Entity $entity, Params $params): void
    {
        $entityType = $entity->getEntityType();

        $defs = $this->entityManager->getDefs()->getEntity($entityType);

        if (!$defs->hasField(self::ATTR_PHONE_NUMBER)) {
            return;
        }

        if ($defs->getField(self::ATTR_PHONE_NUMBER)->getType() !== FieldType::PHONE) {
            return;
        }

        $phoneNumberData = null;

        if ($entity->has(self::ATTR_PHONE_NUMBER_DATA)) {
            $phoneNumberData = $entity->get(self::ATTR_PHONE_NUMBER_DATA);
        }

        if ($phoneNumberData !== null && $entity->isAttributeChanged(self::ATTR_PHONE_NUMBER_DATA)) {
            $this->storeData($entity);

            return;
        }

        if ($entity->has(self::ATTR_PHONE_NUMBER)) {
            $this->storePrimary($entity);
        }
    }

    private function storeData(Entity $entity): void
    {
        if (!$entity->has(self::ATTR_PHONE_NUMBER_DATA)) {
            return;
        }

        $phoneNumberValue = $entity->get(self::ATTR_PHONE_NUMBER);

        if (is_string($phoneNumberValue)) {
            $phoneNumberValue = trim($phoneNumberValue);
        }

        $phoneNumberData = $entity->get(self::ATTR_PHONE_NUMBER_DATA);

        if (!is_array($phoneNumberData)) {
            return;
        }

        $noPrimary = array_filter($phoneNumberData, fn ($item) => !empty($item->primary)) === [];

        if ($noPrimary && $phoneNumberData !== []) {
            $phoneNumberData[0]->primary = true;
        }

        $keyList = [];
        $keyPreviousList = [];
        $previousPhoneNumberData = [];

        if (!$entity->isNew()) {
            /** @var PhoneNumberRepository $repository */
            $repository = $this->entityManager->getRepository(PhoneNumber::ENTITY_TYPE);

            $previousPhoneNumberData = $repository->getPhoneNumberData($entity);
        }

        $hash = (object) [];
        $hashPrevious = (object) [];

        foreach ($phoneNumberData as $row) {
            $key = trim($row->phoneNumber);

            if (empty($key)) {
                continue;
            }

            $type = $row->type ??
                $this->metadata
                    ->get(['entityDefs', $entity->getEntityType(), 'fields', 'phoneNumber', 'defaultType']);

            $hash->$key = [
                'primary' => !empty($row->primary),
                'type' => $type,
                'optOut' => !empty($row->optOut),
                'invalid' => !empty($row->invalid),
            ];

            $keyList[] = $key;
        }

        if (
            $entity->has(self::ATTR_PHONE_NUMBER_IS_OPTED_OUT) && (
                $entity->isNew() ||
                (
                    $entity->hasFetched(self::ATTR_PHONE_NUMBER_IS_OPTED_OUT) &&
                    $entity->isAttributeChanged(self::ATTR_PHONE_NUMBER_IS_OPTED_OUT)
                )
            ) &&
            $phoneNumberValue
        ) {
            $key = $phoneNumberValue;

            if (isset($hash->$key)) {
                $hash->{$key}['optOut'] = (bool) $entity->get(self::ATTR_PHONE_NUMBER_IS_OPTED_OUT);
            }
        }

        if (
            $entity->has(self::ATTR_PHONE_NUMBER_IS_INVALID) && (
                $entity->isNew() ||
                (
                    $entity->hasFetched(self::ATTR_PHONE_NUMBER_IS_INVALID) &&
                    $entity->isAttributeChanged(self::ATTR_PHONE_NUMBER_IS_INVALID)
                )
            ) &&
            $phoneNumberValue
        ) {
            $key = $phoneNumberValue;

            if (isset($hash->$key)) {
                $hash->{$key}['invalid'] = (bool) $entity->get(self::ATTR_PHONE_NUMBER_IS_INVALID);
            }
        }

        foreach ($previousPhoneNumberData as $row) {
            $key = $row->phoneNumber;

            if (empty($key)) {
                continue;
            }

            $hashPrevious->$key = [
                'primary' => (bool) $row->primary,
                'type' => $row->type,
                'optOut' => (bool) $row->optOut,
                'invalid' => (bool) $row->invalid,
            ];

            $keyPreviousList[] = $key;
        }

        $primary = null;

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
                    $hash->{$key}['type'] != $hashPrevious->{$key}['type'] ||
                    $hash->{$key}['optOut'] != $hashPrevious->{$key}['optOut'] ||
                    $hash->{$key}['invalid'] != $hashPrevious->{$key}['invalid'];

                if (
                    $hash->{$key}['primary'] &&
                    $hash->{$key}['primary'] === $hashPrevious->{$key}['primary']
                ) {
                    $primary = null;
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

        foreach ($toRemoveList as $number) {
            $phoneNumber = $this->getByNumber($number);

            if (!$phoneNumber) {
                continue;
            }

            $delete = $this->entityManager->getQueryBuilder()
                ->delete()
                ->from('EntityPhoneNumber')
                ->where([
                    'entityId' => $entity->getId(),
                    'entityType' => $entity->getEntityType(),
                    'phoneNumberId' => $phoneNumber->getId(),
                ])
                ->build();

            $this->entityManager->getQueryExecutor()->execute($delete);
        }

        foreach ($toUpdateList as $number) {
            $phoneNumber = $this->getByNumber($number);

            if ($phoneNumber) {
                $skipSave = $this->checkChangeIsForbidden($phoneNumber, $entity);

                if (!$skipSave) {
                    $phoneNumber->set([
                        'type' => $hash->{$number}['type'],
                        'optOut' => $hash->{$number}['optOut'],
                        'invalid' => $hash->{$number}['invalid'],
                    ]);

                    $this->entityManager->saveEntity($phoneNumber);
                } else {
                    $revertData[$number] = [
                        'type' => $phoneNumber->get('type'),
                        'optOut' => $phoneNumber->get('optOut'),
                        'invalid' => $phoneNumber->get('invalid'),
                    ];
                }
            }
        }

        foreach ($toCreateList as $number) {
            $phoneNumber = $this->getByNumber($number);

            if (!$phoneNumber) {
                $phoneNumber = $this->entityManager->getNewEntity(PhoneNumber::ENTITY_TYPE);

                $phoneNumber->set([
                    'name' => $number,
                    'type' => $hash->{$number}['type'],
                    'optOut' => $hash->{$number}['optOut'],
                    'invalid' => $hash->{$number}['invalid'],
                ]);

                $this->entityManager->saveEntity($phoneNumber);
            } else {
                $skipSave = $this->checkChangeIsForbidden($phoneNumber, $entity);

                if (!$skipSave) {
                    if (
                        $phoneNumber->get('type') != $hash->{$number}['type'] ||
                        $phoneNumber->get('optOut') != $hash->{$number}['optOut'] ||
                        $phoneNumber->get('invalid') != $hash->{$number}['invalid']
                    ) {
                        $phoneNumber->set([
                            'type' => $hash->{$number}['type'],
                            'optOut' => $hash->{$number}['optOut'],
                            'invalid' => $hash->{$number}['invalid'],
                        ]);

                        $this->entityManager->saveEntity($phoneNumber);
                    }
                } else {
                    $revertData[$number] = [
                        'type' => $phoneNumber->getType(),
                        'optOut' => $phoneNumber->isOptedOut(),
                        'invalid' => $phoneNumber->isInvalid(),
                    ];
                }
            }

            $entityPhoneNumber = $this->entityManager->getNewEntity('EntityPhoneNumber');

            $entityPhoneNumber->set([
                'entityId' => $entity->getId(),
                'entityType' => $entity->getEntityType(),
                'phoneNumberId' => $phoneNumber->getId(),
                'primary' => $number === $primary,
                Attribute::DELETED => false,
            ]);

            /** @var BaseMapper $mapper */
            $mapper = $this->entityManager->getMapper();

            $mapper->insertOnDuplicateUpdate($entityPhoneNumber, [
                'primary',
                Attribute::DELETED,
            ]);
        }

        if ($primary) {
            $phoneNumber = $this->getByNumber($primary);

            $entity->set(self::ATTR_PHONE_NUMBER, $primary);

            if ($phoneNumber) {
                $update1 = $this->entityManager
                    ->getQueryBuilder()
                    ->update()
                    ->in('EntityPhoneNumber')
                    ->set(['primary' => false])
                    ->where([
                        'entityId' => $entity->getId(),
                        'entityType' => $entity->getEntityType(),
                        'primary' => true,
                        Attribute::DELETED => false,
                    ])
                    ->build();

                $this->entityManager->getQueryExecutor()->execute($update1);

                $update2 = $this->entityManager
                    ->getQueryBuilder()
                    ->update()
                    ->in('EntityPhoneNumber')
                    ->set(['primary' => true])
                    ->where([
                        'entityId' => $entity->getId(),
                        'entityType' => $entity->getEntityType(),
                        'phoneNumberId' => $phoneNumber->getId(),
                        Attribute::DELETED => false,
                    ])
                    ->build();

                $this->entityManager->getQueryExecutor()->execute($update2);
            }
        }

        if (!empty($revertData)) {
            foreach ($phoneNumberData as $row) {
                if (empty($revertData[$row->phoneNumber])) {
                    continue;
                }

                $row->type = $revertData[$row->phoneNumber]['type'];
                $row->optOut = $revertData[$row->phoneNumber]['optOut'];
                $row->invalid = $revertData[$row->phoneNumber]['invalid'];
            }

            $entity->set(self::ATTR_PHONE_NUMBER_DATA, $phoneNumberData);
        }
    }

    private function storePrimary(Entity $entity): void
    {
        if (!$entity->has(self::ATTR_PHONE_NUMBER)) {
            return;
        }

        $phoneNumberValue = trim($entity->get(self::ATTR_PHONE_NUMBER) ?? '');

        $entityRepository = $this->entityManager->getRDBRepository($entity->getEntityType());

        if (!empty($phoneNumberValue)) {
            if ($phoneNumberValue !== $entity->getFetched(self::ATTR_PHONE_NUMBER)) {
                $this->storePrimaryNotEmpty($phoneNumberValue, $entity);

                return;
            }

            if (
                $entity->has(self::ATTR_PHONE_NUMBER_IS_OPTED_OUT) &&
                (
                    $entity->isNew() ||
                    (
                        $entity->hasFetched(self::ATTR_PHONE_NUMBER_IS_OPTED_OUT) &&
                        $entity->isAttributeChanged(self::ATTR_PHONE_NUMBER_IS_OPTED_OUT)
                    )
                )
            ) {
                $this->markNumberOptedOut($phoneNumberValue, (bool) $entity->get(self::ATTR_PHONE_NUMBER_IS_OPTED_OUT));
            }

            if (
                $entity->has(self::ATTR_PHONE_NUMBER_IS_INVALID) &&
                (
                    $entity->isNew() ||
                    (
                        $entity->hasFetched(self::ATTR_PHONE_NUMBER_IS_INVALID) &&
                        $entity->isAttributeChanged(self::ATTR_PHONE_NUMBER_IS_INVALID)
                    )
                )
            ) {
                $this->markNumberInvalid($phoneNumberValue, (bool) $entity->get(self::ATTR_PHONE_NUMBER_IS_INVALID));
            }

            return;
        }

        $phoneNumberValueOld = $entity->getFetched(self::ATTR_PHONE_NUMBER);

        if (!empty($phoneNumberValueOld)) {
            $phoneNumberOld = $this->getByNumber($phoneNumberValueOld);

            if ($phoneNumberOld) {
                $entityRepository
                    ->getRelation($entity, 'phoneNumbers')
                    ->unrelate($phoneNumberOld, [SaveOption::SKIP_HOOKS => true]);
            }
        }
    }

    private function getByNumber(string $number): ?PhoneNumber
    {
        /** @var PhoneNumberRepository $repository */
        $repository = $this->entityManager->getRepository(PhoneNumber::ENTITY_TYPE);

        return $repository->getByNumber($number);
    }

    private function markNumberOptedOut(string $number, bool $isOptedOut = true): void
    {
        /** @var PhoneNumberRepository $repository */
        $repository = $this->entityManager->getRepository(PhoneNumber::ENTITY_TYPE);

        $repository->markNumberOptedOut($number, $isOptedOut);
    }

    private function markNumberInvalid(string $number, bool $isInvalid = true): void
    {
        /** @var PhoneNumberRepository $repository */
        $repository = $this->entityManager->getRepository(PhoneNumber::ENTITY_TYPE);

        $repository->markNumberInvalid($number, $isInvalid);
    }

    private function checkChangeIsForbidden(PhoneNumber $phoneNumber, Entity $entity): bool
    {
        if (!$this->applicationState->hasUser()) {
            return true;
        }

        $user = $this->applicationState->getUser();

        // @todo Check if not modified by system.

        return !$this->accessChecker->checkEdit($user, $phoneNumber, $entity);
    }

    private function storePrimaryNotEmpty(string $phoneNumberValue, Entity $entity): void
    {
        $entityRepository = $this->entityManager->getRDBRepository($entity->getEntityType());

        $phoneNumberNew = $this->entityManager
            ->getRDBRepository(PhoneNumber::ENTITY_TYPE)
            ->where([
                'name' => $phoneNumberValue,
            ])
            ->findOne();

        if (!$phoneNumberNew) {
            /** @var PhoneNumber $phoneNumberNew */
            $phoneNumberNew = $this->entityManager->getNewEntity(PhoneNumber::ENTITY_TYPE);

            $phoneNumberNew->setNumber($phoneNumberValue);

            if ($entity->has(self::ATTR_PHONE_NUMBER_IS_OPTED_OUT)) {
                $phoneNumberNew->setOptedOut((bool)$entity->get(self::ATTR_PHONE_NUMBER_IS_OPTED_OUT));
            }

            if ($entity->has(self::ATTR_PHONE_NUMBER_IS_INVALID)) {
                $phoneNumberNew->setInvalid((bool)$entity->get(self::ATTR_PHONE_NUMBER_IS_INVALID));
            }

            $defaultType = $this->metadata
                ->get("entityDefs.{$entity->getEntityType()}.fields.phoneNumber.defaultType");

            $phoneNumberNew->setType($defaultType);

            $this->entityManager->saveEntity($phoneNumberNew);
        }

        $phoneNumberValueOld = $entity->getFetched(self::ATTR_PHONE_NUMBER);

        if (!empty($phoneNumberValueOld)) {
            $phoneNumberOld = $this->getByNumber($phoneNumberValueOld);

            if ($phoneNumberOld) {
                $entityRepository
                    ->getRelation($entity, 'phoneNumbers')
                    ->unrelate($phoneNumberOld, [SaveOption::SKIP_HOOKS => true]);
            }
        }

        $entityRepository
            ->getRelation($entity, 'phoneNumbers')
            ->relate($phoneNumberNew, null, [SaveOption::SKIP_HOOKS => true]);

        if ($entity->has(self::ATTR_PHONE_NUMBER_IS_OPTED_OUT)) {
            $this->markNumberOptedOut($phoneNumberValue, (bool)$entity->get(self::ATTR_PHONE_NUMBER_IS_OPTED_OUT));
        }

        if ($entity->has(self::ATTR_PHONE_NUMBER_IS_INVALID)) {
            $this->markNumberInvalid($phoneNumberValue, (bool)$entity->get(self::ATTR_PHONE_NUMBER_IS_INVALID));
        }

        $update = $this->entityManager
            ->getQueryBuilder()
            ->update()
            ->in('EntityPhoneNumber')
            ->set(['primary' => true])
            ->where([
                'entityId' => $entity->getId(),
                'entityType' => $entity->getEntityType(),
                'phoneNumberId' => $phoneNumberNew->getId(),
            ])
            ->build();

        $this->entityManager->getQueryExecutor()->execute($update);
    }
}
