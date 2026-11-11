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

namespace tests\integration\Espo\ORM;

use Espo\Core\Name\Field;
use Espo\Modules\Crm\Entities\Account;
use Espo\ORM\BaseEntity;
use Espo\ORM\Name\Attribute;
use Espo\ORM\Repository\RDBRepository;
use tests\integration\Core\BaseTestCase;

class PartiallyLoadedRepositoryTest extends BaseTestCase
{
    private const string DESCRIPTION = 'Original description';

    public function testFindWithSelect(): void
    {
        $id = $this->createAccount();

        $collection = $this->getRepository()
            ->select([Attribute::ID, Field::NAME])
            ->where([Attribute::ID => $id])
            ->find();

        $this->assertPartial($this->getSingle($collection));
    }

    public function testFindWithSelectSth(): void
    {
        $id = $this->createAccount();

        $collection = $this->getRepository()
            ->select([Attribute::ID, Field::NAME])
            ->where([Attribute::ID => $id])
            ->sth()
            ->find();

        $this->assertPartial($this->getSingle($collection));
    }

    public function testFindOneWithSelect(): void
    {
        $id = $this->createAccount();

        $account = $this->getRepository()
            ->select([Attribute::ID, Field::NAME])
            ->where([Attribute::ID => $id])
            ->findOne();

        $this->assertNotNull($account);
        $this->assertPartial($account);
    }

    public function testFindOneWithSelectSth(): void
    {
        $id = $this->createAccount();

        $account = $this->getRepository()
            ->select([Attribute::ID, Field::NAME])
            ->where([Attribute::ID => $id])
            ->sth()
            ->findOne();

        $this->assertNotNull($account);
        $this->assertPartial($account);
    }

    public function testFindWithoutSelect(): void
    {
        $id = $this->createAccount();

        $collection = $this->getRepository()
            ->where([Attribute::ID => $id])
            ->find();

        $this->assertNotPartial($this->getSingle($collection));
    }

    public function testFindWithoutSelectSth(): void
    {
        $id = $this->createAccount();

        $collection = $this->getRepository()
            ->where([Attribute::ID => $id])
            ->sth()
            ->find();

        $this->assertNotPartial($this->getSingle($collection));
    }

    public function testFindOneWithoutSelect(): void
    {
        $id = $this->createAccount();

        $account = $this->getRepository()
            ->where([Attribute::ID => $id])
            ->findOne();

        $this->assertNotNull($account);
        $this->assertNotPartial($account);
    }

    public function testFindOneWithoutSelectSth(): void
    {
        $id = $this->createAccount();

        $account = $this->getRepository()
            ->where([Attribute::ID => $id])
            ->sth()
            ->findOne();

        $this->assertNotNull($account);
        $this->assertNotPartial($account);
    }

    public function testGetById(): void
    {
        $id = $this->createAccount();

        $account = $this->getRepository()->getById($id);

        $this->assertNotNull($account);
        $this->assertNotPartial($account);
    }

    private function assertPartial(BaseEntity $entity): void
    {
        $this->assertTrue($entity->isPartiallyLoaded());

        // Behavioral check: the unselected attribute is loaded lazily.
        $this->assertEquals(self::DESCRIPTION, $entity->get('description'));
        $this->assertFalse($entity->isPartiallyLoaded());
    }

    private function assertNotPartial(BaseEntity $entity): void
    {
        $this->assertFalse($entity->isPartiallyLoaded());
        $this->assertEquals(self::DESCRIPTION, $entity->get('description'));
    }

    /**
     * @param iterable<BaseEntity> $collection
     */
    private function getSingle(iterable $collection): BaseEntity
    {
        $list = [];

        foreach ($collection as $entity) {
            $list[] = $entity;
        }

        $this->assertCount(1, $list);

        return $list[0];
    }

    /**
     * @return RDBRepository<Account>
     */
    private function getRepository(): RDBRepository
    {
        return $this->getEntityManager()->getRDBRepositoryByClass(Account::class);
    }

    private function createAccount(): string
    {
        $account = $this->getEntityManager()->createEntity(Account::ENTITY_TYPE, [
            Field::NAME => 'Test',
            'description' => self::DESCRIPTION,
        ]);

        return $account->getId();
    }
}
