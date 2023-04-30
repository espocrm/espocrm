<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2023 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

namespace Espo\Tools\Currency\Conversion;

use Espo\Core\Acl;
use Espo\Core\Acl\Table;
use Espo\Core\Currency\Converter;
use Espo\Core\Currency\Rates;
use Espo\Core\Field\Currency;
use Espo\Core\ORM\Entity as CoreEntity;
use Espo\Entities\User;
use Espo\ORM\Entity;
use Espo\ORM\EntityManager;
use LogicException;

/**
 * @implements EntityConverter<CoreEntity>
 */
class DefaultEntityConverter implements EntityConverter
{
    public function __construct(
        private Converter $converter,
        private EntityManager $entityManager,
        private Acl $acl,
        private User $user
    ) {}

    /**
     * @param CoreEntity $entity
     */
    public function convert(Entity $entity, string $targetCurrency, Rates $rates): void
    {
        $entityDefs = $this->entityManager
            ->getDefs()
            ->getEntity($entity->getEntityType());

        foreach ($this->getFieldList($entity->getEntityType()) as $field) {
            $disabled = $entityDefs->getField($field)->getParam('conversionDisabled');

            if ($disabled) {
                continue;
            }

            $value = $entity->getValueObject($field);

            if (!$value) {
                continue;
            }

            if (!$value instanceof Currency) {
                throw new LogicException();
            }

            if ($targetCurrency === $value->getCode()) {
                continue;
            }

            $convertedValue = $this->converter->convertWithRates($value, $targetCurrency, $rates);

            $entity->setValueObject($field, $convertedValue);
        }

        $this->entityManager->saveEntity($entity, ['modifiedById' => $this->user->getId()]);
    }

    /**
     * @return string[]
     */
    private function getFieldList(string $entityType): array
    {
        $resultList = [];

        $fieldDefsList = $this->entityManager
            ->getDefs()
            ->getEntity($entityType)
            ->getFieldList();

        foreach ($fieldDefsList as $fieldDefs) {
            $field = $fieldDefs->getName();
            $type = $fieldDefs->getType();

            if ($type !== 'currency') {
                continue;
            }

            if (!$this->acl->checkField($entityType, $field, Table::ACTION_EDIT)) {
                continue;
            }

            $resultList[] = $field;
        }

        return $resultList;
    }
}
