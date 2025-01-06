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

namespace Espo\Modules\Crm\Tools\Activities;

use Espo\Core\Acl;
use Espo\Core\Field\EmailAddress;
use Espo\Core\Templates\Entities\Company;
use Espo\Core\Templates\Entities\Person;
use Espo\Core\Utils\Metadata;
use Espo\Modules\Crm\Entities\Account;
use Espo\Modules\Crm\Entities\Contact;
use Espo\Modules\Crm\Entities\Lead;
use Espo\ORM\Defs\RelationDefs;
use Espo\ORM\Entity;
use Espo\ORM\EntityManager;
use Espo\ORM\Type\RelationType;
use Espo\Tools\Email\EmailAddressEntityPair;

class ComposeEmailService
{
    public function __construct(
        private EntityManager $entityManager,
        private  Metadata $metadata,
        private Acl $acl
    ) {}

    /**
     * @return EmailAddressEntityPair[]
     */
    public function getEmailAddressList(Entity $entity): array
    {
        $relations = $this->getRelations($entity);

        foreach ($relations as $relation) {
            $address = $this->getPersonEmailAddress($entity, $relation->getName());

            if ($address) {
                return [$address];
            }
        }

        return [];
    }

    private function getPersonEmailAddress(Entity $entity, string $link): ?EmailAddressEntityPair
    {
        $foreignEntity = $this->entityManager
            ->getRDBRepository($entity->getEntityType())
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

    /**
     * @return RelationDefs[]
     */
    private function getRelations(Entity $entity): array
    {
        $relations = $this->entityManager
            ->getDefs()
            ->getEntity($entity->getEntityType())
            ->getRelationList();

        $targetRelations = [];

        foreach ($relations as $relation) {
            if (
                $relation->getType() !== RelationType::BELONGS_TO &&
                $relation->getType() !== RelationType::HAS_ONE
            ) {
                continue;
            }

            $foreignEntityType = $relation->getForeignEntityType();

            if (
                $foreignEntityType !== Account::ENTITY_TYPE &&
                $foreignEntityType !== Contact::ENTITY_TYPE &&
                $foreignEntityType !== Lead::ENTITY_TYPE &&
                $this->metadata->get("scopes.$foreignEntityType.type") !== Person::TEMPLATE_TYPE &&
                $this->metadata->get("scopes.$foreignEntityType.type") !== Company::TEMPLATE_TYPE
            ) {
                continue;
            }

            $targetRelations[] = $relation;
        }

        return $this->sortRelations($targetRelations);
    }

    /**
     * @param RelationDefs[] $targetRelations
     * @return RelationDefs[]
     */
    private function sortRelations(array $targetRelations): array
    {
        usort($targetRelations, function (RelationDefs $a, RelationDefs $b) {
            $entityTypeList = [
                Account::ENTITY_TYPE,
                Contact::ENTITY_TYPE,
                Lead::ENTITY_TYPE,
            ];

            $index1 = array_search($a->getForeignEntityType(), $entityTypeList);
            $index2 = array_search($b->getForeignEntityType(), $entityTypeList);

            if ($index1 !== false && $index2 === false) {
                return -1;
            }

            if ($index1 === false && $index2 !== false) {
                return 1;
            }

            if ($index1 !== false && $index2 !== false && $index1 !== $index2) {
                return $index1 - $index2;
            }

            return 0;
        });

        return $targetRelations;
    }
}
