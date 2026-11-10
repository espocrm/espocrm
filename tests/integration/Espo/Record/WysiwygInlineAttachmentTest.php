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

use Espo\Core\Acl\Table;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Name\Field;
use Espo\Core\ORM\Repository\Option\SaveOption;
use Espo\Core\Record\CreateParams;
use Espo\Core\Record\Service;
use Espo\Core\Record\ServiceContainer;
use Espo\Core\Record\UpdateParams;
use Espo\Entities\Attachment;
use Espo\Entities\Role;
use Espo\Entities\User;
use Espo\Modules\Crm\Entities\KnowledgeBaseArticle;
use tests\integration\Core\BaseTestCase;

class WysiwygInlineAttachmentTest extends BaseTestCase
{
    private const string FIELD = 'body';

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testCreate(): void
    {
        $user = $this->createTestUser();
        $otherUser = $this->createUser('other');

        $notInlineId = $this->createAttachment($user->getId(), Attachment::ROLE_ATTACHMENT);
        $noAccessId = $this->createAttachment($otherUser->getId(), Attachment::ROLE_INLINE_ATTACHMENT);
        $allowedId = $this->createAttachment($user->getId(), Attachment::ROLE_INLINE_ATTACHMENT);

        $this->authenticate('test');

        $service = $this->getArticleService();

        $this->assertForbidden(function () use ($service, $notInlineId) {
            $service->create((object) [
                Field::NAME => 'Test 1',
                self::FIELD => $this->composeContents($notInlineId),
            ], CreateParams::create());
        });

        $this->assertForbidden(function () use ($service, $noAccessId) {
            $service->create((object) [
                Field::NAME => 'Test 2',
                self::FIELD => $this->composeContents($noAccessId),
            ], CreateParams::create());
        });

        $this->assertForbidden(function () use ($service, $allowedId, $noAccessId) {
            $service->create((object) [
                Field::NAME => 'Test 3',
                self::FIELD => $this->composeContents($allowedId, $noAccessId),
            ], CreateParams::create());
        });

        $this->assertNotRelated($notInlineId);
        $this->assertNotRelated($noAccessId);
        $this->assertNotRelated($allowedId);

        $article = $service->create((object) [
            Field::NAME => 'Test 4',
            self::FIELD => $this->composeContents($allowedId),
        ], CreateParams::create())->getEntity();

        $this->assertRelated($allowedId, $article->getId());
    }

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testUpdate(): void
    {
        $user = $this->createTestUser();
        $otherUser = $this->createUser('other');

        $notInlineId = $this->createAttachment($user->getId(), Attachment::ROLE_ATTACHMENT);
        $noAccessId = $this->createAttachment($otherUser->getId(), Attachment::ROLE_INLINE_ATTACHMENT);
        $allowedId = $this->createAttachment($user->getId(), Attachment::ROLE_INLINE_ATTACHMENT);

        $this->authenticate('test');

        $service = $this->getArticleService();

        $article = $service->create((object) [
            Field::NAME => 'Test',
            self::FIELD => 'Initial',
        ], CreateParams::create())->getEntity();

        $id = $article->getId();

        $this->assertForbidden(function () use ($service, $id, $notInlineId) {
            $service->update($id, (object) [
                self::FIELD => $this->composeContents($notInlineId),
            ], UpdateParams::create());
        });

        $this->assertForbidden(function () use ($service, $id, $noAccessId) {
            $service->update($id, (object) [
                self::FIELD => $this->composeContents($noAccessId),
            ], UpdateParams::create());
        });

        $this->assertForbidden(function () use ($service, $id, $allowedId, $noAccessId) {
            $service->update($id, (object) [
                self::FIELD => $this->composeContents($allowedId, $noAccessId),
            ], UpdateParams::create());
        });

        $this->assertBodyEquals($id, 'Initial');

        $this->assertNotRelated($notInlineId);
        $this->assertNotRelated($noAccessId);
        $this->assertNotRelated($allowedId);

        $service->update($id, (object) [
            self::FIELD => $this->composeContents($allowedId),
        ], UpdateParams::create());

        $this->assertBodyEquals($id, $this->composeContents($allowedId));

        $this->assertRelated($allowedId, $id);
    }

    /**
     * IDs already present in the field before the update are not checked again,
     * so a user can edit a record that contains an attachment that would not pass the check.
     *
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testUpdateKeepsExistingIds(): void
    {
        $user = $this->createTestUser();
        $otherUser = $this->createUser('other');

        $existingId = $this->createAttachment($otherUser->getId(), Attachment::ROLE_ATTACHMENT);
        $noAccessId = $this->createAttachment($otherUser->getId(), Attachment::ROLE_INLINE_ATTACHMENT);
        $allowedId = $this->createAttachment($user->getId(), Attachment::ROLE_INLINE_ATTACHMENT);

        $article = $this->getEntityManager()->createEntity(KnowledgeBaseArticle::ENTITY_TYPE, [
            Field::NAME => 'Test',
            self::FIELD => $this->composeContents($existingId),
        ]);

        $id = $article->getId();

        $this->authenticate('test');

        $service = $this->getArticleService();

        $contents = $this->composeContents($existingId) . 'Edited';

        $service->update($id, (object) [
            self::FIELD => $contents,
        ], UpdateParams::create());

        $this->assertBodyEquals($id, $contents);

        $this->assertForbidden(function () use ($service, $id, $existingId, $noAccessId) {
            $service->update($id, (object) [
                self::FIELD => $this->composeContents($existingId, $noAccessId),
            ], UpdateParams::create());
        });

        $this->assertBodyEquals($id, $contents);
        $this->assertNotRelated($noAccessId);

        $contents = $this->composeContents($existingId, $allowedId);

        $service->update($id, (object) [
            self::FIELD => $contents,
        ], UpdateParams::create());

        $this->assertBodyEquals($id, $contents);
        $this->assertRelated($allowedId, $id);
    }

    private function createTestUser(): User
    {
        return $this->createUser('test', [
            Role::FIELD_DATA => [
                KnowledgeBaseArticle::ENTITY_TYPE => [
                    Table::ACTION_CREATE => Table::LEVEL_YES,
                    Table::ACTION_READ => Table::LEVEL_ALL,
                    Table::ACTION_EDIT => Table::LEVEL_ALL,
                    Table::ACTION_DELETE => Table::LEVEL_NO,
                ],
            ],
        ]);
    }

    private function createAttachment(?string $createdById, string $role): string
    {
        $attachment = $this->getEntityManager()->getRDBRepositoryByClass(Attachment::class)->getNew();

        $attachment
            ->setName('test.png')
            ->setType('image/png')
            ->setRole($role)
            ->setContents('test')
            ->setTargetField(self::FIELD);

        $this->getEntityManager()->saveEntity($attachment, [SaveOption::CREATED_BY_ID => $createdById]);

        $this->assertEquals($createdById, $attachment->getCreatedBy()->getId());

        return $attachment->getId();
    }

    /**
     * @noinspection HtmlRequiredAltAttribute
     */
    private function composeContents(string ...$ids): string
    {
        $contents = '';

        foreach ($ids as $id) {
            $contents .= "<img src=\"?entryPoint=attachment&amp;id=$id\">";
        }

        return $contents;
    }

    /**
     * @return Service<KnowledgeBaseArticle>
     */
    private function getArticleService(): Service
    {
        return $this->getContainer()
            ->getByClass(ServiceContainer::class)
            ->getByClass(KnowledgeBaseArticle::class);
    }

    private function assertForbidden(callable $callback): void
    {
        $isThrown = false;

        try {
            $callback();
        } catch (Forbidden) {
            $isThrown = true;
        }

        $this->assertTrue($isThrown, 'Forbidden is not thrown.');
    }

    private function assertBodyEquals(string $id, string $expected): void
    {
        $article = $this->getEntityManager()->getEntityById(KnowledgeBaseArticle::ENTITY_TYPE, $id);

        $this->assertNotNull($article);
        $this->assertEquals($expected, $article->get(self::FIELD));
    }

    private function assertRelated(string $attachmentId, string $articleId): void
    {
        $attachment = $this->getEntityManager()->getRDBRepositoryByClass(Attachment::class)->getById($attachmentId);

        $this->assertNotNull($attachment);
        $this->assertEquals(KnowledgeBaseArticle::ENTITY_TYPE, $attachment->getRelated()?->getEntityType());
        $this->assertEquals($articleId, $attachment->getRelated()?->getId());
    }

    private function assertNotRelated(string $attachmentId): void
    {
        $attachment = $this->getEntityManager()->getRDBRepositoryByClass(Attachment::class)->getById($attachmentId);

        $this->assertNotNull($attachment);
        $this->assertNull($attachment->getRelatedId());
    }
}
