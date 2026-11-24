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


namespace tests\integration\Espo\Stream;

use Espo\Core\Acl\Table;
use Espo\Core\Name\Field;
use Espo\Core\ORM\Repository\Option\SaveOption;
use Espo\Core\Record\ServiceContainer;
use Espo\Core\Select\SearchParams;
use Espo\Entities\Note;
use Espo\Entities\Role;
use Espo\Modules\Crm\Entities\Opportunity;
use Espo\ORM\Name\Attribute;
use tests\integration\Core\BaseTestCase;

class NoteListAccessTest extends BaseTestCase
{
    /**
     * The generic Note list must not return notes created by other users.
     *
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testOtherUsersNotesNotAccessible(): void
    {
        $em = $this->getEntityManager();

        $user = $this->createUser('tester', [
            Role::FIELD_DATA => [
                Opportunity::ENTITY_TYPE => [
                    Table::ACTION_READ => Table::LEVEL_ALL,
                    Table::ACTION_STREAM => Table::LEVEL_ALL,
                ],
            ],
            Role::FIELD_FIELD_DATA => [
                Opportunity::ENTITY_TYPE => [
                    Opportunity::FIELD_AMOUNT => [
                        Table::ACTION_READ => Table::LEVEL_NO,
                        Table::ACTION_EDIT => Table::LEVEL_NO,
                    ],
                ],
            ],
        ]);

        $opportunity = $em->createEntity(Opportunity::ENTITY_TYPE, [
            Field::NAME => 'Test',
            Opportunity::FIELD_AMOUNT => 50000,
            Opportunity::FIELD_AMOUNT . 'Currency' => 'USD',
        ]);

        $opportunity->set(Opportunity::FIELD_AMOUNT, 73000);
        $em->saveEntity($opportunity);

        $updateNote = $em->getRDBRepositoryByClass(Note::class)
            ->where([
                'parentId' => $opportunity->getId(),
                'parentType' => Opportunity::ENTITY_TYPE,
                Note::FIELD_TYPE => Note::TYPE_UPDATE,
            ])
            ->findOne();

        $this->assertNotNull($updateNote);
        $this->assertContains(Opportunity::FIELD_AMOUNT, $updateNote->getData()?->fields ?? []);
        $this->assertNotEquals($user->getId(), $updateNote->getCreatedById());

        $matchingUpdateNote = $em->getRDBRepositoryByClass(Note::class)
            ->where([
                Attribute::ID => $updateNote->getId(),
                Note::FIELD_DATA . '*' => '%"amount":73%',
            ])
            ->findOne();

        $this->assertNotNull($matchingUpdateNote);

        // A note of the user's own, to make sure the list and the search work at all.
        $ownNote = $em->getRDBRepositoryByClass(Note::class)->getNew();
        $ownNote->setParent($opportunity);
        $ownNote->setType(Note::TYPE_POST);
        $ownNote->setData([Opportunity::FIELD_AMOUNT => 73000]);
        $em->saveEntity($ownNote, [SaveOption::CREATED_BY_ID => $user->getId()]);

        $this->authenticate('tester');

        $service = $this->getContainer()
            ->getByClass(ServiceContainer::class)
            ->getByClass(Note::class);

        $collection = $service->find(SearchParams::create())->getCollection();

        $idList = array_map(fn ($note) => $note->getId(), iterator_to_array($collection));

        $this->assertContains($ownNote->getId(), $idList);
        $this->assertNotContains($updateNote->getId(), $idList);
    }
}
