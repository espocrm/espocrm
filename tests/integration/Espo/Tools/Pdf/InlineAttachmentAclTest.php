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

namespace tests\integration\Espo\Tools\Pdf;

use Espo\Core\Acl;
use Espo\Core\Acl\Table;
use Espo\Core\Name\Field;
use Espo\Core\ORM\Repository\Option\SaveOption;
use Espo\Entities\Attachment;
use Espo\Entities\Role;
use Espo\Entities\Template;
use Espo\Modules\Crm\Entities\KnowledgeBaseArticle;
use Espo\Tools\Pdf\Data;
use Espo\Tools\Pdf\Dompdf\HtmlComposer;
use Espo\Tools\Pdf\Params;
use Espo\Tools\Pdf\TemplateWrapper;
use tests\integration\Core\BaseTestCase;

class InlineAttachmentAclTest extends BaseTestCase
{
    private const string OWN_IMAGE =
        'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAIAAACQd1PeAAAADElEQVR4nGP4z8AAAAMBAQDJ/pLvAAAAAElFTkSuQmCC';
    private const string OTHER_IMAGE =
        'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAIAAACQd1PeAAAADElEQVR4nGNgYPgPAAEDAQAIicLsAAAAAElFTkSuQmCC';

    /**
     * An inline attachment reference that did not pass the save-time check (here, from a text field
     * output with triple braces) must not be embedded when the PDF is generated with ACL applied.
     *
     * @noinspection HtmlRequiredAltAttribute
     */
    public function testImageNotEmbeddedWithoutAccess(): void
    {
        $em = $this->getEntityManager();

        $user = $this->createUser('test', [
            Role::FIELD_DATA => [
                KnowledgeBaseArticle::ENTITY_TYPE => [
                    Table::ACTION_CREATE => Table::LEVEL_YES,
                    Table::ACTION_READ => Table::LEVEL_ALL,
                    Table::ACTION_EDIT => Table::LEVEL_ALL,
                    Table::ACTION_DELETE => Table::LEVEL_NO,
                ],
                Template::ENTITY_TYPE => [
                    Table::ACTION_READ => Table::LEVEL_ALL,
                ],
            ],
        ]);

        $otherUser = $this->createUser('other');

        $ownId = $this->createAttachment($user->getId(), self::OWN_IMAGE);
        $otherId = $this->createAttachment($otherUser->getId(), self::OTHER_IMAGE);

        $article = $em->createEntity(KnowledgeBaseArticle::ENTITY_TYPE, [
            Field::NAME => 'Test',
            KnowledgeBaseArticle::FIELD_BODY => "<img src=\"?entryPoint=attachment&amp;id=$ownId\">",
            Field::DESCRIPTION => "<img src=\"?entryPoint=attachment&id=$otherId\">",
        ]);

        $template = $em->createEntity(Template::ENTITY_TYPE, [
            Field::NAME => 'Test',
            Template::FIELD_ENTITY_TYPE => KnowledgeBaseArticle::ENTITY_TYPE,
            Template::FIELD_BODY => '{{{body}}}{{{description}}}',
        ]);

        $this->authenticate('test');

        $acl = $this->getContainer()->getByClass(Acl::class);

        $ownAttachment = $em->getRDBRepositoryByClass(Attachment::class)->getById($ownId);
        $otherAttachment = $em->getRDBRepositoryByClass(Attachment::class)->getById($otherId);

        $this->assertNotNull($ownAttachment);
        $this->assertNotNull($otherAttachment);
        $this->assertTrue($acl->checkEntityRead($ownAttachment));
        $this->assertFalse($acl->checkEntityRead($otherAttachment));

        $article = $em->getEntityById(KnowledgeBaseArticle::ENTITY_TYPE, $article->getId());
        $template = $em->getRDBRepositoryByClass(Template::class)->getById($template->getId());

        $this->assertNotNull($article);
        $this->assertNotNull($template);

        $composer = $this->getInjectableFactory()->create(HtmlComposer::class);

        $compose = fn (Params $params) => $composer->composeMain(
            template: new TemplateWrapper($template),
            entity: $article,
            params: $params,
            data: Data::create(),
        );

        $html = $compose(Params::create()->withAcl());

        $this->assertStringContainsString(self::OWN_IMAGE, $html, 'Accessible image is not embedded.');
        $this->assertStringNotContainsString(self::OTHER_IMAGE, $html, 'Inaccessible image is embedded.');

        // Without ACL (e.g. formula), both are embedded.
        $html = $compose(Params::create());

        $this->assertStringContainsString(self::OWN_IMAGE, $html);
        $this->assertStringContainsString(self::OTHER_IMAGE, $html);
    }

    private function createAttachment(string $createdById, string $base64Contents): string
    {
        $em = $this->getEntityManager();

        $attachment = $em->getRDBRepositoryByClass(Attachment::class)->getNew();

        $attachment
            ->setName('test.png')
            ->setType('image/png')
            ->setRole(Attachment::ROLE_INLINE_ATTACHMENT)
            ->setContents(base64_decode($base64Contents))
            ->setTargetField('body');

        $em->saveEntity($attachment, [SaveOption::CREATED_BY_ID => $createdById]);

        return $attachment->getId();
    }
}

