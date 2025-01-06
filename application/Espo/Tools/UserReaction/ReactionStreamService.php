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

namespace Espo\Tools\UserReaction;

use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\NotFound;
use Espo\Core\Field\DateTime;
use Espo\Core\Select\SearchParams;
use Espo\Core\Select\Where\Item;
use Espo\Core\Utils\Config;
use Espo\Entities\Note;
use Espo\Entities\User;
use Espo\Entities\UserReaction;
use Espo\ORM\EntityManager;
use Espo\ORM\Name\Attribute;
use Espo\ORM\Query\SelectBuilder;
use Espo\Tools\Stream\UserRecordService;
use stdClass;

class ReactionStreamService
{
    private const MAX_NOTE_COUNT = 100;
    private const MAX_PERIOD = '2 hours';

    public function __construct(
        private EntityManager $entityManager,
        private UserRecordService $userRecordService,
        private User $user,
        private Config $config,
    ) {}


    /**
     * Get reaction updates.
     *
     * @param string[] $noteIds
     * @return stdClass[]
     * @throws Forbidden
     * @throws BadRequest
     * @throws NotFound
     * @internal
     */
    public function getReactionUpdates(DateTime $after, array $noteIds, ?string $userId): array
    {
        if (count($noteIds) > $this->getMaxCount()) {
            throw new Forbidden("Too many note IDs.");
        }

        $userId ??= $this->user->getId();

        $after = $this->getAfter($after);

        $updatedIds = [];

        $query = SelectBuilder::create()
            ->from(UserReaction::ENTITY_TYPE)
            ->select('parentId')
            ->where([
                'parentId' => $noteIds,
                'parentType' => Note::ENTITY_TYPE,
                'createdAt>=' => $after->toString(),
            ])
            ->group('parentId')
            ->build();

        /** @var array{parentId: string}[] $rows */
        $rows = $this->entityManager->getQueryExecutor()->execute($query)->fetchAll();

        foreach ($rows as $row) {
            $updatedIds[] = $row['parentId'];
        }

        if (!$updatedIds) {
            return [];
        }

        $searchParams = SearchParams::create()
            ->withSelect([Attribute::ID, 'reactionCounts', 'myReactions'])
            ->withWhereAdded(
                Item::createBuilder()
                    ->setType(Item\Type::IN)
                    ->setAttribute('id')
                    ->setValue($updatedIds)
                    ->build()
            );

        $updatedNotes = $this->userRecordService->find($userId, $searchParams);

        $result = [];

        foreach ($updatedNotes->getCollection() as $note) {
            $result[] = (object) [
                'id' => $note->getId(),
                'myReactions' => $note->get('myReactions'),
                'reactionCounts' => $note->get('reactionCounts')
            ];
        }

        return $result;
    }

    private function getAfter(DateTime $after): DateTime
    {
        $afterMax = DateTime::createNow()->modify('-' . self::MAX_PERIOD);

        if ($afterMax->isGreaterThan($after)) {
            $after = $afterMax;
        }

        return $after;
    }

    private function getMaxCount(): int
    {
        return $this->config->get('streamReactionsCheckMaxSize') ?? self::MAX_NOTE_COUNT;
    }
}
