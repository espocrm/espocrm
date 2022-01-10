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

namespace Espo\Core\Mail\Account;

use Espo\Core\Exceptions\Error;

use Espo\Core\Mail\Account\Storage\Flag;
use Espo\Core\Mail\Account\Storage;
use Espo\Core\Mail\Importer;
use Espo\Core\Mail\Importer\Data as ImporterData;
use Espo\Core\Mail\ParserFactory;
use Espo\Core\Mail\MessageWrapper;
use Espo\Core\Mail\Account\Hook\BeforeFetch as BeforeFetchHook;
use Espo\Core\Mail\Account\Hook\AfterFetch as AfterFetchHook;
use Espo\Core\Mail\Account\Hook\BeforeFetchResult as BeforeFetchHookResult;

use Espo\Core\Utils\DateTime as DateTimeUtil;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\Log;
use Espo\Core\Field\DateTime as DateTimeField;

use Espo\Entities\EmailFilter;
use Espo\Entities\Email;

use Espo\ORM\Collection;
use Espo\ORM\EntityManager;

use Throwable;
use DateTime;

class Fetcher
{
    private Importer $importer;

    private StorageFactory $storageFactory;

    private Config $config;

    private Log $log;

    private EntityManager $entityManager;

    private ParserFactory $parserFactory;

    private ?BeforeFetchHook $beforeFetchHook;

    private ?AfterFetchHook $afterFetchHook;

    public function __construct(
        Importer $importer,
        StorageFactory $storageFactory,
        Config $config,
        Log $log,
        EntityManager $entityManager,
        ParserFactory $parserFactory,
        ?BeforeFetchHook $beforeFetchHook,
        ?AfterFetchHook $afterFetchHook
    ) {
        $this->importer = $importer;
        $this->storageFactory = $storageFactory;
        $this->config = $config;
        $this->log = $log;
        $this->entityManager = $entityManager;
        $this->parserFactory = $parserFactory;
        $this->beforeFetchHook = $beforeFetchHook;
        $this->afterFetchHook = $afterFetchHook;
    }

    public function fetch(Account $account): void
    {
        if (!$account->isAvailableForFetching()) {
            throw new Error("{$account->getEntityType()} {$account->getId()} is not active.");
        }

        $monitoredFolderList = $account->getMonitoredFolderList();

        if (count($monitoredFolderList) === 0) {
            return;
        }

        $filterList = $this->getFilterList($account);

        $storage = $this->storageFactory->create($account);

        foreach ($monitoredFolderList as $folder) {
            $this->fetchFolder($account, $folder, $storage, $filterList);
        }

        $storage->close();
    }

    /**
     * @param Collection<EmailFilter> $filterList
     */
    private function fetchFolder(
        Account $account,
        string $folderOriginal,
        Storage $storage,
        Collection $filterList
    ): void {

        $fetchData = $account->getFetchData();

        $folder = mb_convert_encoding($folderOriginal, 'UTF7-IMAP', 'UTF-8');

        try {
            $storage->selectFolder($folderOriginal);
        }
        catch (Throwable $e) {
            $this->log->error(
                "{$account->getEntityType()} {$account->getId()}, " .
                "could not select folder '{$folder}'; [{$e->getCode()}] {$e->getMessage()}"
            );

            return;
        }

        $lastUID = $fetchData->lastUID->$folder ?? 0;
        $lastDate = $fetchData->lastDate->$folder ?? 0;
        $forceByDate = !empty($fetchData->byDate->$folder);

        $portionLimit = $forceByDate ? 0 : $account->getPortionLimit();

        $previousLastUID = $lastUID;

        $idList = $this->getIdList($account, $storage, $lastUID, $lastDate, $forceByDate);

        if (count($idList) === 1 && !empty($lastUID)) {
            if ($storage->getUniqueId($idList[0]) === $lastUID) {
                return;
            }
        }

        $counter = 0;

        foreach ($idList as $id) {
            if ($counter == count($idList) - 1) {
                $lastUID = $storage->getUniqueId($id);
            }

            if ($forceByDate && $previousLastUID) {
                $uid = $storage->getUniqueId($id);

                if ($uid <= $previousLastUID) {
                    $counter++;

                    continue;
                }
            }

            $email = $this->fetchEmail($account, $storage, $id, $filterList);

            $isLast = $counter === count($idList) - 1;
            $isLastInPortion = $counter === $portionLimit - 1;

            if ($isLast || $isLastInPortion) {
                $lastUID = $storage->getUniqueId($id);

                if ($email && $email->getDateSent()) {
                    $dt = $email->getDateSent()->getDateTime();

                    $nowDt = new DateTime();

                    if ($dt->getTimestamp() >= $nowDt->getTimestamp()) {
                        $dt = $nowDt;
                    }

                    $lastDate = $dt->format(DateTimeUtil::SYSTEM_DATE_TIME_FORMAT);
                }

                break;
            }

            $counter++;
        }

        if ($forceByDate) {
            $nowDt = new DateTime();

            $lastDate = $nowDt->format(DateTimeUtil::SYSTEM_DATE_TIME_FORMAT);
        }

        $fetchData->lastDate->$folder = $lastDate;
        $fetchData->lastUID->$folder = $lastUID;

        if ($forceByDate) {
            if ($previousLastUID) {
                $idList = $storage->getIdsFromUniqueId($previousLastUID);

                if (count($idList)) {
                    $uid1 = $storage->getUniqueId($idList[0]);

                    if ($uid1 && $uid1 > $previousLastUID) {
                        unset($fetchData->byDate->$folder);
                    }
                }
            }
        }
        else if ($previousLastUID && count($idList) && $previousLastUID >= $lastUID) {
            $fetchData->byDate->$folder = true;
        }

        $account->updateFetchData($fetchData);
    }

    /**
     * @return int[]
     */
    private function getIdList(
        Account $account,
        Storage $storage,
        ?string $lastUID,
        ?string $lastDate,
        bool $forceByDate
    ): array {

        if (!empty($lastUID) && !$forceByDate) {
            return $storage->getIdsFromUniqueId($lastUID);
        }

        if ($lastDate) {
            return $storage->getIdsSinceDate(
                DateTimeField::fromString($lastDate)
            );
        }

        if (!$account->getFetchSince()) {
            throw new Error("{$account->getEntityType()} {$account->getId()}, no fetch-since.");
        }

        $fetchSince = $account->getFetchSince()->getDateTime();

        return $storage->getIdsSinceDate(
            DateTimeField::fromDateTime($fetchSince)
        );
    }

    /**
     * @param Collection<EmailFilter> $filterList
     */
    private function fetchEmail(
        Account $account,
        Storage $storage,
        int $id,
        Collection $filterList
    ): ?Email {

        $teamIdList = $account->getTeams()->getIdList();
        $userIdList = $account->getUsers()->getIdList();
        $userId = $account->getUser() ? $account->getUser()->getId() : null;
        $assignedUserId = $account->getAssignedUser() ? $account->getAssignedUser()->getId() : null;

        $fetchOnlyHeader = $this->checkFetchOnlyHeader($storage, $id);

        $folderData = [];

        if ($userId && $account->getEmailFolder()) {
            $folderData[$userId] = $account->getEmailFolder()->getId();
        }

        $flags = null;

        $parser = $this->parserFactory->create();

        $importerData = ImporterData
            ::create()
            ->withTeamIdList($teamIdList)
            ->withFilterList($filterList)
            ->withFetchOnlyHeader($fetchOnlyHeader)
            ->withFolderData($folderData)
            ->withUserIdList($userIdList)
            ->withAssignedUserId($assignedUserId);

        try {
            $message = new MessageWrapper($id, $storage, $parser);

            $hookResult = null;

            if ($this->beforeFetchHook) {
                $hookResult = $this->processBeforeFetchHook($account, $message);
            }

            if ($hookResult && $hookResult->toSkip()) {
                return null;
            }

            if ($message->isFetched() && $account->keepFetchedEmailsUnread()) {
                $flags = $message->getFlags();
            }

            $email = $this->importMessage($account, $message, $importerData);

            if (
                $account->keepFetchedEmailsUnread() &&
                is_array($flags) &&
                empty($flags[Flag::SEEN])
            ) {
                unset($flags[Flag::RECENT]);

                $storage->setFlags($id, $flags);
            }
        }
        catch (Throwable $e) {
            $this->log->error(
                "{$account->getEntityType()} {$account->getId()}, get message; " .
                "{$e->getCode()} {$e->getMessage()}"
            );

            return null;
        }

        $account->relateEmail($email);

        if (!$this->afterFetchHook) {
            return $email;
        }

        try {
            $this->afterFetchHook->process(
                $account,
                $email,
                $hookResult ?? BeforeFetchHookResult::create()
            );
        }
        catch (Throwable $e) {
            $this->log->error(
                "{$account->getEntityType()} {$account->getId()}, after-fetch hook; " .
                "{$e->getCode()} {$e->getMessage()}"
            );
        }

        return $email;
    }

    private function processBeforeFetchHook(Account $account, MessageWrapper $message): BeforeFetchHookResult
    {
        try {
            return $this->beforeFetchHook->process($account, $message);
        }
        catch (Throwable $e) {
            $this->log->error(
                "{$account->getEntityType()} {$account->getId()}, before-fetch hook; " .
                "{$e->getCode()} {$e->getMessage()}"
            );
        }

        return BeforeFetchHookResult::create()->withToSkip(true);
    }

    /**
     * @return Collection<EmailFilter>
     */
    private function getFilterList(Account $account): Collection
    {
        /** @var Collection<EmailFilter> */
        return $this->entityManager
            ->getRDBRepository(EmailFilter::ENTITY_TYPE)
            ->where([
                'action' => 'Skip',
                'OR' => [
                    [
                        'parentType' => $account->getEntityType(),
                        'parentId' => $account->getId(),
                    ],
                    [
                        'parentId' => null,
                    ],
                ]
            ])
            ->find();
    }

    private function checkFetchOnlyHeader(Storage $storage, int $id): bool
    {
        $maxSize = $this->config->get('emailMessageMaxSize');

        if (!$maxSize) {
            return false;
        }

        try {
            $size = $storage->getSize((int) $id);
        }
        catch (Throwable $e) {
            return false;
        }

        if (!is_int($size)) {
            return false;
        }

        if ($size > $maxSize * 1024 * 1024) {
            return true;
        }

        return false;
    }

    private function importMessage(
        Account $account,
        MessageWrapper $message,
        ImporterData $data
    ): ?Email {

        try {
            return $this->importer->import($message, $data);
        }
        catch (Throwable $e) {
            $this->log->error(
                "{$account->getEntityType()} {$account->getId()}, import message; " .
                "{$e->getCode()} {$e->getMessage()}"
            );

            if ($this->entityManager->getLocker()->isLocked()) {
                $this->entityManager->getLocker()->rollback();
            }
        }

        return null;
    }
}
