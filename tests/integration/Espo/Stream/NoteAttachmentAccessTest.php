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
use Espo\Core\AclManager;
use Espo\Core\Name\Field;
use Espo\Core\ORM\Repository\Option\SaveOption;
use Espo\Entities\Attachment;
use Espo\Entities\Note;
use Espo\Entities\Role;
use Espo\Entities\User;
use Espo\Modules\Crm\Entities\Account;
use tests\integration\Core\BaseTestCase;

class NoteAttachmentAccessTest extends BaseTestCase
{
    /**
     * An attachment of a stream post must require stream access to the post's parent,
     * not only read access.
     */
    public function testStreamAccessRequired(): void
    {
        $em = $this->getEntityManager();

        $poster = $this->createUser('poster');

        $noStreamUser = $this->createUser('noStream', [
            Role::FIELD_DATA => [
                Account::ENTITY_TYPE => [
                    Table::ACTION_READ => Table::LEVEL_ALL,
                    Table::ACTION_STREAM => Table::LEVEL_NO,
                ],
            ],
        ]);

        $streamUser = $this->createUser('stream', [
            Role::FIELD_DATA => [
                Account::ENTITY_TYPE => [
                    Table::ACTION_READ => Table::LEVEL_ALL,
                    Table::ACTION_STREAM => Table::LEVEL_ALL,
                ],
            ],
        ]);

        $account = $em->createEntity(Account::ENTITY_TYPE, [Field::NAME => 'Test']);

        $note = $em->getRDBRepositoryByClass(Note::class)->getNew()
            ->setParent($account)
            ->setType(Note::TYPE_POST)
            ->setPost('Test.');
        $em->saveEntity($note, [SaveOption::CREATED_BY_ID => $poster->getId()]);

        $attachment = $em->getRDBRepositoryByClass(Attachment::class)->getNew()
            ->setName('test.txt')
            ->setType('text/plain')
            ->setRole(Attachment::ROLE_ATTACHMENT)
            ->setTargetField(Note::FIELD_ATTACHMENTS)
            ->setParent($note);
        $em->saveEntity($attachment, [SaveOption::CREATED_BY_ID => $poster->getId()]);

        $noStreamUser = $em->getRDBRepositoryByClass(User::class)->getById($noStreamUser->getId());
        $streamUser = $em->getRDBRepositoryByClass(User::class)->getById($streamUser->getId());

        $this->assertNotNull($noStreamUser);
        $this->assertNotNull($streamUser);

        $aclManager = $this->getContainer()->getByClass(AclManager::class);

        $this->assertTrue($aclManager->checkEntityRead($noStreamUser, $account));
        $this->assertFalse($aclManager->checkEntityStream($noStreamUser, $account));

        $this->assertFalse($aclManager->checkEntityRead($noStreamUser, $note));
        $this->assertFalse($aclManager->checkEntityRead($noStreamUser, $attachment));

        $this->assertTrue($aclManager->checkEntityRead($streamUser, $note));
        $this->assertTrue($aclManager->checkEntityRead($streamUser, $attachment));
    }
}
