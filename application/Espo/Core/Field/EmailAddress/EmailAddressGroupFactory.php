<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

namespace Espo\Core\Field\EmailAddress;

use Espo\Core\ORM\Type\FieldType;
use Espo\Entities\EmailAddress as EmailAddressEntity;
use Espo\ORM\Defs\Params\FieldParam;
use Espo\Repositories\EmailAddress as Repository;

use Espo\ORM\Entity;
use Espo\ORM\EntityManager;
use Espo\ORM\Value\ValueFactory;

use Espo\Core\Field\EmailAddress;
use Espo\Core\Field\EmailAddressGroup;
use Espo\Core\Utils\Metadata;

use RuntimeException;
use stdClass;

/**
 * An email address group factory.
 */
class EmailAddressGroupFactory implements ValueFactory
{
    private Metadata $metadata;
    private EntityManager $entityManager;

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
        $type = $this->metadata->get(['entityDefs', $entity->getEntityType(), 'fields', $field, FieldParam::TYPE]);

        if ($type !== FieldType::EMAIL) {
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
            $repository = $this->entityManager->getRepository(EmailAddressEntity::ENTITY_TYPE);

            $dataList = $repository->getEmailAddressData($entity);
        }

        foreach ($dataList as $item) {
            $emailAddress = EmailAddress::create($item->emailAddress);

            if ($item->optOut ?? false) {
                $emailAddress = $emailAddress->optedOut();
            }

            if ($item->invalid ?? false) {
                $emailAddress = $emailAddress->invalid();
            }

            if ($item->primary ?? false) {
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

    /**
     * @param array<int, array<string, mixed>|stdClass> $dataList
     * @return stdClass[]
     */
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
