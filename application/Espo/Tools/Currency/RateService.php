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

namespace Espo\Tools\Currency;

use Espo\Core\Acl\Table;
use Espo\Core\Currency\ConfigDataProvider;
use Espo\Core\Currency\Rates;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Acl;
use Espo\Core\Utils\Currency\DatabasePopulator;
use Espo\Core\Utils\DateTime;
use Espo\ORM\EntityManager;
use RuntimeException;

class RateService
{
    private const string SCOPE = 'Currency';

    public function __construct(
        private Acl $acl,
        private DatabasePopulator $databasePopulator,
        private ConfigDataProvider $configDataProvider,
        private SyncManager $syncManager,
        private RateEntryProvider $rateEntryProvider,
        private DateTime $dateTime,
        private EntityManager $entityManager,
    ) {}

    /**
     * @throws Forbidden
     */
    public function get(): Rates
    {
        $this->checkReadAccess();

        $rates = Rates::create($this->configDataProvider->getBaseCurrency());

        foreach ($this->configDataProvider->getCurrencyList() as $code) {
            $rates = $rates->withRate($code, $this->configDataProvider->getCurrencyRate($code));
        }

        return $rates;
    }

    /**
     * @throws BadRequest
     * @throws Forbidden
     */
    public function set(Rates $rates): void
    {
        $this->checkEditAccess();

        $codeList = $this->configDataProvider->getCurrencyList();
        $base = $this->configDataProvider->getBaseCurrency();

        foreach ($rates->toAssoc() as $code => $value) {
            if ($value < 0) {
                throw new BadRequest("Bad value.");
            }

            if (!in_array($code, $codeList) || $code === $base) {
                continue;
            }

            $this->writeOne($code, $value);
        }

        $this->syncManager->refreshCache();
        $this->databasePopulator->process();
    }

    private function writeOne(string $code, float $value): void
    {
        $date = $this->dateTime->getToday();

        try {
            $rateEntry = $this->rateEntryProvider->getRateEntryOnDate($code, $date) ??
                $this->rateEntryProvider->prepareNew($code, $date);
        } catch (Exceptions\NotEnabled $e) {
            throw new RuntimeException($e->getMessage(), previous: $e);
        }

        $rateEntry->setRate((string) $value);

        $this->entityManager->saveEntity($rateEntry);
    }

    /**
     * @throws Forbidden
     */
    private function checkReadAccess(): void
    {
        if (!$this->acl->check(self::SCOPE)) {
            throw new Forbidden();
        }

        if ($this->acl->getLevel(self::SCOPE, Table::ACTION_READ) !== Table::LEVEL_YES) {
            throw new Forbidden();
        }
    }

    /**
     * @throws Forbidden
     */
    private function checkEditAccess(): void
    {
        if (!$this->acl->check(self::SCOPE)) {
            throw new Forbidden();
        }

        if ($this->acl->getLevel(self::SCOPE, Table::ACTION_EDIT) !== Table::LEVEL_YES) {
            throw new Forbidden();
        }
    }
}
