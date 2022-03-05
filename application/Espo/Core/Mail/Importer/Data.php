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

namespace Espo\Core\Mail\Importer;

class Data
{
    private ?string $assignedUserId = null;

    /**
     * @var string[]
     */
    private array $teamIdList = [];

    /**
     * @var string[]
     */
    private $userIdList = [];

    /**
     * @var iterable<\Espo\Entities\EmailFilter>
     */
    private $filterList = [];

    private bool $fetchOnlyHeader = false;

    /**
     * @var array<string,string>
     */
    private array $folderData = [];

    public static function create(): self
    {
        return new self();
    }

    public function getAssignedUserId(): ?string
    {
        return $this->assignedUserId;
    }

    /**
     * @return string[]
     */
    public function getTeamIdList(): array
    {
        return $this->teamIdList;
    }

    /**
     * @return string[]
     */
    public function getUserIdList(): array
    {
        return $this->userIdList;
    }

    /**
     * @return iterable<\Espo\Entities\EmailFilter>
     */
    public function getFilterList(): iterable
    {
        return $this->filterList;
    }

    public function fetchOnlyHeader(): bool
    {
        return $this->fetchOnlyHeader;
    }

    /**
     * @return array<string,string>
     */
    public function getFolderData(): array
    {
        return $this->folderData;
    }

    public function withAssignedUserId(?string $assignedUserId): self
    {
        $obj = clone $this;

        $obj->assignedUserId = $assignedUserId;

        return $obj;
    }

    /**
     * @param string[] $teamIdList
     */
    public function withTeamIdList(array $teamIdList): self
    {
        $obj = clone $this;

        $obj->teamIdList = $teamIdList;

        return $obj;
    }

    /**
     * @param string[] $userIdList
     */
    public function withUserIdList(array $userIdList): self
    {
        $obj = clone $this;
        $obj->userIdList = $userIdList;

        return $obj;
    }

    /**
     * @param iterable<\Espo\Entities\EmailFilter> $filterList
     */
    public function withFilterList(iterable $filterList): self
    {
        $obj = clone $this;
        $obj->filterList = $filterList;

        return $obj;
    }

    public function withFetchOnlyHeader(bool $fetchOnlyHeader = true): self
    {
        $obj = clone $this;
        $obj->fetchOnlyHeader = $fetchOnlyHeader;

        return $obj;
    }

    /**
     * @param array<string,string> $folderData
     */
    public function withFolderData(array $folderData): self
    {
        $obj = clone $this;
        $obj->folderData = $folderData;

        return $obj;
    }
}
