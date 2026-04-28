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

namespace tests\unit\Espo\Core\Mail\Account;

use Espo\Core\Field\Date;
use Espo\Core\Field\DateTime;
use Espo\Core\Mail\Account\Account;
use Espo\Core\Mail\Account\FetchData;
use Espo\Core\Mail\Account\Fetcher;
use Espo\Core\Mail\Account\Fetcher\ConfigDataProvider;
use Espo\Core\Mail\Account\Storage;
use Espo\Core\Mail\Account\StorageFactory;
use Espo\Core\Mail\Importer;
use Espo\Core\Mail\ParserFactory;
use Espo\Core\Utils\Log;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FetcherTest extends TestCase
{
    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testFetchSinceDate(): void
    {
        $storage = $this->createMock(Storage::class);
        $importer = $this->createMock(Importer::class);

        $fetcher = $this->prepareFetcher($storage, $importer);

        $fetchData = $this->prepareFetchData(
            lastUid: null,
            lastDate: null,
            uidValidity: null,
        );

        $account = $this->prepareAccount($fetchData);

        $storage
            ->expects($this->once())
            ->method('selectFolder')
            ->with('INBOX');

        $storage
            ->expects($this->once())
            ->method('getFolderStatus')
            ->willReturn(new Storage\FolderStatus(uidValidity: 1));

        $storage
            ->expects($this->once())
            ->method('getUidsSinceDate')
            ->with(DateTime::fromString('2026-01-01 00:00'))
            ->willReturn([1, 2, 3]);

        $storage
            ->expects($this->once())
            ->method('close');

        $importer
            ->expects($this->exactly(3))
            ->method('import');

        $fetchData
            ->expects($this->once())
            ->method('setUidValidity')
            ->with('INBOX', 1);

        $fetchData
            ->expects($this->once())
            ->method('setLastUid')
            ->with('INBOX', 3);

        $fetcher->fetch($account);
    }

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testFetchSinceLastUid(): void
    {
        $storage = $this->createMock(Storage::class);
        $importer = $this->createMock(Importer::class);

        $fetcher = $this->prepareFetcher($storage, $importer);

        $fetchData = $this->prepareFetchData(
            lastUid: 3,
            lastDate: DateTime::fromString('2026-01-02 00:00'),
            uidValidity: 1,
        );

        $account = $this->prepareAccount($fetchData);

        $storage
            ->expects($this->once())
            ->method('selectFolder')
            ->with('INBOX');

        $storage
            ->expects($this->once())
            ->method('getFolderStatus')
            ->willReturn(new Storage\FolderStatus(uidValidity: 1));

        $storage
            ->expects($this->once())
            ->method('getUidsFromUid')
            ->with(3)
            ->willReturn([4]);

        $storage
            ->expects($this->once())
            ->method('close');

        $importer
            ->expects($this->exactly(1))
            ->method('import');

        $fetchData
            ->expects($this->once())
            ->method('setLastUid')
            ->with('INBOX', 4);

        $fetcher->fetch($account);
    }

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testFetchUidValidityBroken(): void
    {
        $storage = $this->createMock(Storage::class);
        $importer = $this->createMock(Importer::class);

        $fetcher = $this->prepareFetcher($storage, $importer);

        $fetchData = $this->prepareFetchData(
            lastUid: 3,
            lastDate: DateTime::fromString('2026-01-02 00:00'),
            uidValidity: 1,
        );

        $account = $this->prepareAccount($fetchData);

        $storage
            ->expects($this->once())
            ->method('selectFolder')
            ->with('INBOX');

        $storage
            ->expects($this->once())
            ->method('getFolderStatus')
            ->willReturn(new Storage\FolderStatus(uidValidity: 2));

        $storage
            ->expects($this->once())
            ->method('getUidsSinceDate')
            ->with(DateTime::fromString('2026-01-02 00:00'))
            ->willReturn([4]);

        $storage
            ->expects($this->once())
            ->method('close');

        $importer
            ->expects($this->exactly(1))
            ->method('import');

        $fetchData
            ->expects($this->once())
            ->method('setUidValidity')
            ->with('INBOX', 2);

        $fetchData
            ->expects($this->once())
            ->method('setLastUid')
            ->with('INBOX', 4);

        $fetcher->fetch($account);
    }

    private function prepareFetchData(
        ?int $lastUid,
        ?DateTime $lastDate,
        ?int $uidValidity,
    ): FetchData & MockObject {

        $fetchData = $this->createMock(FetchData::class);

        $fetchData
            ->method('getLastUid')
            ->willReturn($lastUid);

        $fetchData
            ->method('getLastDate')
            ->willReturn($lastDate);

        $fetchData
            ->method('getUidValidity')
            ->willReturn($uidValidity);

        $fetchData
            ->method('getForceByDate')
            ->willReturn(false);

        return $fetchData;
    }

    private function prepareAccount(FetchData $fetchData): Account & MockObject
    {
        $account = $this->createMock(Account::class);

        $account
            ->method('isAvailableForFetching')
            ->willReturn(true);

        $account
            ->method('getMonitoredFolderList')
            ->willReturn(['INBOX']);

        $account
            ->expects($this->once())
            ->method('getPortionLimit')
            ->willReturn(5);

        $account
            ->expects($this->once())
            ->method('getFetchData')
            ->willReturn($fetchData);

        $account
            ->method('getFetchSince')
            ->willReturn(Date::fromString('2026-01-01'));

        $account
            ->expects($this->once())
            ->method('updateFetchData')
            ->with($fetchData);

        return $account;
    }

    private function prepareFetcher(
        Storage & MockObject $storage,
        Importer & MockObject $importer,
    ): Fetcher {

        $storageFactory = $this->createMock(StorageFactory::class);
        $configDataProvider = $this->createMock(ConfigDataProvider::class);
        $log = $this->createMock(Log::class);
        $parserFactory = $this->createMock(ParserFactory::class);
        $filtersProvider = $this->createMock(Fetcher\FiltersProvider::class);
        $unlocker = $this->createMock(Fetcher\Unlocker::class);
        $messageFactory = $this->createMock(Fetcher\MessageFactory::class);

        $storageFactory
            ->expects($this->once())
            ->method('create')
            ->willReturn($storage);

        return new Fetcher(
            importer: $importer,
            storageFactory: $storageFactory,
            configDataProvider: $configDataProvider,
            log: $log,
            parserFactory: $parserFactory,
            filtersProvider: $filtersProvider,
            unlocker: $unlocker,
            messageFactory: $messageFactory,
        );
    }
}
