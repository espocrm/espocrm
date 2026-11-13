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

namespace tests\integration\Espo\ORM\Repository;

use Espo\Entities\User;
use Espo\Modules\Crm\Entities\Account;
use Espo\Modules\Crm\Entities\Contact;
use Espo\Modules\Crm\Entities\Opportunity;
use Espo\ORM\Entity;
use Espo\ORM\EntityManager;
use Espo\ORM\Name\Attribute;
use Espo\ORM\Relation\Relations;
use Espo\ORM\Relation\RelationsMap;
use ReflectionProperty;
use RuntimeException;
use tests\integration\Core\BaseTestCase;

class EagerLoadTest extends BaseTestCase
{
    public function testBelongsTo(): void
    {
        $em = $this->getEntityManager();

        $account = $em->createEntity(Account::ENTITY_TYPE, [
            'name' => 'Acme',
            'website' => 'acme.com',
        ]);

        $opportunity = $em->createEntity(Opportunity::ENTITY_TYPE, [
            'name' => 'Deal',
            'accountId' => $account->getId(),
        ]);

        $loaded = $em->getRDBRepository(Opportunity::ENTITY_TYPE)
            ->eager('account', ['name'])
            ->where(['id' => $opportunity->getId()])
            ->find();

        $this->assertCount(1, $loaded);

        $loadedOpportunity = $loaded[0];

        // Changed after loading. If accessing the relation runs a query, the new name is returned.
        $this->updateInDb(Account::ENTITY_TYPE, $account->getId(), ['name' => 'Changed']);

        $loadedAccount = $this->getRelations($loadedOpportunity)->getOne('account');

        $this->assertInstanceOf(Account::class, $loadedAccount);

        $this->assertNotNull($loadedAccount);
        $this->assertEquals($account->getId(), $loadedAccount->getId());
        $this->assertEquals('Acme', $loadedAccount->get('name'));

        $loadedAccount->setAsNotPartiallyLoaded();
        $this->assertFalse($loadedAccount->has('website'));
    }

    public function testFindOneAndSth(): void
    {
        $em = $this->getEntityManager();

        $account = $em->createEntity(Account::ENTITY_TYPE, ['name' => 'Acme']);

        $opportunity = $em->createEntity(Opportunity::ENTITY_TYPE, [
            'name' => 'Deal',
            'accountId' => $account->getId(),
        ]);

        $oppRepo = $em->getRDBRepository(Opportunity::ENTITY_TYPE);

        $one = $oppRepo
            ->eager('account', ['name'])
            ->where([Attribute::ID => $opportunity->getId()])
            ->findOne();

        $this->assertNotNull($one);
        $this->assertEquals('Acme', $this->getRelations($one)->getOne('account')?->get('name'));

        $collection = $oppRepo
            ->eager('account', ['name'])
            ->sth()
            ->where([Attribute::ID => $opportunity->getId()])
            ->find();

        $count = 0;

        foreach ($collection as $item) {
            $this->assertEquals('Acme', $this->getRelations($item)->getOne('account')?->get('name'));

            $count++;
        }

        $this->assertEquals(1, $count);
    }

    public function testHasOne(): void
    {
        $em = $this->getEntityManager();

        $contact = $em->createEntity(Contact::ENTITY_TYPE, ['lastName' => 'Test']);

        $user = $em->createEntity(User::ENTITY_TYPE, [
            'userName' => 'eager-portal-user',
            'type' => User::TYPE_PORTAL,
            'contactId' => $contact->getId(),
        ]);

        $loadedContact = $em->getRDBRepositoryByClass(Contact::class)
            ->eager('portalUser', ['userName'])
            ->where([Attribute::ID => $contact->getId()])
            ->findOne();

        $this->assertNotNull($loadedContact);

        $loadedUser = $this->getRelations($loadedContact)->getOne('portalUser');

        $this->assertNotNull($loadedUser);
        $this->assertEquals($user->getId(), $loadedUser->getId());
        $this->assertEquals('eager-portal-user', $loadedUser->get(User::FIELD_USER_NAME));
    }

    public function testHasOneNoMatch(): void
    {
        $em = $this->getEntityManager();

        $contact = $em->createEntity(Contact::ENTITY_TYPE, ['lastName' => 'Test']);

        $loadedContact = $em->getRDBRepositoryByClass(Contact::class)
            ->eager('portalUser', [User::FIELD_USER_NAME])
            ->where([Attribute::ID => $contact->getId()])
            ->findOne();

        $this->assertNotNull($loadedContact);

        // Linked after loading. If accessing the relation runs a query, the user is returned.
        $em->createEntity(User::ENTITY_TYPE, [
            User::FIELD_USER_NAME => 'eager-portal-user',
            User::FIELD_TYPE => User::TYPE_PORTAL,
            'contactId' => $contact->getId(),
        ]);

        $this->assertNull($this->getRelations($loadedContact)->getOne('portalUser'));
    }

    public function testSaveDoesNotWriteEagerLoadedHasOne(): void
    {
        $em = $this->getEntityManager();

        $contact = $em->createEntity(Contact::ENTITY_TYPE, ['lastName' => 'Test']);

        $user = $em->createEntity(User::ENTITY_TYPE, [
            User::FIELD_USER_NAME => 'eager-portal-user',
            User::FIELD_TYPE => User::TYPE_PORTAL,
            'contactId' => $contact->getId(),
        ]);

        $loadedContact = $em->getRDBRepositoryByClass(Contact::class)
            ->eager('portalUser', [User::FIELD_USER_NAME])
            ->where([Attribute::ID => $contact->getId()])
            ->findOne();

        $this->assertNotNull($loadedContact);

        // Unlinked after loading. If saving treats eager data as a pending change, the link is restored.
        $this->updateInDb(User::ENTITY_TYPE, $user->getId(), ['contactId' => null]);

        $loadedContact->set('lastName', 'Changed');

        $em->saveEntity($loadedContact);

        $reloadedUser = $em->getEntityById(User::ENTITY_TYPE, $user->getId());

        $this->assertNotNull($reloadedUser);
        $this->assertNull($reloadedUser->get('contactId'));
    }

    /**
     * @param array<string, mixed> $values
     */
    private function updateInDb(string $entityType, string $id, array $values): void
    {
        $em = $this->getEntityManager();

        $query = $em->getQueryBuilder()
            ->update()
            ->in($entityType)
            ->set($values)
            ->where([Attribute::ID => $id])
            ->build();

        $em->getQueryExecutor()->execute($query);
    }

    private function getRelations(Entity $entity): Relations
    {
        /** @var RelationsMap $relationsMap */
        $relationsMap = (new ReflectionProperty(EntityManager::class, 'relationsMap'))
            ->getValue($this->getEntityManager());

        return $relationsMap->get($entity) ?? throw new RuntimeException("No relations.");
    }
}
