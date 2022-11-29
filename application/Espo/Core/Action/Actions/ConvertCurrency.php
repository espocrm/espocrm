<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2022 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

namespace Espo\Core\Action\Actions;

use Espo\Core\Acl;
use Espo\Core\Action\Action;
use Espo\Core\Action\Data;
use Espo\Core\Action\Params;
use Espo\Core\Currency\ConfigDataProvider as CurrencyConfigDataProvider;
use Espo\Core\Currency\Converter as CurrencyConverter;
use Espo\Core\Currency\Rates as CurrencyRates;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\NotFound;
use Espo\Core\Field\Currency;
use Espo\Core\ORM\EntityManager;
use Espo\Core\Utils\FieldUtil;
use Espo\Core\Utils\Metadata;
use Espo\ORM\Entity;

class ConvertCurrency implements Action
{
    public function __construct(
        private Acl $acl,
        private EntityManager $entityManager,
        private FieldUtil $fieldUtil,
        private Metadata $metadata,
        private CurrencyConfigDataProvider $configDataProvider,
        private CurrencyConverter $currencyConverter
    ) {}

    public function process(Params $params, Data $data): void
    {
        $entityType = $params->getEntityType();
        $id = $params->getId();

        if (!$this->acl->checkScope($entityType, Acl\Table::ACTION_EDIT)) {
            throw new Forbidden();
        }

        $fieldList = $this->getFieldList($entityType, $data);

        if (empty($fieldList)) {
            throw new Forbidden("No fields to convert.");
        }

        $baseCurrency = $this->configDataProvider->getBaseCurrency();

        $targetCurrency = $data->get('targetCurrency');

        if (!$targetCurrency) {
            throw new BadRequest("No target currency.");
        }

        $rates =
            $this->getRatesFromData($data) ??
            $this->configDataProvider->getCurrencyRates();

        if ($targetCurrency !== $baseCurrency && !$rates->hasRate($targetCurrency)) {
            throw new BadRequest("Target currency rate is not specified.");
        }

        $entity = $this->entityManager->getEntityById($entityType, $id);

        if (!$entity) {
            throw new NotFound();
        }

        if (!$this->acl->checkEntityEdit($entity)) {
            throw new Forbidden();
        }

        $this->convertEntity($entity, $fieldList, $targetCurrency, $rates);
    }

    /**
     * @param string[] $fieldList
     */
    protected function convertEntity(
        Entity $entity,
        array $fieldList,
        string $targetCurrency,
        CurrencyRates $rates
    ): void {

        $entityDefs = $this->entityManager
            ->getDefs()
            ->getEntity($entity->getEntityType());

        foreach ($fieldList as $field) {
            $disabled = $entityDefs->getField($field)->getParam('conversionDisabled');

            if ($disabled) {
                continue;
            }

            $amount = $entity->get($field);
            $code = $entity->get($field . 'Currency');

            if ($amount === null) {
                continue;
            }

            if ($targetCurrency === $code) {
                continue;
            }

            $value = new Currency($amount, $code);

            $convertedValue = $this->currencyConverter->convertWithRates($value, $targetCurrency, $rates);

            $entity->set($field, $convertedValue->getAmount());
            $entity->set($field . 'Currency', $convertedValue->getCode());
        }

        $this->entityManager->saveEntity($entity);
    }

    protected function getRatesFromData(Data $data): ?CurrencyRates
    {
        if ($data->get('rates') === null) {
            return null;
        }

        $baseCurrency = $this->configDataProvider->getBaseCurrency();

        $ratesArray = get_object_vars($data->get('rates'));

        $ratesArray[$baseCurrency] = 1.0;

        return CurrencyRates::fromArray($ratesArray, $baseCurrency);
    }

    /**
     * @return string[]
     */
    protected function getFieldList(string $entityType, Data $data): array
    {
        $forbiddenFieldList = $this->acl->getScopeForbiddenFieldList($entityType, 'edit');

        $resultList = [];

        $fieldList = $data->get('fieldList') ?? $this->fieldUtil->getEntityTypeFieldList($entityType);

        foreach ($fieldList as $field) {
            $type = $this->metadata->get(['entityDefs', $entityType, 'fields', $field, 'type']);

            if ($type !== 'currency') {
                continue;
            }

            if (in_array($field, $forbiddenFieldList)) {
                continue;
            }

            $resultList[] = $field;
        }

        return $resultList;
    }
}
