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

namespace Espo\Core\Field\EmailAddress;

use Espo\Repositories\EmailAddress as Repository;

use Espo\ORM\{
    EntityManager,
    Entity,
    Value\ValueFactory,
};

use Espo\Core\{
    Utils\Metadata,
    Field\EmailAddressGroup,
    Field\EmailAddress,
};

use RuntimeException;

/**
 * An email address group factory.
 */
class EmailAddressGroupFactory implements ValueFactory
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

        if ($type !== 'email') {
            return false;
        }

        return true;
    }

    public function createFromEntity(Entity $entity, string $field): EmailAddressGroup
    {
        if (!$this->isCreatableFromEntity($entity, $field)) {
            throw new RuntimeException();
        }

        $emailAddressList = [];

        $primaryEmailAddress = null;

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
            $repository = $this->entityManager->getRepository('EmailAddress');

            $dataList = $repository->getEmailAddressData($entity);
        }

        foreach ($dataList as $item) {
            $emailAddress = EmailAddress::create($item->emailAddress);

            if ($item->optOut) {
                $emailAddress = $emailAddress->optedOut();
            }

            if ($item->invalid) {
                $emailAddress = $emailAddress->invalid();
            }

            if ($item->primary) {
                $primaryEmailAddress = $emailAddress;
            }

            $emailAddressList[] = $emailAddress;
        }

        $group = EmailAddressGroup::create($emailAddressList);

        if ($primaryEmailAddress) {
            $group = $group->withPrimary($primaryEmailAddress);
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
