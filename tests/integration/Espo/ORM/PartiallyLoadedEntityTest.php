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
use tests\integration\Core\BaseTestCase;

class PartiallyLoadedEntityTest extends BaseTestCase
{
    private const string DESCRIPTION = 'Original description';
    private const string WEBSITE = 'https://original.example.com';
    private const string SIC_CODE = '1234';

    public function testEnsureFullyLoaded(): void
    {
        $id = $this->createAccount();
        $account = $this->fetchPartial($id);

        $this->assertTrue($account->isPartiallyLoaded());

        $account->ensureFullyLoaded();

        $this->assertFalse($account->isPartiallyLoaded());
    }

    /**
     * @noinspection PhpConditionAlreadyCheckedInspection
     */
    public function testLeadedAfterRefresh(): void
    {
        $id = $this->createAccount();
        $account = $this->fetchPartial($id);

        $this->assertTrue($account->isPartiallyLoaded());

        $this->getEntityManager()->refreshEntity($account);

        $this->assertFalse($account->isPartiallyLoaded());
    }

    public function testGetUnloadedAttributeTriggersLoad(): void
    {
        $id = $this->createAccount();
        $account = $this->fetchPartial($id);

        $this->assertEquals(self::DESCRIPTION, $account->getDescription());
        $this->assertTrue($account->has('website'));
        $this->assertEquals(self::WEBSITE, $account->get('website'));
    }

    public function testGetValueMapDoesNotTriggerLoad(): void
    {
        $id = $this->createAccount();
        $account = $this->fetchPartial($id);

        $valueMap = get_object_vars($account->getValueMap());

        $this->assertArrayNotHasKey('description', $valueMap);
        $this->assertArrayHasKey('name', $valueMap);

        // If getValueMap had loaded the entity, the old value would be returned below.
        $this->updateDescriptionDirectly($id, 'Changed behind the entity');

        $this->assertEquals('Changed behind the entity', $account->get('description'));
    }

    public function testClearedAttributeIsNotLoadedOnGet(): void
    {
        $id = $this->createAccount();
        $account = $this->fetchPartial($id);

        $account->clear('description');

        $this->assertFalse($account->has('description'));
        $this->assertNull($account->get('description'));
    }

    public function testClearedAttributesSurviveLoadTriggeredByAnotherAttribute(): void
    {
        $id = $this->createAccount();
        $account = $this->fetchPartial($id);

        $account->clear('description');
        $account->clear('website');

        // Triggers the full load.
        $this->assertEquals(self::SIC_CODE, $account->get('sicCode'));

        $this->assertFalse($account->has('description'));
        $this->assertFalse($account->has('website'));

        $valueMap = get_object_vars($account->getValueMap());

        $this->assertArrayNotHasKey('description', $valueMap);
        $this->assertArrayNotHasKey('website', $valueMap);
    }

    public function testClearedAttributesSurviveLoadTriggeredFromFetched(): void
    {
        $id = $this->createAccount();
        $account = $this->fetchPartial($id);

        // Simulates field-level ACL clearing before output.
        $account->clear('description');
        $account->clear('website');

        // Triggers the full load via the fetched side.
        $this->assertEquals(self::SIC_CODE, $account->getFetched('sicCode'));

        $this->assertFalse($account->has('description'));
        $this->assertFalse($account->has('website'));

        // Fetched values of cleared attributes are still the real DB values.
        $this->assertEquals(self::DESCRIPTION, $account->getFetched('description'));
        $this->assertTrue($account->hasFetched('website'));
    }

    public function testValueSetAfterClearSurvivesLoad(): void
    {
        $id = $this->createAccount();
        $account = $this->fetchPartial($id);

        $account->clear('description');
        $account->set('description', 'New description');

        // Fetched value is unknown, so this triggers the full load.
        $this->assertEquals(self::DESCRIPTION, $account->getFetched('description'));

        $this->assertEquals('New description', $account->get('description'));
        $this->assertTrue($account->isAttributeChanged('description'));
    }

    public function testSetUnloadedAttributeComparesWithDbValue(): void
    {
        $id = $this->createAccount();

        $account = $this->fetchPartial($id);
        $account->set('description', self::DESCRIPTION);

        $this->assertFalse($account->isAttributeChanged('description'));

        $account = $this->fetchPartial($id);
        $account->set('description', 'Different');

        $this->assertTrue($account->isAttributeChanged('description'));
    }

    public function testSavePartialDoesNotOverwriteUnloadedAttributes(): void
    {
        $id = $this->createAccount();
        $account = $this->fetchPartial($id);

        $account->set('name', 'Renamed');

        $this->getEntityManager()->saveEntity($account);

        $reloaded = $this->getEntityManager()->getEntityById(Account::ENTITY_TYPE, $id);

        $this->assertNotNull($reloaded);
        $this->assertEquals('Renamed', $reloaded->get('name'));
        $this->assertEquals(self::DESCRIPTION, $reloaded->get('description'));
        $this->assertEquals(self::WEBSITE, $reloaded->get('website'));
    }

    public function testSavePartialDoesNotWriteClearedAttributes(): void
    {
        $id = $this->createAccount();
        $account = $this->fetchPartial($id);

        $account->clear('description');
        $account->set('name', 'Renamed');

        $this->getEntityManager()->saveEntity($account);

        $reloaded = $this->getEntityManager()->getEntityById(Account::ENTITY_TYPE, $id);

        $this->assertNotNull($reloaded);
        $this->assertEquals(self::DESCRIPTION, $reloaded->get('description'));
    }

    private function createAccount(): string
    {
        $account = $this->getEntityManager()->createEntity(Account::ENTITY_TYPE, [
            'name' => 'Test',
            'description' => self::DESCRIPTION,
            'website' => self::WEBSITE,
            'sicCode' => self::SIC_CODE,
        ]);

        return $account->getId();
    }

    private function fetchPartial(string $id): Account
    {
        $account = $this->getEntityManager()
            ->getRDBRepositoryByClass(Account::class)
            ->select([Attribute::ID, Field::NAME])
            ->where([Attribute::ID => $id])
            ->findOne();

        $this->assertInstanceOf(BaseEntity::class, $account);

        // Remove once the mapper marks entities fetched with a select as partially loaded.
        $account->setAsPartiallyLoaded();

        return $account;
    }

    /**
     * @noinspection PhpSameParameterValueInspection
     */
    private function updateDescriptionDirectly(string $id, string $description): void
    {
        $query = $this->getEntityManager()
            ->getQueryBuilder()
            ->update()
            ->in(Account::ENTITY_TYPE)
            ->set(['description' => $description])
            ->where([Attribute::ID => $id])
            ->build();

        $this->getEntityManager()->getQueryExecutor()->execute($query);
    }
}

