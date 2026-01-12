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

namespace tests\integration\Espo\Record;

use Espo\Core\Record\CreateParams;
use Espo\Core\Record\Duplicator\EntityDuplicator;
use Espo\Core\Record\ServiceContainer;
use Espo\Entities\Email;
use Espo\Entities\User;
use Espo\Modules\Crm\Entities\Account;
use Espo\Modules\Crm\Entities\Task;
use tests\integration\Core\BaseTestCase;

class EntityDuplicatorTest extends BaseTestCase
{
    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testDuplicate(): void
    {
        $user = $this->createUser([
            'userName' => 'test',
            'type' => User::TYPE_ADMIN,
        ]);

        $this->auth('test');
        $this->reCreateApplication();

        $em = $this->getEntityManager();

        $account = $em->getRDBRepositoryByClass(Account::class)->getNew();
        $account->setName('Account');
        $em->saveEntity($account);

        $email = $em->getRDBRepositoryByClass(Email::class)->getNew();
        $email->setSubject('Test');
        $email->setParent($account);
        $em->saveEntity($email);

        $taskService = $this->getContainer()
            ->getByClass(ServiceContainer::class)
            ->getByClass(Task::class);

        $task = $taskService->create((object) [
            'name' => 'Task',
            'originalEmailId' => $email->getId(),
            'parentId' => $account->getId(),
            'parentType' => $account->getEntityType(),
            'assignedUserId' => $user->getId(),
        ], CreateParams::create());

        $this->assertEquals($email->getId(), $task->get('emailId'));

        $duplicator = $this->getInjectableFactory()->create(EntityDuplicator::class);

        $values = $duplicator->duplicate($task);

        $this->assertEquals($account->getId(), $values->accountId ?? null);
        $this->assertFalse(isset($values->emailId));
    }
}
