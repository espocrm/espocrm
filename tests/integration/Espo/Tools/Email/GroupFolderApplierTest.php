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

namespace tests\integration\Espo\Tools\Email;

use Espo\Core\Field\LinkMultiple;
use Espo\Entities\Email;
use Espo\Entities\GroupEmailFolder;
use Espo\Entities\Team;
use Espo\Entities\User;
use Espo\Tools\Email\GroupFolderApplier;
use tests\integration\Core\BaseTestCase;

class GroupFolderApplierTest extends BaseTestCase
{
    public function testApply(): void
    {
        $em = $this->getEntityManager();

        $team = $this->getEntityManager()->getRDBRepositoryByClass(Team::class)->getNew();
        $em->saveEntity($team);

        $user1 = $this->getEntityManager()->getRDBRepositoryByClass(User::class)->getNew();
        $user1
            ->setUserName('test-1')
            ->setTeams(LinkMultiple::create()->withAddedId($team->getId()));
        $em->saveEntity($user1);

        $folder = $em->getRDBRepositoryByClass(GroupEmailFolder::class)->getNew();
        $folder->setTeams(LinkMultiple::create()->withAddedId($team->getId()));
        $em->saveEntity($folder);

        $email = $em->getRDBRepositoryByClass(Email::class)->getNew();

        $applier = $this->getInjectableFactory()->create(GroupFolderApplier::class);

        $applier->apply($email, $folder);

        $this->assertContains($user1->getId(), $email->getUsers()->getIdList());
        $this->assertContains($team->getId(), $email->getTeams()->getIdList());
    }
}
