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

use Espo\Core\Utils\Config;
use Espo\Entities\Note;
use Espo\Entities\User;
use Espo\Entities\UserReaction;
use Espo\ORM\EntityManager;
use Espo\ORM\Query\Part\Expression;
use Espo\ORM\Query\Part\Selection;
use Espo\ORM\Query\SelectBuilder;

/**
 * @internal
 */
class MassNotePreparator
{
    public function __construct(
        private EntityManager $entityManager,
        private User $user,
        private Config $config,
    ) {}

    /**
     * @param iterable<Note> $notes
     */
    public function prepare(iterable $notes): void
    {
        if ($this->noAvailableReactions()) {
            return;
        }

        $ids = $this->getPostIds($notes);

        $this->prepareMyReactions($ids, $notes);
        $this->prepareReactionCounts($ids, $notes);
    }

    /**
     * @param iterable<Note> $notes
     * @return string[]
     */
    private function getPostIds(iterable $notes): array
    {
        $ids = [];

        foreach ($notes as $note) {
            if ($note->getType() !== Note::TYPE_POST) {
                continue;
            }

            $ids[] = $note->getId();
        }

        return $ids;
    }

    /**
     * @param string[] $ids
     * @param iterable<Note> $notes
     */
    private function prepareMyReactions(array $ids, iterable $notes): void
    {
        $myUserReactionCollection = $this->entityManager
            ->getRDBRepositoryByClass(UserReaction::class)
            ->where([
                'userId' => $this->user->getId(),
                'parentType' => Note::ENTITY_TYPE,
                'parentId' => $ids,
            ])
            ->find();

        /** @var UserReaction[] $myUserReactions */
        $myUserReactions = iterator_to_array($myUserReactionCollection);

        foreach ($notes as $note) {
            $noteMyReactions = [];

            foreach ($myUserReactions as $reaction) {
                if ($reaction->getParent()->getId() !== $note->getId()) {
                    continue;
                }

                $noteMyReactions[] = $reaction->getType();
            }

            $note->set('myReactions', $noteMyReactions);
        }
    }

    /**
     * @param string[] $ids
     * @param iterable<Note> $notes
     */
    private function prepareReactionCounts(array $ids, iterable $notes): void
    {
        $query = SelectBuilder::create()
            ->from(UserReaction::ENTITY_TYPE)
            ->select([
                Selection::create(Expression::count(Expression::column('id')), 'count'),
                'parentId',
                'type',
            ])
            ->where([
                'parentType' => Note::ENTITY_TYPE,
                'parentId' => $ids,
            ])
            ->group('parentId')
            ->group('type')
            ->build();

        /** @var array<int, array{count: int, type: string, parentId: string}> $rows */
        $rows = $this->entityManager
            ->getQueryExecutor()
            ->execute($query)
            ->fetchAll();

        foreach ($notes as $note) {
            $counts = [];

            foreach ($rows as $row) {
                if ($row['parentId'] !== $note->getId()) {
                    continue;
                }

                $counts[$row['type']] = $row['count'];
            }

            $note->set('reactionCounts', $counts);
        }
    }

    private function noAvailableReactions(): bool
    {
        return $this->config->get('availableReactions', []) === [];
    }
}
