<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2026 EspoCRM, Inc.
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

namespace Espo\Classes\FieldValidators\Common\LinkMultiple;

use Espo\Core\Field\LinkMultiple;
use Espo\Core\FieldValidation\Validator;
use Espo\Core\FieldValidation\Validator\Data;
use Espo\Core\FieldValidation\Validator\Failure;
use Espo\Core\ORM\Entity as CoreEntity;
use Espo\ORM\Defs;
use Espo\ORM\Entity;
use Espo\ORM\EntityManager;
use Espo\ORM\Name\Attribute;
use Espo\Tools\DynamicLogic\CascadingFields\ItemsProvider;
use Espo\Tools\DynamicLogic\CascadingFields\ValidationHelper;

/**
 * @implements Validator<Entity>
 */
class CascadingSelect implements Validator
{
    public function __construct(
        private EntityManager $entityManager,
        private Defs $defs,
        private ValidationHelper $helper,
        private ItemsProvider $itemsProvider,
    ) {}

    public function validate(Entity $entity, string $field, Data $data): ?Failure
    {
        if (!$entity instanceof CoreEntity) {
            return null;
        }

        $items = $this->itemsProvider->get($entity->getEntityType(), $field);

        if (!$items) {
            return null;
        }

        $linkValue = $entity->getValueObject($field);

        if (!$linkValue instanceof LinkMultiple || !$linkValue->getIdList()) {
            return null;
        }

        $entityType = $this->defs
            ->getEntity($entity->getEntityType())
            ->tryGetRelation($field)
            ?->tryGetForeignEntityType();

        if (!$entityType) {
            return null;
        }

        $valueEntities = $this->entityManager
            ->getRDBRepository($entityType)
            ->where([
                Attribute::ID => $linkValue->getIdList(),
            ])
            ->find();

        if (!count($valueEntities)) {
            return null;
        }

        foreach ($valueEntities as $valueEntity) {
            if (!$valueEntity instanceof CoreEntity) {
                continue;
            }

            foreach ($items as $item) {
                $itemFailure = $this->helper->validateItem($entity, $valueEntity, $item);

                if ($itemFailure) {
                    return $itemFailure;
                }
            }
        }

        return null;
    }
}
