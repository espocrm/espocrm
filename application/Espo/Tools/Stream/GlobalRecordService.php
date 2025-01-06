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

namespace Espo\Tools\Stream;

use Espo\Core\Acl;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Record\Collection as RecordCollection;
use Espo\Core\Select\SearchParams;
use Espo\Core\Utils\Metadata;
use Espo\Entities\Note;
use Espo\Entities\User;
use Espo\ORM\Collection;
use Espo\ORM\EntityManager;
use Espo\ORM\Query\Part\Order;
use Espo\ORM\Query\Select;
use Espo\ORM\Query\SelectBuilder;
use Espo\ORM\SthCollection;
use Espo\Tools\Stream\RecordService\NoteHelper;
use Espo\Tools\Stream\RecordService\QueryHelper;

class GlobalRecordService
{
    public const SCOPE_NAME = 'GlobalStream';
    private const ITERATION_LIMIT = 50;

    public function __construct(
        private Acl $acl,
        private User $user,
        private Metadata $metadata,
        private EntityManager $entityManager,
        private QueryHelper $queryHelper,
        private NoteAccessControl $noteAccessControl,
        private NoteHelper $noteHelper,
        private MassNotePreparator $massNotePreparator,
    ) {}

    /**
     * @return RecordCollection<Note>
     * @throws Forbidden
     * @throws BadRequest
     */
    public function find(SearchParams $searchParams): RecordCollection
    {
        $this->preCheck($searchParams);

        $maxSize = $searchParams->getMaxSize() ?? 0;
        $entityTypeList = $this->getEntityTypeList();

        $baseBuilder = $this->queryHelper->buildBaseQueryBuilder($searchParams)
            ->select($this->queryHelper->getUserQuerySelect())
            ->order('number', Order::DESC)
            ->limit(0, $maxSize + 1);

        /** @var array{string, string}[] $ignoreList */
        $ignoreList = [];
        /** @var array{string, string}[] $allowList */
        $allowList = [];

        $list = [];
        $i = 0;
        $iterationBuilder = (clone $baseBuilder);

        while (true) {
            $queryList = [];

            $this->buildBelongToParentQuery($iterationBuilder, $queryList, $entityTypeList, $ignoreList);
            $this->buildPostedToUserQuery($iterationBuilder, $queryList);
            $this->buildPostedToPortalQuery($iterationBuilder, $queryList);
            $this->buildPostedToTeamsQuery($iterationBuilder, $queryList);
            $this->buildPostedByUserQuery($iterationBuilder, $queryList);
            $this->buildPostedToGlobalQuery($iterationBuilder, $queryList);

            $collection = $this->fetchCollection($queryList, $maxSize);

            /** @var Note[] $subList */
            $subList = iterator_to_array($collection);

            if ($subList === []) {
                break;
            }

            // Should be obtained before filtering.
            $lastNumber = end($subList)->getNumber();

            $list = array_merge(
                $list,
                $this->filter($subList, $ignoreList, $allowList),
            );

            if (count($list) >= $maxSize + 1) {
                break;
            }

            $i ++;

            // @todo Introduce a config parameter 'globalStreamIterationLimits'.
            if ($i === self::ITERATION_LIMIT) {
                break;
            }

            $iterationBuilder = (clone $baseBuilder)->where(['number<' => $lastNumber]);
        }

        $list = array_slice($list, 0, $maxSize + 1);

        /** @var Collection<Note> $collection */
        $collection = $this->entityManager->getCollectionFactory()->create(null, $list);

        foreach ($collection as $note) {
            $note->loadAdditionalFields();
            $this->noteAccessControl->apply($note, $this->user);
            $this->noteHelper->prepare($note);
        }

        $this->massNotePreparator->prepare($collection);

        return RecordCollection::createNoCount($collection, $maxSize);
    }

    /**
     * @param Note[] $noteList
     * @param array{string, string}[] $ignoreList
     * @param array{string, string}[] $allowList
     * @return Note[]
     */
    private function filter(array $noteList, array &$ignoreList, array &$allowList): array
    {
        /** @var Note[] $outputList */
        $outputList = [];

        foreach ($noteList as $note) {
            if ($this->checkAgainstList($note, $ignoreList)) {
                continue;
            }

            if (
                !$this->checkAgainstList($note, $allowList) &&
                !$this->checkAccess($note)
            ) {
                $this->addToList($note, $ignoreList);

                continue;
            }

            if ($note->getParentType() && $note->getParentId()) {
                $this->addToList($note, $allowList);
            }

            $outputList[] = $note;
        }

        return $outputList;
    }

    /**
     * @param array{string, string}[] $list
     */
    private function addToList(Note $note, array &$list): void
    {
        if (!$note->getParentType() || !$note->getParentId()) {
            return;
        }

        $list[] = [$note->getParentType(), $note->getParentId()];
    }

    /**
     * @param array{string, string}[] $list
     */
    private function checkAgainstList(Note $note, array $list): bool
    {
        if (!$note->getParentType() || !$note->getParentId()) {
            return false;
        }

        return
            array_filter($list, function ($it) use ($note) {
                return
                    $it[0] === $note->getParentType() &&
                    $it[1] === $note->getParentId();
            }) !== [];
    }

    private function checkAccess(Note $note): bool
    {
        $parentType = $note->getParentType();
        $parentId = $note->getParentId();

        if (!$note->getParentType()) {
            // Only proper records are fetched.
            return true;
        }

        if (!$parentType || !$parentId) {
            return false;
        }

        if (!$this->acl->checkScope($parentType, Acl\Table::ACTION_STREAM)) {
            return false;
        }

        $parent = $this->entityManager->getEntityById($parentType, $parentId);

        if (!$parent) {
            return false;
        }

        return $this->acl->checkEntityStream($parent);
    }

    /**
     * @return string[]
     */
    private function getEntityTypeList(): array
    {
        $list = [];

        /** @var array<string, array<string, mixed>> $scopes */
        $scopes = $this->metadata->get('scopes');

        foreach ($scopes as $scope => $item) {
            if (
                !($item['entity'] ?? false) ||
                !($item['stream'] ?? false)
            ) {
                continue;
            }

            if (
                !$this->acl->checkScope($scope, Acl\Table::ACTION_READ) ||
                !$this->acl->checkScope($scope, Acl\Table::ACTION_STREAM)
            ) {
                continue;
            }

            $list[] = $scope;
        }

        return $list;
    }

    /**
     * @throws BadRequest
     * @throws Forbidden
     */
    private function preCheck(SearchParams $searchParams): void
    {
        if (!$this->acl->checkScope(self::SCOPE_NAME)) {
            throw new Forbidden();
        }

        if ($searchParams->getOffset()) {
            throw new BadRequest("Offset is not supported.");
        }
    }

    /**
     * @param Select[] $queryList
     * @param int $maxSize
     * @return SthCollection<Note>
     */
    private function fetchCollection(array $queryList, int $maxSize): SthCollection
    {
        $unionBuilder = $this->entityManager
            ->getQueryBuilder()
            ->union()
            ->all()
            ->order('number', Order::DESC)
            ->limit(0, $maxSize + 1);

        foreach ($queryList as $query) {
            $unionBuilder->query($query);
        }

        $unionQuery = $unionBuilder->build();

        $sql = $this->entityManager
            ->getQueryComposer()
            ->compose($unionQuery);

        /** @var SthCollection<Note> */
        return $this->entityManager
            ->getRDBRepositoryByClass(Note::class)
            ->findBySql($sql);
    }

    /**
     * @param Select[] $queryList
     */
    private function buildPostedToUserQuery(SelectBuilder $baseBuilder, array &$queryList): void
    {
        $queryList[] = $this->queryHelper->buildPostedToUserQuery($this->user, $baseBuilder);
    }

    /**
     * @param Select[] $queryList
     */
    private function buildPostedToPortalQuery(SelectBuilder $baseBuilder, array &$queryList): void
    {
        $query = $this->queryHelper->buildPostedToPortalQuery($this->user, $baseBuilder);

        if (!$query) {
            return;
        }

        $queryList[] = $query;
    }

    /**
     * @param Select[] $queryList
     */
    private function buildPostedToTeamsQuery(SelectBuilder $baseBuilder, array &$queryList): void
    {
        $query = $this->queryHelper->buildPostedToTeamsQuery($this->user, $baseBuilder);

        if (!$query) {
            return;
        }

        $queryList[] = $query;
    }

    /**
     * @param Select[] $queryList
     */
    private function buildPostedByUserQuery(SelectBuilder $baseBuilder, array &$queryList): void
    {
        $queryList[] = $this->queryHelper->buildPostedByUserQuery($this->user, $baseBuilder);
    }

    /**
     * @param Select[] $queryList
     */
    private function buildPostedToGlobalQuery(SelectBuilder $baseBuilder, array &$queryList): void
    {
        $query = $this->queryHelper->buildPostedToGlobalQuery($this->user, $baseBuilder);

        if (!$query) {
            return;
        }

        $queryList[] = $query;
    }

    /**
     * @param Select[] $queryList
     * @param string[] $entityTypeList
     * @param array{string, string}[] $ignoreList
     */
    private function buildBelongToParentQuery(
        SelectBuilder $builder,
        array &$queryList,
        array $entityTypeList,
        array $ignoreList
    ): void {

        $ignoreWhere = [];

        foreach ($ignoreList as $it) {
            $ignoreWhere[] = [
                'OR' => [
                    'parentType!=' => $it[0],
                    'parentId!=' => $it[1]
                ]
            ];
        }

        $queryList[] = (clone $builder)
            ->where(['parentType' => $entityTypeList])
            ->where($ignoreWhere)
            ->build();
    }
}
