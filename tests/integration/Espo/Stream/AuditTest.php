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

namespace tests\integration\Espo\Stream;

use Espo\Core\Acl\Table;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Record\CreateParams;
use Espo\Core\Record\ServiceContainer;
use Espo\Core\Record\UpdateParams;
use Espo\Core\Select\SearchParams;
use Espo\Entities\Note;
use Espo\Modules\Crm\Entities\KnowledgeBaseArticle;
use Espo\Tools\Stream\RecordService;
use tests\integration\Core\BaseTestCase;

class AuditTest extends BaseTestCase
{
    public function testAudit1(): void
    {
        $this->createUser('test1', [
            'auditPermission' => Table::LEVEL_YES,
            'data' => [
                KnowledgeBaseArticle::ENTITY_TYPE => [
                    'create' => Table::LEVEL_NO,
                    'read' => Table::LEVEL_ALL,
                ],
            ]
        ]);

        $this->createUser('test2', [
            'auditPermission' => Table::LEVEL_NO,
            'data' => [
                KnowledgeBaseArticle::ENTITY_TYPE => [
                    'create' => Table::LEVEL_NO,
                    'read' => Table::LEVEL_ALL,
                ],
            ]
        ]);

        $this->createUser('test3', [
            'auditPermission' => Table::LEVEL_NO,
            'data' => [
                KnowledgeBaseArticle::ENTITY_TYPE => [
                    'create' => Table::LEVEL_NO,
                    'read' => Table::LEVEL_NO,
                ],
            ]
        ]);

        $this->getMetadata()->set('entityDefs', KnowledgeBaseArticle::ENTITY_TYPE, [
            'fields' => [
                'publishDate' => [
                    'audited' => true,
                ],
            ],
        ]);
        $this->getMetadata()->save();

        $this->reCreateApplication();

        $service = $this->getContainer()
            ->getByClass(ServiceContainer::class)
            ->getByClass(KnowledgeBaseArticle::class);

        /** @noinspection PhpUnhandledExceptionInspection */
        $article = $service->create((object) [
            'name' => 'Test 1',
            'publishDate' => '2025-01-01',
        ], CreateParams::create());

        /** @noinspection PhpUnhandledExceptionInspection */
        $service->update($article->getId(), (object) [
            'publishDate' => '2025-01-02',
        ], UpdateParams::create());

        $em = $this->getEntityManager();

        $note = $em->getRDBRepositoryByClass(Note::class)
            ->where([
                'parentId' => $article->getId(),
                'parentType' => $article->getEntityType(),
                'type' => Note::TYPE_UPDATE,
            ])
            ->findOne();

        $this->assertNotNull($note);
        $this->assertEquals(['publishDate'], $note->getData()?->fields);

        $searchParams = SearchParams::create();

        $this->auth('test1');
        $this->reCreateApplication();

        $service = $this->getInjectableFactory()->create(RecordService::class);
        /** @noinspection PhpUnhandledExceptionInspection */
        $recordCollection = $service->findUpdates(KnowledgeBaseArticle::ENTITY_TYPE, $article->getId(), $searchParams);

        $this->assertCount(1, $recordCollection->getCollection()->getValueMapList());

        $this->auth('test2');
        $this->reCreateApplication();

        $isThrown = false;

        try {
            $service = $this->getInjectableFactory()->create(RecordService::class);
            /** @noinspection PhpUnhandledExceptionInspection */
            $service->findUpdates(KnowledgeBaseArticle::ENTITY_TYPE, $article->getId(), $searchParams);
        } catch (Forbidden) {
            $isThrown = true;
        }

        $this->assertTrue($isThrown);

        $this->auth('test3');
        $this->reCreateApplication();

        $isThrown = false;

        try {
            $service = $this->getInjectableFactory()->create(RecordService::class);
            /** @noinspection PhpUnhandledExceptionInspection */
            $service->findUpdates(KnowledgeBaseArticle::ENTITY_TYPE, $article->getId(), $searchParams);
        } catch (Forbidden) {
            $isThrown = true;
        }

        $this->assertTrue($isThrown);
    }
}
