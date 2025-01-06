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

namespace tests\integration\Espo\Stream;

use Espo\Core\Acl\Table;
use Espo\Core\Api\Request;
use Espo\Core\Exceptions\Forbidden;
use Espo\Entities\Note;
use Espo\Modules\Crm\Entities\Account;
use Espo\Tools\Stream\Api\DeleteMyReactions;
use Espo\Tools\Stream\Api\PostMyReactions;
use Espo\Tools\Stream\MassNotePreparator;
use tests\integration\Core\BaseTestCase;

class ReactionsTest extends BaseTestCase
{
    private const REACTION_LIKE = 'Like';

    public function testReactions(): void
    {
        $userTest = $this->createUser('test', [
            'data' => [
                Account::ENTITY_TYPE => [
                    'create' => Table::LEVEL_NO,
                    'read' => Table::LEVEL_ALL,
                    'stream' => Table::LEVEL_OWN,
                ],
            ]
        ]);

        $em = $this->getEntityManager();

        $account1 = $em->getRDBRepositoryByClass(Account::class)->getNew();
        $account1->setAssignedUser($userTest);
        $em->saveEntity($account1);

        $account2 = $em->getRDBRepositoryByClass(Account::class)->getNew();
        $em->saveEntity($account2);

        $note1 = $this->getEntityManager()->getRDBRepositoryByClass(Note::class)->getNew();
        $note1
            ->setType(Note::TYPE_POST)
            ->setParent($account1)
            ->setPost('Test');
        $em->saveEntity($note1);

        $note2 = $this->getEntityManager()->getRDBRepositoryByClass(Note::class)->getNew();
        $note2
            ->setType(Note::TYPE_POST)
            ->setParent($account2)
            ->setPost('Test');
        $em->saveEntity($note2);

        $this->auth('test');
        $this->reCreateApplication();

        // Has access.

        $request = $this->createMock(Request::class);

        $request->expects($this->any())
            ->method('getRouteParam')
            ->willReturnMap([
                ['id', $note1->getId()],
                ['type', self::REACTION_LIKE],
            ]);

        /** @noinspection PhpUnhandledExceptionInspection */
        $this->getInjectableFactory()
            ->create(PostMyReactions::class)
            ->process($request);

        $this->getInjectableFactory()->create(MassNotePreparator::class)->prepare([$note1]);

        $this->assertEquals([self::REACTION_LIKE], $note1->get('myReactions'));
        $this->assertEquals(1, $note1->get('reactionCounts')->{self::REACTION_LIKE});

        // Un-react.

        $request = $this->createMock(Request::class);

        $request->expects($this->any())
            ->method('getRouteParam')
            ->willReturnMap([
                ['id', $note1->getId()],
                ['type', self::REACTION_LIKE],
            ]);

        /** @noinspection PhpUnhandledExceptionInspection */
        $this->getInjectableFactory()
            ->create(DeleteMyReactions::class)
            ->process($request);

        $this->getInjectableFactory()->create(MassNotePreparator::class)->prepare([$note1]);

        $this->assertEquals([], $note1->get('myReactions'));
        $this->assertEquals(0, $note1->get('reactionCounts')->{self::REACTION_LIKE} ?? 0);

        // No access to note.

        $isThrown = false;

        try {
            $request = $this->createMock(Request::class);

            $request->expects($this->any())
                ->method('getRouteParam')
                ->willReturnMap([
                    ['id', $note2->getId()],
                    ['type', self::REACTION_LIKE],
                ]);

            /** @noinspection PhpUnhandledExceptionInspection */
            $this->getInjectableFactory()
                ->create(PostMyReactions::class)
                ->process($request);
        } catch (Forbidden) {
            $isThrown = true;
        }

        $this->assertTrue($isThrown);

        // No access to note to un-react.

        $isThrown = false;

        try {
            $request = $this->createMock(Request::class);

            $request->expects($this->any())
                ->method('getRouteParam')
                ->willReturnMap([
                    ['id', $note2->getId()],
                    ['type', self::REACTION_LIKE],
                ]);

            /** @noinspection PhpUnhandledExceptionInspection */
            $this->getInjectableFactory()
                ->create(DeleteMyReactions::class)
                ->process($request);
        } catch (Forbidden) {
            $isThrown = true;
        }

        $this->assertTrue($isThrown);

        // Not allowed reaction.

        $isThrown = false;

        try {
            $request = $this->createMock(Request::class);

            $request->expects($this->any())
                ->method('getRouteParam')
                ->willReturnMap([
                    ['id', $note1->getId()],
                    ['type', 'Smile'],
                ]);

            /** @noinspection PhpUnhandledExceptionInspection */
            $this->getInjectableFactory()
                ->create(PostMyReactions::class)
                ->process($request);
        } catch (Forbidden) {
            $isThrown = true;
        }

        $this->assertTrue($isThrown);
    }
}
