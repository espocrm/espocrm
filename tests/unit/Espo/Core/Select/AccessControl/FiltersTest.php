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

namespace tests\unit\Espo\Core\Select\AccessControl;

use Espo\Core\Field\LinkMultiple;
use Espo\Core\Field\LinkMultipleItem;
use Espo\Core\Portal\Acl\OwnershipChecker\MetadataProvider;
use Espo\Core\Select\AccessControl\Filter as AccessControlFilter;
use Espo\Core\Select\AccessControl\Filters\No;
use Espo\Core\Select\AccessControl\Filters\PortalOnlyAccount;
use Espo\Core\Select\AccessControl\Filters\PortalOnlyContact;
use Espo\Core\Select\AccessControl\Filters\PortalOnlyOwn;
use Espo\Core\Select\Helpers\FieldHelper;
use Espo\Core\Select\Helpers\RelationQueryHelper;
use Espo\Entities\User;
use Espo\Modules\Crm\Entities\Account;
use Espo\Modules\Crm\Entities\Contact;
use Espo\ORM\Defs;
use Espo\ORM\Defs\RelationDefs;
use Espo\ORM\Query\Part\Where\OrGroup;
use Espo\ORM\Query\SelectBuilder as QueryBuilder;
use Espo\ORM\Type\RelationType;
use PHPUnit\Framework\TestCase;

class FiltersTest extends TestCase
{
    private $queryBuilder;
    private $fieldHelper;
    private $user;
    private $entityType;

    protected function setUp(): void
    {
        $this->queryBuilder = $this->createMock(QueryBuilder::class);
        $this->fieldHelper = $this->createMock(FieldHelper::class);
        $this->user = $this->createMock(User::class);

        $this->user->set('id', 'user-id');

        $this->user
            ->expects($this->any())
            ->method('getId')
            ->willReturn('user-id');

        $this->entityType = 'Test';

        $this->user
            ->expects($this->any())
            ->method('getTeamIdList')
            ->willReturn(['team-id']);
    }

    public function testNo(): void
    {
        $filter = $this->createFilter(No::class);

        $this->queryBuilder
            ->expects($this->once())
            ->method('where')
            ->with([
                'id' => null,
            ]);

        $filter->apply($this->queryBuilder);
    }

    public function testPortalOnlyAccount1(): void
    {
        $defs = $this->createMock(Defs::class);
        $metadataProvider = $this->createMock(MetadataProvider::class);

        $queryHelper = new RelationQueryHelper($defs);

        $filter = new PortalOnlyAccount(
            'Test',
            $this->user,
            $this->fieldHelper,
            $metadataProvider,
            $queryHelper
        );

        $metadataProvider
            ->expects($this->any())
            ->method('getContactLink')
            ->willReturn(
                RelationDefs::fromRaw([
                    'type' => RelationType::BELONGS_TO_PARENT,
                    'entityList' => [Contact::ENTITY_TYPE],
                ], 'contact')
            );

        $metadataProvider
            ->expects($this->any())
            ->method('getAccountLink')
            ->willReturn(
                RelationDefs::fromRaw([
                    'type' => RelationType::MANY_MANY,
                    'entity' => [Account::ENTITY_TYPE],
                    'midKeys' => ['nId', 'fId'],
                    'relationName' => 'TestAccount'
                ], 'account')
            );

        $this->user
            ->expects($this->any())
            ->method('getAccounts')
            ->willReturn(LinkMultiple::create([
                LinkMultipleItem::create('account-id')
            ]));

        $this->user
            ->expects($this->any())
            ->method('getContactId')
            ->willReturn('contact-id');

        $this->initHelperMethods([
            ['hasCreatedByField', true],
        ]);

       $this->queryBuilder
            ->expects($this->once())
            ->method('where')
            ->with(
                $this->callback(function ($where) {
                    if (!$where instanceof OrGroup) {
                        return false;
                    }

                    if (!isset($where->getRawValue()[0]['id=s'])) {
                        return false;
                    }

                    return $where->getItemCount() === 3;
                })
            )
            ->willReturn($this->queryBuilder);

        $filter->apply($this->queryBuilder);
    }

    public function testPortalOnlyAccount2(): void
    {
        $defs = $this->createMock(Defs::class);
        $metadataProvider = $this->createMock(MetadataProvider::class);

        $queryHelper = new RelationQueryHelper($defs);

        $filter = new PortalOnlyAccount(
            'Test',
            $this->user,
            $this->fieldHelper,
            $metadataProvider,
            $queryHelper
        );

        $this->user
            ->expects($this->any())
            ->method('getLinkMultipleIdList')
            ->with('accounts')
            ->willReturn([]);

        $this->user
            ->expects($this->any())
            ->method('get')
            ->with('contactId')
            ->willReturn(null);

        $this->initHelperMethods([
            ['hasCreatedByField', false],
        ]);

        $this->queryBuilder
            ->expects($this->once())
            ->method('where')
            ->with([
                'id' => null,
            ]);

        $filter->apply($this->queryBuilder);
    }

    public function testPortalOnlyContact1(): void
    {
        $defs = $this->createMock(Defs::class);
        $metadataProvider = $this->createMock(MetadataProvider::class);

        $queryHelper = new RelationQueryHelper($defs);

        $filter = new PortalOnlyContact(
            'Test',
            $this->user,
            $this->fieldHelper,
            $metadataProvider,
            $queryHelper
        );

        $this->user
            ->expects($this->any())
            ->method('getContactId')
            ->willReturn('contact-id');

        $this->initHelperMethods([
            ['hasCreatedByField', true],
        ]);

        $metadataProvider
            ->expects($this->once())
            ->method('getContactLink')
            ->willReturn(
                RelationDefs::fromRaw([
                    'type' => RelationType::BELONGS_TO,
                    'entity' => Contact::ENTITY_TYPE,
                ], 'contact')
            );

        $this->queryBuilder
            ->expects($this->once())
            ->method('where')
            ->with(
                $this->callback(function ($where) {
                    return $where instanceof OrGroup &&
                        $where->getItemCount() === 2;
                })
            )
            ->willReturn($this->queryBuilder);

        $filter->apply($this->queryBuilder);
    }

    public function testPortalOnlyContact2()
    {
        $defs = $this->createMock(Defs::class);
        $metadataProvider = $this->createMock(MetadataProvider::class);

        $queryHelper = new RelationQueryHelper($defs);

        $filter = new PortalOnlyContact(
            'Test',
            $this->user,
            $this->fieldHelper,
            $metadataProvider,
            $queryHelper
        );

        $this->user
            ->expects($this->any())
            ->method('get')
            ->with('contactId')
            ->willReturn(null);

        $this->initHelperMethods([
            ['hasCreatedByField', false],
        ]);

        $this->queryBuilder
            ->expects($this->once())
            ->method('where')
            ->with([
                'id' => null,
            ])
            ->willReturn($this->queryBuilder);

        $filter->apply($this->queryBuilder);
    }

    public function testPortalOnlyOwn1()
    {
        $filter = $this->createFilter(PortalOnlyOwn::class);

        $this->initHelperMethods([
            ['hasCreatedByField', true],
        ]);

        $this->queryBuilder
            ->expects($this->once())
            ->method('where')
            ->with([
                'createdById' => $this->user->getId(),
            ])
            ->willReturn($this->queryBuilder);

        $filter->apply($this->queryBuilder);
    }

    public function testPortalOnlyOwn2()
    {
        $filter = $this->createFilter(PortalOnlyOwn::class);

        $this->initHelperMethods([
            ['hasCreatedByField', false],
        ]);

        $this->queryBuilder
            ->expects($this->once())
            ->method('where')
            ->with([
                'id' => null,
            ])
            ->willReturn($this->queryBuilder);

        $filter->apply($this->queryBuilder);
    }

    protected function initHelperMethods(array $map)
    {
        foreach ($map as $i => $item) {
            $this->fieldHelper
                ->expects($this->once())
                ->method($item[0])
                ->willReturn($item[1]);
        }
    }

    protected function createFilter(string $className): AccessControlFilter
    {
        return new $className(
            $this->user,
            $this->fieldHelper
        );
    }
}
