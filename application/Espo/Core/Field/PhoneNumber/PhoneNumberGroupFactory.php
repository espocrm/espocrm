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

namespace Espo\Core\Field\PhoneNumber;

use Espo\Repositories\PhoneNumber as Repository;

use Espo\ORM\{
    EntityManager,
    Entity,
    Value\ValueFactory,
};

use Espo\Core\{
    Utils\Metadata,
    Field\PhoneNumberGroup,
    Field\PhoneNumber,
};

use RuntimeException;

/**
 * A phone number group factory.
 */
class PhoneNumberGroupFactory implements ValueFactory
{
    private $metadata;

    private $entityManager;

    /**
     * @todo Use OrmDefs instead of Metadata.
     */
    public function __construct(Metadata $metadata, EntityManager $entityManager)
    {
        $this->metadata = $metadata;
        $this->entityManager = $entityManager;
    }

    public function isCreatableFromEntity(Entity $entity, string $field): bool
    {
        $type = $this->metadata->get([
            'entityDefs', $entity->getEntityType(), 'fields', $field, 'type'
        ]);

        if ($type !== 'phone') {
            return false;
        }

        return true;
    }

    public function createFromEntity(Entity $entity, string $field): PhoneNumberGroup
    {
        if (!$this->isCreatableFromEntity($entity, $field)) {
            throw new RuntimeException();
        }

        $phoneNumberList = [];

        $primaryPhoneNumber = null;

        $dataList = null;

        $dataAttribute = $field . 'Data';

        if ($entity->has($dataAttribute)) {
            $dataList = $this->sanitizeDataList(
                $entity->get($dataAttribute)
            );
        }

        if (!$dataList && $entity->has($field) && !$entity->get($field)) {
            $dataList = [];
        }

        if (!$dataList) {
            /** @var Repository $repository */
            $repository = $this->entityManager->getRepository('PhoneNumber');

            $dataList = $repository->getPhoneNumberData($entity);
        }

        foreach ($dataList as $item) {
            $phoneNumber = PhoneNumber::create($item->phoneNumber);

            if ($item->type) {
                $phoneNumber = $phoneNumber->withType($item->type);
            }

            if ($item->optOut) {
                $phoneNumber = $phoneNumber->optedOut();
            }

            if ($item->invalid) {
                $phoneNumber = $phoneNumber->invalid();
            }

            if ($item->primary) {
                $primaryPhoneNumber = $phoneNumber;
            }

            $phoneNumberList[] = $phoneNumber;
        }

        $group = PhoneNumberGroup::create($phoneNumberList);

        if ($primaryPhoneNumber) {
            $group = $group->withPrimary($primaryPhoneNumber);
        }

        return $group;
    }

    private function sanitizeDataList(array $dataList): array
    {
        $sanitizedDataList = [];

        foreach ($dataList as $item) {
            if (is_array($item)) {
                $sanitizedDataList[] = (object) $item;

                continue;
            }

            if (!is_object($item)) {
                throw new RuntimeException("Bad data.");
            }

            $sanitizedDataList[] = $item;
        }

        return $sanitizedDataList;
    }
}
