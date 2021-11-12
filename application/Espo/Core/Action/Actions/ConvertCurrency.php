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

namespace Espo\Core\Action\Actions;

use Espo\Core\{
    Exceptions\Forbidden,
    Exceptions\BadRequest,
    Exceptions\NotFound,
    Action\Action,
    Action\Params,
    Action\Data,
    Acl,
    ORM\EntityManager,
    Utils\FieldUtil,
    Utils\Metadata,
    Field\Currency\CurrencyConfigDataProvider,
    Field\Currency\CurrencyConverter,
    Field\Currency,
    Field\Currency\CurrencyRates,
};

use Espo\{
    ORM\Entity,
};

class ConvertCurrency implements Action
{
    private $acl;

    private $entityManager;

    private $fieldUtil;

    private $metadata;

    private $configDataProvider;

    private $currencyConverter;

    public function __construct(
        Acl $acl,
        EntityManager $entityManager,
        FieldUtil $fieldUtil,
        Metadata $metadata,
        CurrencyConfigDataProvider $configDataProvider,
        CurrencyConverter $currencyConverter
    ) {
        $this->acl = $acl;
        $this->entityManager = $entityManager;
        $this->fieldUtil = $fieldUtil;
        $this->metadata = $metadata;
        $this->configDataProvider = $configDataProvider;
        $this->currencyConverter = $currencyConverter;
    }

    public function process(Params $params, Data $data): void
    {
        $entityType = $params->getEntityType();
        $id = $params->getId();

        if (!$this->acl->checkScope($entityType, 'edit')) {
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

        $entity = $this->entityManager->getEntity($entityType, $id);

        if (!$entity) {
            throw new NotFound();
        }

        if (!$this->acl->checkEntity($entity, 'edit')) {
            throw new Forbidden();
        }

        $this->convertEntity($entity, $fieldList, $targetCurrency, $rates);
    }

    protected function convertEntity(
        Entity $entity,
        array $fieldList,
        string $targetCurrency,
        CurrencyRates $rates
    ) {
        foreach ($fieldList as $field) {
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

        return CurrencyRates::fromArray($ratesArray);
    }

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
