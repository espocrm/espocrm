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

namespace Espo\Core\Mail\Account\Storage;

use DirectoryTree\ImapEngine\Exceptions\Exception as CommonException;
use DirectoryTree\ImapEngine\FolderInterface;
use DirectoryTree\ImapEngine\Mailbox;
use DirectoryTree\ImapEngine\Message;
use DirectoryTree\ImapEngine\MessageInterface;
use DirectoryTree\ImapEngine\MessageQuery;
use Espo\Core\Field\DateTime;
use Espo\Core\Mail\Account\Storage;
use Espo\Core\Mail\Exceptions\ImapError;
use LogicException;

class DirectoryTreeStorage implements Storage
{
    private ?FolderInterface $selectedFolder = null;

    public function __construct(
        private Mailbox $mailbox,
    ) {}

    /**
     * @todo Test.
     * @inheritDoc
     * @noinspection PhpRedundantCatchClauseInspection
     */
    public function unmarkSeen(int $id): void
    {
        $folder = $this->getSelectedFolder();

        try {
            $message = $folder->messages()
                ->withFlags()
                ->find($id);
        } catch (CommonException $e) {
            throw new ImapError($e->getMessage(), previous: $e);
        }

        if (!$message) {
            throw new ImapError("Could not fetch message $id.");
        }

        try {
            $message->unmarkSeen();
        } catch (CommonException $e) {
            throw new ImapError($e->getMessage(), previous: $e);
        }
    }

    /**
     * @noinspection PhpRedundantCatchClauseInspection
     */
    public function getSize(int $id): int
    {
        $folder = $this->getSelectedFolder();

        try {
            $message = $folder->messages()
                ->withSize()
                ->find($id);
        } catch (CommonException $e) {
            throw new ImapError($e->getMessage(), previous: $e);
        }

        if (!$message) {
            throw new ImapError("Could not fetch message $id.");
        }

        return $message->size() ?? 0;
    }

    /**
     * @inheritDoc
     * @noinspection PhpRedundantCatchClauseInspection
     */
    public function getRawContent(int $id, bool $peek): string
    {
        $folder = $this->getSelectedFolder();

        try {
            $message = $folder->messages()
                ->withHeaders()
                ->withFlags()
                ->withBody()
                ->find($id);

            if ($message instanceof Message && !$peek) {
                $message->markSeen();
            }
        } catch (CommonException $e) {
            throw new ImapError($e->getMessage(), previous: $e);
        }

        if (!$message) {
            throw new ImapError("Could not fetch message $id.");
        }

        if (!$message instanceof Message) {
            throw new LogicException("Not supported message instance.");
        }

        return $message->body();
    }

    /**
     * @inheritDoc
     * @noinspection PhpRedundantCatchClauseInspection
     */
    public function getUidsFromUid(int $id): array
    {
        $output = [];

        $query = $this->getSelectedFolder()
            ->messages()
            ->withoutHeaders()
            ->uid($id, INF);

        /**
         * Magic methods are used.
         * @noinspection PhpConditionAlreadyCheckedInspection
         * @phpstan-ignore-next-line
         */
        assert($query instanceof MessageQuery);

        $query->setFetchOrderAsc();

        try {
            $query->each(function (MessageInterface $message) use (&$output) {
                $output[] = $message->uid();
            });
        } catch (CommonException $e) {
            throw new ImapError($e->getMessage(), previous: $e);
        }

        // May return wrong items. Do not return one with matching id.
        $output = array_filter($output, fn ($it) => $it > $id);
        $output = array_values($output);

        // Otherwise, it's in reverse order.
        sort($output);

        return $output;
    }

    /**
     * @todo Test.
     * @inheritDoc
     * @noinspection PhpRedundantCatchClauseInspection
     */
    public function getUidsSinceDate(DateTime $since): array
    {
        $output = [];

        $query = $this->getSelectedFolder()
            ->messages()
            ->withoutHeaders()
            ->since($since->toDateTime());

        /**
         * Magic methods are used.
         * @noinspection PhpConditionAlreadyCheckedInspection
         * @phpstan-ignore-next-line
         */
        assert($query instanceof MessageQuery);

        $query->setFetchOrderAsc();

        try {
            $query->each(function (MessageInterface $message) use (&$output) {
                $output[] = $message->uid();
            });
        } catch (CommonException $e) {
            throw new ImapError($e->getMessage(), previous: $e);
        }

        // Otherwise, it's in reverse order.
        sort($output);

        return $output;
    }

    /**
     * @return array{header: string, flags: string[]}
     * @inheritDoc
     * @noinspection PhpRedundantCatchClauseInspection
     */
    public function getHeaderAndFlags(int $id): array
    {
        try {
            $folder = $this->getSelectedFolder();

            $message = $folder->messages()
                ->withHeaders()
                ->withFlags()
                ->find($id);
        } catch (CommonException $e) {
            throw new ImapError($e->getMessage(), previous: $e);
        }

        if (!$message) {
            throw new ImapError("Could not fetch message $id.");
        }

        if (!$message instanceof Message) {
            // Whenever the library is upgraded, it's reasonable to check whether
            // the Message instance is still returned by the library.
            throw new LogicException("Not supported message instance.");
        }

        return [
            'header' => $message->head(),
            'flags' => $folder->flags(),
        ];
    }

    public function close(): void
    {
        $this->mailbox->disconnect();
        $this->selectedFolder = null;
    }

    /**
     * @return string[]
     * @inheritDoc
     * @noinspection PhpRedundantCatchClauseInspection
     */
    public function getFolderNames(): array
    {
        $output = [];

        try {
            $folders = $this->mailbox->folders()->get();

            foreach ($folders as $folder) {
                $output[] = mb_convert_encoding($folder->path(), 'UTF-8', 'UTF7-IMAP');
            }
        } catch (CommonException $e) {
            throw new ImapError($e->getMessage(), previous: $e);
        }

        return $output;
    }

    /**
     * @inheritDoc
     * @noinspection PhpRedundantCatchClauseInspection
     */
    public function selectFolder(string $name): void
    {
        try {
            $folder = $this->getFolder($name);
            $this->selectedFolder = $folder;
            $folder->select();
        } catch (CommonException $e) {
            throw new ImapError($e->getMessage(), previous: $e);
        }
    }

    /**
     * @inheritDoc
     * @noinspection PhpRedundantCatchClauseInspection
     */
    public function appendMessage(string $content, string $folder): void
    {
        try {
            $this->getFolder($folder)
                ->messages()
                ->append($content);
        } catch (CommonException $e) {
            throw new ImapError($e->getMessage(), previous: $e);
        }
    }

    private function getSelectedFolder(): FolderInterface
    {
        return $this->selectedFolder ?: $this->mailbox->inbox();
    }

    /**
     * @throws ImapError
     * @noinspection PhpRedundantCatchClauseInspection
     */
    private function getFolder(string $name): FolderInterface
    {
        try {
            $folder = $this->mailbox->folders()->find($name);
        } catch (CommonException $e) {
            throw new ImapError($e->getMessage(), previous: $e);
        }

        if (!$folder) {
            throw new ImapError("Could not select folder '$name'.");
        }

        return $folder;
    }
}
