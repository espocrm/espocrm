<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2024 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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
use Espo\Tools\Stream\RecordService\Helper;

class GlobalRecordService
{
    public const SCOPE_NAME = 'GlobalStream';

    public function __construct(
        private Acl $acl,
        private User $user,
        private Metadata $metadata,
        private EntityManager $entityManager,
        private Helper $helper,
        private NoteAccessControl $noteAccessControl
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

        $baseBuilder = $this->helper->buildBaseQueryBuilder($searchParams)
            ->select($this->helper->getUserQuerySelect())
            ->where([
                'OR' => [
                    ['parentType' => $this->getEntityTypeList()],
                    [
                        'parentType' => null,
                        'type' => Note::TYPE_POST,
                    ],
                ]
            ])
            ->order('number', Order::DESC)
            ->limit(0, $maxSize + 1);

        $builder = (clone $baseBuilder);

        $list = [];

        while (true) {
            $collection = $this->entityManager
                ->getRDBRepositoryByClass(Note::class)
                ->clone($builder->build())
                ->sth()
                ->find();

            /** @var Note[] $subList */
            $subList = iterator_to_array($collection);

            if ($subList === []) {
                break;
            }

            $lastNumber = end($subList)->getNumber();

            $list = array_merge($list, $this->filter($subList));

            if (count($list) >= $maxSize + 1) {
                break;
            }

            $builder = (clone $baseBuilder)->where(['number<' => $lastNumber]);
        }

        $list = array_slice($list, 0, $maxSize + 1);

        /** @var Collection<Note> $collection */
        $collection = $this->entityManager->getCollectionFactory()->create(null, $list);

        foreach ($collection as $note) {
            $note->loadAdditionalFields();
            $this->noteAccessControl->apply($note, $this->user);
        }

        return RecordCollection::createNoCount($collection, $maxSize);
    }

    /**
     * @param Note[] $noteList
     * @return Note[]
     */
    private function filter(array $noteList): array
    {
        /** @var Note[] $outputList */
        $outputList = [];

        foreach ($noteList as $note) {
            if (!$this->checkAccess($note)) {
                continue;
            }

            $outputList[] = $note;
        }

        return $outputList;
    }

    private function checkAccess(Note $note): bool
    {
        $parentType = $note->getParentType();
        $parentId = $note->getParentId();

        if (!$note->getParentType()) {
            return $this->acl->checkEntityRead($note);
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
}
