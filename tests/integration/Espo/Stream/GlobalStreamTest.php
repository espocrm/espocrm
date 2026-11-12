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
use Espo\Core\ORM\Repository\Option\SaveOption;
use Espo\Core\Select\SearchParams;
use Espo\Entities\Note;
use Espo\Entities\Role;
use Espo\Entities\Team;
use Espo\Entities\User;
use Espo\Modules\Crm\Entities\Account;
use Espo\Modules\Crm\Entities\CaseObj;
use Espo\Modules\Crm\Entities\Contact;
use Espo\Modules\Crm\Entities\Lead;
use Espo\Modules\Crm\Entities\Meeting;
use Espo\Modules\Crm\Entities\Opportunity;
use Espo\Modules\Crm\Entities\Task;
use Espo\ORM\Entity;
use Espo\Tools\Stream\GlobalRecordService;
use tests\integration\Core\BaseTestCase;

class GlobalStreamTest extends BaseTestCase
{
    private const string USER_NAME = 'test';

    private ?Team $team = null;
    private ?Team $otherTeam = null;
    private ?User $user = null;
    private ?User $otherUser = null;

    protected function setUp(): void
    {
        parent::setUp();

        $em = $this->getEntityManager();

        $this->team = $em->getRDBRepositoryByClass(Team::class)->getNew()
            ->setName('Team');

        $this->otherTeam = $em->getRDBRepositoryByClass(Team::class)->getNew()
            ->setName('Other Team');

        $em->saveEntity($this->team);
        $em->saveEntity($this->otherTeam);


        $this->user = $this->createUser([
            User::FIELD_USER_NAME => self::USER_NAME,
            User::LINK_TEAMS . 'Ids' => [$this->team->getId()],
            User::LINK_DEFAULT_TEAM . 'Id' => $this->team->getId(),
        ], [
            Role::FIELD_DATA => [
                GlobalRecordService::SCOPE_NAME => true,
                Account::ENTITY_TYPE => [
                    Table::ACTION_READ => Table::LEVEL_ALL,
                    Table::ACTION_STREAM => Table::LEVEL_ALL,
                ],
                Contact::ENTITY_TYPE => [
                    Table::ACTION_READ => Table::LEVEL_ALL,
                    Table::ACTION_STREAM => Table::LEVEL_NO,
                ],
                Lead::ENTITY_TYPE => [
                    Table::ACTION_READ => Table::LEVEL_NO,
                    Table::ACTION_STREAM => Table::LEVEL_NO,
                ],
                Opportunity::ENTITY_TYPE => [
                    Table::ACTION_READ => Table::LEVEL_OWN,
                    Table::ACTION_STREAM => Table::LEVEL_OWN,
                ],
                CaseObj::ENTITY_TYPE => [
                    Table::ACTION_READ => Table::LEVEL_ALL,
                    Table::ACTION_STREAM => Table::LEVEL_ALL,
                ],
                Meeting::ENTITY_TYPE => [
                    Table::ACTION_READ => Table::LEVEL_TEAM,
                    Table::ACTION_STREAM => Table::LEVEL_TEAM,
                ],
                Task::ENTITY_TYPE => [
                    Table::ACTION_READ => Table::LEVEL_OWN,
                    Table::ACTION_STREAM => Table::LEVEL_OWN,
                ],
            ],
        ]);

        $this->otherUser = $this->createUser([
            User::FIELD_USER_NAME => 'other',
            User::LINK_TEAMS . 'Ids' => [$this->otherTeam->getId()],
            User::LINK_DEFAULT_TEAM . 'Id' => $this->otherTeam->getId(),
        ]);
    }

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testParentAccess(): void
    {
        $account = $this->createRecordSilently(Account::ENTITY_TYPE, ['name' => 'Account']);
        $contact = $this->createRecordSilently(Contact::ENTITY_TYPE, ['lastName' => 'Contact']);
        $lead = $this->createRecordSilently(Lead::ENTITY_TYPE, ['lastName' => 'Lead']);

        $ownOpportunity = $this->createRecordSilently(Opportunity::ENTITY_TYPE, [
            'name' => 'Own',
            'assignedUserId' => $this->user->getId(),
            'teamsIds' => [$this->team->getId()],
        ]);

        $otherOpportunity = $this->createRecordSilently(Opportunity::ENTITY_TYPE, [
            'name' => 'Other',
            'assignedUserId' => $this->otherUser->getId(),
            // Being in the same team must not grant access with the 'own' level.
            'teamsIds' => [$this->team->getId()],
        ]);

        $accountNote = $this->createPost($account);
        $contactNote = $this->createPost($contact);
        $leadNote = $this->createPost($lead);
        $ownOpportunityNote = $this->createPost($ownOpportunity);
        $otherOpportunityNote = $this->createPost($otherOpportunity);

        $ids = $this->findIds();

        $this->assertContains($accountNote->getId(), $ids, 'Parent with all access.');
        $this->assertContains($ownOpportunityNote->getId(), $ids, 'Own parent with own access.');

        $this->assertNotContains($contactNote->getId(), $ids, 'Parent with no stream access.');
        $this->assertNotContains($leadNote->getId(), $ids, 'Parent with no read access.');
        $this->assertNotContains($otherOpportunityNote->getId(), $ids, 'Not own parent with own access.');
    }

    /**
     * Notes with a 'related' record the user has no access to must not be visible, even though
     * the parent is accessible. Access to 'related' is applied via `NoteTeam` and `NoteUser` and
     * respective conditions inferred from the access level.
     *
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testRelatedAccess(): void
    {
        $account = $this->createRecordSilently(Account::ENTITY_TYPE, ['name' => 'Account']);

        $otherOpportunity = $this->createRecordSilently(Opportunity::ENTITY_TYPE, [
            'name' => 'Other',
            'assignedUserId' => $this->otherUser->getId(),
        ]);

        $case = $this->createRecordSilently(CaseObj::ENTITY_TYPE, [
            'name' => 'Case',
            'assignedUserId' => $this->otherUser->getId(),
            'teamsIds' => [$this->otherTeam->getId()],
        ]);

        $meetingTeam = $this->createRecordSilently(Meeting::ENTITY_TYPE, [
            'name' => 'Meeting in user team',
            'assignedUserId' => $this->otherUser->getId(),
            'teamsIds' => [$this->team->getId()],
        ]);

        $meetingUser = $this->createRecordSilently(Meeting::ENTITY_TYPE, [
            'name' => 'Meeting assigned to user',
            'assignedUserId' => $this->user->getId(),
            'teamsIds' => [$this->otherTeam->getId()],
        ]);

        $meetingOther = $this->createRecordSilently(Meeting::ENTITY_TYPE, [
            'name' => 'Meeting of other',
            'assignedUserId' => $this->otherUser->getId(),
            'teamsIds' => [$this->otherTeam->getId()],
        ]);

        $meetingNone = $this->createRecordSilently(Meeting::ENTITY_TYPE, [
            'name' => 'Meeting with no teams and users',
        ]);

        $taskOwn = $this->createRecordSilently(Task::ENTITY_TYPE, [
            'name' => 'Task assigned to user',
            'assignedUserId' => $this->user->getId(),
            'teamsIds' => [$this->otherTeam->getId()],
        ]);

        $taskTeam = $this->createRecordSilently(Task::ENTITY_TYPE, [
            'name' => 'Task in user team',
            'assignedUserId' => $this->otherUser->getId(),
            'teamsIds' => [$this->team->getId()],
        ]);

        $taskOther = $this->createRecordSilently(Task::ENTITY_TYPE, [
            'name' => 'Task of other',
            'assignedUserId' => $this->otherUser->getId(),
            'teamsIds' => [$this->otherTeam->getId()],
        ]);

        // Notes without 'related' are not subject to related access control.
        $postNote = $this->createPost($account);

        // 'all' level. Teams and users of the note do not matter.
        $caseNote = $this->createRelatedNote(
            $account,
            $case,
            teamIds: [$this->otherTeam->getId()],
            userIds: [$this->otherUser->getId()],
        );

        // 'team' level.
        $meetingTeamNote = $this->createRelatedNote(
            parent: $account,
            related: $meetingTeam,
            teamIds: [$this->team->getId()],
            userIds: [$this->otherUser->getId()],
        );

        $meetingUserNote = $this->createRelatedNote(
            parent: $account,
            related: $meetingUser,
            teamIds: [$this->otherTeam->getId()],
            userIds: [$this->user->getId()],
        );

        $meetingOtherNote = $this->createRelatedNote(
            $account,
            related: $meetingOther,
            teamIds: [$this->otherTeam->getId()],
            userIds: [$this->otherUser->getId()],
        );

        $meetingNoneNote = $this->createRelatedNote(
            $account,
            related: $meetingNone,
            teamIds: [],
            userIds: [],
        );

        // 'own' level.
        $taskOwnNote = $this->createRelatedNote(
            parent: $account,
            related: $taskOwn,
            teamIds: [$this->otherTeam->getId()],
            userIds: [$this->user->getId()],
        );

        $taskTeamNote = $this->createRelatedNote(
            parent: $account,
            related: $taskTeam,
            teamIds: [$this->team->getId()],
            userIds: [$this->otherUser->getId()],
        );

        $taskOtherNote = $this->createRelatedNote(
            parent: $account,
            related: $taskOther,
            teamIds: [$this->otherTeam->getId()],
            userIds: [$this->otherUser->getId()],
        );

        // Accessible 'related', but not accessible parent.
        $otherOpportunityCaseNote = $this->createRelatedNote($otherOpportunity, $case, [], []);

        $ids = $this->findIds();

        $this->assertContains($postNote->getId(), $ids, 'No related.');
        $this->assertContains($caseNote->getId(), $ids, 'Related with all access.');

        $this->assertContains($meetingTeamNote->getId(), $ids, 'Team related, access through team.');
        $this->assertContains($meetingUserNote->getId(), $ids, 'Team related, access through user.');
        $this->assertNotContains($meetingOtherNote->getId(), $ids, 'Team related, no team or user match.');
        $this->assertNotContains($meetingNoneNote->getId(), $ids, 'Team related, no teams or users.');

        $this->assertContains($taskOwnNote->getId(), $ids, 'Own related, access through user.');
        $this->assertNotContains($taskTeamNote->getId(), $ids, 'Own related, team must not grant access.');
        $this->assertNotContains($taskOtherNote->getId(), $ids, 'Own related, no user match.');

        $this->assertNotContains($otherOpportunityCaseNote->getId(), $ids, 'Not accessible parent.');
    }

    /**
     * @return string[]
     * @noinspection PhpUnhandledExceptionInspection
     * @noinspection PhpSameParameterValueInspection
     */
    private function findIds(int $maxSize = 100): array
    {
        $this->authenticate(self::USER_NAME);

        $service = $this->getInjectableFactory()->create(GlobalRecordService::class);

        $collection = $service
            ->find(SearchParams::create()->withMaxSize($maxSize))
            ->getCollection();

        $ids = [];

        foreach ($collection as $note) {
            $ids[] = $note->getId();
        }

        return $ids;
    }

    /**
     * Created silently to prevent automatic stream notes.
     *
     * @param array<string, mixed> $data
     */
    private function createRecordSilently(string $entityType, array $data): Entity
    {
        return $this->getEntityManager()->createEntity($entityType, $data, [SaveOption::SILENT => true]);
    }

    private function createPost(Entity $parent): Note
    {
        $em = $this->getEntityManager();

        $note = $em->getRDBRepositoryByClass(Note::class)->getNew();

        $note
            ->setType(Note::TYPE_POST)
            ->setParent($parent)
            ->setPost('Test.');

        $em->saveEntity($note);

        return $note;
    }

    /**
     * @param string[] $teamIds
     * @param string[] $userIds
     */
    private function createRelatedNote(Entity $parent, Entity $related, array $teamIds, array $userIds): Note
    {
        $em = $this->getEntityManager();

        $note = $em->getRDBRepositoryByClass(Note::class)->getNew();

        $note
            ->setType(Note::TYPE_CREATE_RELATED)
            ->setParent($parent)
            ->setRelated($related)
            ->setTeamsIds($teamIds)
            ->setUsersIds($userIds);

        $em->saveEntity($note);

        return $note;
    }
}
