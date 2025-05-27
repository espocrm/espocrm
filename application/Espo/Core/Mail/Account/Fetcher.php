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

namespace Espo\Core\Mail\Account;

use Espo\Core\Exceptions\Error;

use Espo\Core\Mail\Account\Storage\Flag;
use Espo\Core\Mail\Exceptions\ImapError;
use Espo\Core\Mail\Exceptions\NoImap;
use Espo\Core\Mail\Importer;
use Espo\Core\Mail\Importer\Data as ImporterData;
use Espo\Core\Mail\ParserFactory;
use Espo\Core\Mail\MessageWrapper;
use Espo\Core\Mail\Account\Hook\BeforeFetch as BeforeFetchHook;
use Espo\Core\Mail\Account\Hook\AfterFetch as AfterFetchHook;
use Espo\Core\Mail\Account\Hook\BeforeFetchResult as BeforeFetchHookResult;

use Espo\Core\Utils\Config;
use Espo\Core\Utils\Log;
use Espo\Core\Field\DateTime as DateTimeField;
use Espo\Entities\EmailFilter;
use Espo\Entities\Email;
use Espo\Entities\InboundEmail;
use Espo\ORM\Collection;
use Espo\ORM\EntityManager;
use Espo\ORM\Query\Part\Expression;
use Espo\ORM\Query\Part\Order;

use Throwable;
use DateTime;

class Fetcher
{
    public function __construct(
        private Importer $importer,
        private StorageFactory $storageFactory,
        private Config $config,
        private Log $log,
        private EntityManager $entityManager,
        private ParserFactory $parserFactory,
        private ?BeforeFetchHook $beforeFetchHook,
        private ?AfterFetchHook $afterFetchHook
    ) {}

    /**
     * @throws Error
     * @throws ImapError
     * @throws NoImap
     */
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
     * @throws Error
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
        } catch (Throwable $e) {
            $this->log->error(
                "{$account->getEntityType()} {$account->getId()}, " .
                "could not select folder '$folder'; [{$e->getCode()}] {$e->getMessage()}"
            );

            return;
        }

        $lastUniqueId = $fetchData->getLastUniqueId($folder);
        $lastDate = $fetchData->getLastDate($folder);
        $forceByDate = $fetchData->getForceByDate($folder);

        $portionLimit = $forceByDate ? 0 : $account->getPortionLimit();

        $previousLastUniqueId = $lastUniqueId;

        $idList = $this->getIdList(
            $account,
            $storage,
            $lastUniqueId,
            $lastDate,
            $forceByDate
        );

        if (count($idList) === 1 && $lastUniqueId) {
            if ($storage->getUniqueId($idList[0]) === $lastUniqueId) {
                return;
            }
        }

        $counter = 0;

        foreach ($idList as $id) {
            if ($counter == count($idList) - 1) {
                $lastUniqueId = $storage->getUniqueId($id);
            }

            if ($forceByDate && $previousLastUniqueId) {
                $uid = $storage->getUniqueId($id);

                if ((int) $uid <= (int) $previousLastUniqueId) {
                    $counter++;

                    continue;
                }
            }

            $email = $this->fetchEmail($account, $storage, $id, $filterList);

            $isLast = $counter === count($idList) - 1;
            $isLastInPortion = $counter === $portionLimit - 1;

            if ($isLast || $isLastInPortion) {
                $lastUniqueId = $storage->getUniqueId($id);

                if ($email && $email->getDateSent()) {
                    $lastDate = $email->getDateSent();

                    if ($lastDate->toTimestamp() >= (new DateTime())->getTimestamp()) {
                        $lastDate = DateTimeField::createNow();
                    }
                }

                break;
            }

            $counter++;
        }

        if ($forceByDate) {
            $lastDate = DateTimeField::createNow();
        }

        $fetchData->setLastDate($folder, $lastDate);
        $fetchData->setLastUniqueId($folder, $lastUniqueId);

        if ($forceByDate && $previousLastUniqueId) {
            $idList = $storage->getIdsFromUniqueId($previousLastUniqueId);

            if (count($idList)) {
                $uid1 = $storage->getUniqueId($idList[0]);

                if ((int) $uid1 > (int) $previousLastUniqueId) {
                    $fetchData->setForceByDate($folder, false);
                }
            }
        }

        if (
            !$forceByDate &&
            $previousLastUniqueId &&
            count($idList) &&
            (int) $previousLastUniqueId >= (int) $lastUniqueId
        ) {
            // Handling broken numbering. Next time fetch since the last date rather than the last UID.
            $fetchData->setForceByDate($folder, true);
        }

        $account->updateFetchData($fetchData);
    }

    /**
     * @return int[]
     * @throws Error
     */
    private function getIdList(
        Account $account,
        Storage $storage,
        ?string $lastUID,
        ?DateTimeField $lastDate,
        bool $forceByDate
    ): array {

        if (!empty($lastUID) && !$forceByDate) {
            return $storage->getIdsFromUniqueId($lastUID);
        }

        if ($lastDate) {
            return $storage->getIdsSinceDate($lastDate);
        }

        if (!$account->getFetchSince()) {
            throw new Error("{$account->getEntityType()} {$account->getId()}, no fetch-since.");
        }

        $fetchSince = $account->getFetchSince()->toDateTime();

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
        $groupEmailFolderId = $account->getGroupEmailFolder() ? $account->getGroupEmailFolder()->getId() : null;

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
            ->withAssignedUserId($assignedUserId)
            ->withGroupEmailFolderId($groupEmailFolderId);

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

            if (!$email) {
                return null;
            }

            if (
                $account->keepFetchedEmailsUnread() &&
                $flags !== null &&
                !in_array(Flag::SEEN, $flags)
            ) {
                $storage->setFlags($id, self::flagsWithoutRecent($flags));
            }
        } catch (Throwable $e) {
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
        } catch (Throwable $e) {
            $this->log->error(
                "{$account->getEntityType()} {$account->getId()}, after-fetch hook; " .
                "{$e->getCode()} {$e->getMessage()}"
            );
        }

        return $email;
    }

    private function processBeforeFetchHook(Account $account, MessageWrapper $message): BeforeFetchHookResult
    {
        assert($this->beforeFetchHook !== null);

        try {
            return $this->beforeFetchHook->process($account, $message);
        } catch (Throwable $e) {
            $this->log->error(
                "{$account->getEntityType()} {$account->getId()}, before-fetch hook; " .
                "{$e->getCode()} {$e->getMessage()}"
            );
        }

        return BeforeFetchHookResult::create()->withToSkip();
    }

    /**
     * @return Collection<EmailFilter>
     */
    private function getFilterList(Account $account): Collection
    {
        $actionList = [EmailFilter::ACTION_SKIP];

        if ($account->getEntityType() === InboundEmail::ENTITY_TYPE) {
            $actionList[] = EmailFilter::ACTION_MOVE_TO_GROUP_FOLDER;
        }

        $builder = $this->entityManager
            ->getRDBRepository(EmailFilter::ENTITY_TYPE)
            ->where([
                'action' => $actionList,
                'OR' => [
                    [
                        'parentType' => $account->getEntityType(),
                        'parentId' => $account->getId(),
                        'action' => $actionList,
                    ],
                    [
                        'parentId' => null,
                        'action' => EmailFilter::ACTION_SKIP,
                    ],
                ]
            ]);

        if (count($actionList) > 1) {
            $builder->order(
                Order::createByPositionInList(
                    Expression::column('action'),
                    $actionList
                )
            );
        }

        /** @var Collection<EmailFilter> */
        return $builder->find();
    }

    private function checkFetchOnlyHeader(Storage $storage, int $id): bool
    {
        $maxSize = $this->config->get('emailMessageMaxSize');

        if (!$maxSize) {
            return false;
        }

        try {
            $size = $storage->getSize($id);
        } catch (Throwable) {
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
        } catch (Throwable $e) {
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

    /**
     * @param string[] $flags
     * @return string[]
     */
    private static function flagsWithoutRecent(array $flags): array
    {
        return array_values(
            array_diff($flags, [Flag::RECENT])
        );
    }
}
