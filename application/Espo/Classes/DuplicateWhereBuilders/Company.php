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

namespace Espo\Classes\DuplicateWhereBuilders;

use Espo\Core\Duplicate\WhereBuilder;
use Espo\Core\Field\EmailAddressGroup;
use Espo\Core\ORM\Entity as CoreEntity;

use Espo\ORM\{
    Query\Part\Condition as Cond,
    Query\Part\WhereItem,
    Query\Part\Where\OrGroup,
    Entity,
};

class Company implements WhereBuilder
{
    public function build(Entity $entity): ?WhereItem
    {
        assert($entity instanceof CoreEntity);

        $orBuilder = OrGroup::createBuilder();

        $toCheck = false;

        if ($entity->get('name')) {
            $orBuilder->add(
                Cond::equal(
                    Cond::column('name'),
                    $entity->get('name')
                ),
            );

            $toCheck = true;
        }

        if (
            ($entity->get('emailAddress') || $entity->get('emailAddressData')) &&
            (
                $entity->isNew() ||
                $entity->isAttributeChanged('emailAddress') ||
                $entity->isAttributeChanged('emailAddressData')
            )
        ) {
            foreach ($this->getEmailAddressList($entity) as $emailAddress) {
                $orBuilder->add(
                    Cond::equal(
                        Cond::column('emailAddress'),
                        $emailAddress
                    )
                );

                $toCheck = true;
            }
        }

        if (!$toCheck) {
            return null;
        }

        return $orBuilder->build();
    }

    private function getEmailAddressList(CoreEntity $entity): array
    {
        if ($entity->get('emailAddressData')) {
            /** @var EmailAddressGroup $eaGroup */
            $eaGroup = $entity->getValueObject('emailAddress');

            return $eaGroup->getAddressList();
        }

        if ($entity->get('emailAddress')) {
            return [
                $entity->get('emailAddress')
            ];
        }

        return [];
    }
}
