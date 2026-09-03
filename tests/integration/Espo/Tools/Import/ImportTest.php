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

namespace integration\Espo\Tools\Import;

use Espo\Core\Acl\Table;
use Espo\Entities\Attachment;
use Espo\Entities\Role;
use Espo\Entities\User;
use Espo\Modules\Crm\Entities\Account;
use Espo\Modules\Crm\Entities\Contact;
use Espo\Tools\Import\Import;
use Espo\Tools\Import\Params;
use tests\integration\Core\BaseTestCase;

class ImportTest extends BaseTestCase
{
    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testRelateOnImport(): void
    {
        $metadata = $this->getMetadata();

        $metadata->set('entityDefs', Contact::ENTITY_TYPE, [
            'fields' => [
                'accountType' => [
                    'relateOnImport' => true,
                ]
            ],
        ]);

        $metadata->save();

        //

        $this->createUser('test', [
            Role::FIELD_DATA => [
                'Import' => true,
                Account::ENTITY_TYPE => [
                    Table::ACTION_READ => Table::LEVEL_ALL,
                ],
                Contact::ENTITY_TYPE => [
                    Table::ACTION_CREATE => Table::LEVEL_YES,
                    Table::ACTION_READ => Table::LEVEL_ALL,
                    Table::ACTION_EDIT => Table::LEVEL_ALL,
                ],
            ],
        ]);

        $this->authenticate('test');

        $em = $this->getEntityManager();

        //

        $attachment = $em->getRDBRepositoryByClass(Attachment::class)->getNew();
        $attachment
            ->setName('test.csv')
            ->setType('text/csv')
            ->setRole(Import::FILE_ROLE)
            ->setContents(
                <<<'EOT'
                lastName,accountType
                Test,Partner
                ```
                EOT
            );
        $em->saveEntity($attachment);

        $account = $em->getRDBRepositoryByClass(Account::class)->getNew();
        $account->setType(Account::TYPE_PARTNER);
        $em->saveEntity($account);

        $import = $this->getInjectableFactory()->create(Import::class);

        $import
            ->setUser($this->getContainer()->getByClass(User::class))
            ->setEntityType(Contact::ENTITY_TYPE)
            ->setParams(
                Params::create()
            )
            ->setAttachmentId($attachment->getId())
            ->setAttributeList(['lastName', 'accountType']);

        $import->run();

        $contact = $em->getRDBRepositoryByClass(Contact::class)
            ->where(['lastName' => 'Test'])
            ->findOne();

        $this->assertNotNull($contact);
        $this->assertEquals($account->getId(), $contact->getAccount()?->getId());
    }

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testRelateByForeignName(): void
    {
        $this->createUser('test', [
            Role::FIELD_DATA => [
                'Import' => true,
                Account::ENTITY_TYPE => [
                    Table::ACTION_READ => Table::LEVEL_ALL,
                ],
                Contact::ENTITY_TYPE => [
                    Table::ACTION_CREATE => Table::LEVEL_YES,
                    Table::ACTION_READ => Table::LEVEL_ALL,
                    Table::ACTION_EDIT => Table::LEVEL_ALL,
                ],
            ],
        ]);

        $this->authenticate('test');

        $em = $this->getEntityManager();

        //

        $attachment = $em->getRDBRepositoryByClass(Attachment::class)->getNew();
        $attachment
            ->setName('test.csv')
            ->setType('text/csv')
            ->setRole(Import::FILE_ROLE)
            ->setContents(
                <<<'EOT'
                lastName,accountName
                Test,Hello
                ```
                EOT
            );
        $em->saveEntity($attachment);

        $account = $em->getRDBRepositoryByClass(Account::class)->getNew();
        $account->setName('Hello');
        $em->saveEntity($account);

        $import = $this->getInjectableFactory()->create(Import::class);

        $import
            ->setEntityType(Contact::ENTITY_TYPE)
            ->setParams(
                Params::create()
            )
            ->setAttachmentId($attachment->getId())
            ->setAttributeList(['lastName', 'accountName']);

        $import->run();

        $contact = $em->getRDBRepositoryByClass(Contact::class)
            ->where(['lastName' => 'Test'])
            ->findOne();

        $this->assertNotNull($contact);
        $this->assertEquals($account->getId(), $contact->getAccount()?->getId());
    }

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testDefaultValues(): void
    {
        $this->createUser('test', [
            Role::FIELD_DATA => [
                'Import' => true,
                Account::ENTITY_TYPE => [
                    Table::ACTION_CREATE => Table::LEVEL_YES,
                    Table::ACTION_READ => Table::LEVEL_ALL,
                ],
            ],
            Role::FIELD_FIELD_DATA => [
                Account::ENTITY_TYPE => [
                    'type' => [
                        Table::ACTION_READ => Table::LEVEL_YES,
                        Table::ACTION_EDIT => Table::LEVEL_NO,
                    ],
                ]
            ]
        ]);

        $this->authenticate('test');

        $em = $this->getEntityManager();

        //

        $attachment = $em->getRDBRepositoryByClass(Attachment::class)->getNew();
        $attachment
            ->setName('test.csv')
            ->setType('text/csv')
            ->setRole(Import::FILE_ROLE)
            ->setContents(
                <<<'EOT'
                name
                Test
                ```
                EOT
            );
        $em->saveEntity($attachment);

        $account = $em->getRDBRepositoryByClass(Account::class)->getNew();
        $account->setType(Account::TYPE_PARTNER);
        $em->saveEntity($account);

        $import = $this->getInjectableFactory()->create(Import::class);

        $import
            ->setEntityType(Account::ENTITY_TYPE)
            ->setParams(
                Params::create()->withDefaultValues([
                    'type' => Account::TYPE_PARTNER,
                    'description' => 'Test.',
                ])
            )
            ->setAttachmentId($attachment->getId())
            ->setAttributeList(['name']);

        $import->run();

        //

        $account = $em->getRDBRepositoryByClass(Account::class)
            ->where(['name' => 'Test'])
            ->findOne();

        $this->assertNotNull($account);
        $this->assertNull($account->getType());
        $this->assertEquals('Test.', $account->getDescription());
    }
}
