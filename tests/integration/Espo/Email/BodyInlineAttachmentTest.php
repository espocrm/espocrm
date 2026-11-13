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

namespace tests\integration\Espo\Email;

namespace tests\integration\Espo\Email;

use Espo\Core\Acl\Table;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Field\DateTime;
use Espo\Core\FieldProcessing\Wysiwyg\Saver;
use Espo\Core\Name\Field;
use Espo\Core\ORM\Repository\Option\SaveOption;
use Espo\Core\Record\CreateParams;
use Espo\Core\Record\Service;
use Espo\Core\Record\ServiceContainer;
use Espo\Core\Record\UpdateParams;
use Espo\Entities\Attachment;
use Espo\Entities\Email;
use Espo\Entities\Role;
use Espo\Entities\User;
use tests\integration\Core\BaseTestCase;

/**
 * Inline attachment check in the email body: new emails, replies and duplicates (used by forward).
 *
 * Emails fetched from a mailbox may reference 'Attachment'-role records in the body (legacy).
 */
class BodyInlineAttachmentTest extends BaseTestCase
{
    private const string FIELD_BODY = 'body';
    private const string FIELD_ATTACHMENTS = 'attachments';

    private const string USER_ADDRESS = 'test@test.com';
    private const string OTHER_ADDRESS = 'other@test.com';
    private const string SENDER_ADDRESS = 'sender@test.com';

    private const string MESSAGE_NOT_INLINE = 'Not inline attachment record';
    private const string MESSAGE_NO_ACCESS = 'No access to inline attachment';

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testNew(): void
    {
        $user = $this->createTestUser();
        $otherUser = $this->createUser('other');

        $source = $this->createReceivedEmail(readable: true, userId: $user->getId());

        $notInlineId = $this->createAttachment(Attachment::ROLE_ATTACHMENT, $user->getId());
        $noAccessId = $this->createAttachment(Attachment::ROLE_INLINE_ATTACHMENT, $otherUser->getId());
        $allowedId = $this->createAttachment(Attachment::ROLE_INLINE_ATTACHMENT, $user->getId());

        $this->authenticate('test');

        $service = $this->getEmailService();

        $this->assertForbidden(function () use ($service, $notInlineId) {
            $service->create($this->composeDraftData($this->composeContents($notInlineId)), CreateParams::create());
        }, self::MESSAGE_NOT_INLINE);

        $this->assertForbidden(function () use ($service, $noAccessId) {
            $service->create($this->composeDraftData($this->composeContents($noAccessId)), CreateParams::create());
        }, self::MESSAGE_NO_ACCESS);

        $this->assertForbidden(function () use ($service, $allowedId, $noAccessId) {
            $service->create(
                $this->composeDraftData($this->composeContents($allowedId, $noAccessId)),
                CreateParams::create()
            );
        }, self::MESSAGE_NO_ACCESS);

        // Quoting a readable received email without being a reply to it is not exempted.
        $this->assertForbidden(function () use ($service, $source) {
            $service->create($this->composeDraftData($this->composeQuote($source)), CreateParams::create());
        }, self::MESSAGE_NOT_INLINE);

        $this->assertNotRelated($notInlineId);
        $this->assertNotRelated($noAccessId);
        $this->assertNotRelated($allowedId);

        $contents = $this->composeContents($allowedId);

        $email = $service->create($this->composeDraftData($contents), CreateParams::create())->getEntity();

        $this->assertBodyEquals($email->getId(), $contents);
        $this->assertRelated($allowedId, $email->getId());
    }

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testReply(): void
    {
        $user = $this->createTestUser();

        $source = $this->createReceivedEmail(readable: true, userId: $user->getId());
        $notReadable = $this->createReceivedEmail(readable: false, userId: $user->getId());

        $notInlineId = $this->createAttachment(Attachment::ROLE_ATTACHMENT, $user->getId());

        $sourceIds = $this->parseIds($source->getBody() ?? '');

        $this->assertCount(2, $sourceIds);

        $this->authenticate('test');

        $service = $this->getEmailService();

        // A reply to an email not readable by the user.
        $this->assertForbidden(function () use ($service, $notReadable) {
            $service->create(
                $this->composeDraftData($this->composeQuote($notReadable), $notReadable->getId()),
                CreateParams::create()
            );
        });

        // Only IDs from the replied email are exempted, not from another email.
        $this->assertForbidden(function () use ($service, $source, $notReadable) {
            $service->create(
                $this->composeDraftData($this->composeQuote($notReadable), $source->getId()),
                CreateParams::create()
            );
        }, self::MESSAGE_NOT_INLINE);

        // An additional not-inline attachment next to the quoted contents.
        $this->assertForbidden(function () use ($service, $source, $notInlineId) {
            $service->create(
                $this->composeDraftData(
                    $this->composeQuote($source) . $this->composeContents($notInlineId),
                    $source->getId()
                ),
                CreateParams::create()
            );
        }, self::MESSAGE_NOT_INLINE);

        $contents = $this->composeQuote($source);

        $reply = $service
            ->create($this->composeDraftData($contents, $source->getId()), CreateParams::create())
            ->getEntity();

        $this->assertBodyEquals($reply->getId(), $contents);
        $this->assertEquals($source->getId(), $reply->getReplied()?->getId());

        // Attachments of the replied email are not re-related to the reply.
        foreach ($sourceIds as $id) {
            $this->assertNotRelated($id);
        }

        // Editing the draft keeps working.
        $contents = '<p>Edited</p>' . $contents;

        $service->update($reply->getId(), (object) [
            self::FIELD_BODY => $contents,
        ], UpdateParams::create());

        $this->assertBodyEquals($reply->getId(), $contents);

        $replyId = $reply->getId();

        $this->assertForbidden(function () use ($service, $replyId, $contents, $notInlineId) {
            $service->update($replyId, (object) [
                self::FIELD_BODY => $contents . $this->composeContents($notInlineId),
            ], UpdateParams::create());
        }, self::MESSAGE_NOT_INLINE);

        $this->assertBodyEquals($replyId, $contents);
    }

    /**
     * Forward obtains the body from duplicate attributes.
     *
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testDuplicate(): void
    {
        $user = $this->createTestUser();

        $source = $this->createReceivedEmail(readable: true, userId: $user->getId());

        $sourceIds = $this->parseIds($source->getBody() ?? '');

        $this->assertCount(2, $sourceIds);

        $this->authenticate('test');

        $service = $this->getEmailService();

        $attributes = $service->getDuplicateAttributes($source->getId());

        $this->assertIsString($attributes->body ?? null);

        $body = $attributes->body;
        $copiedIds = $this->parseIds($body);

        $this->assertCount(2, $copiedIds);
        $this->assertEmpty(array_intersect($copiedIds, $sourceIds), 'Body IDs are not replaced with copies.');

        foreach ($copiedIds as $id) {
            $attachment = $this->getAttachment($id);

            $this->assertEquals(Attachment::ROLE_INLINE_ATTACHMENT, $attachment->getRole());
            $this->assertEquals(self::FIELD_BODY, $attachment->getTargetField());
            $this->assertEquals($user->getId(), $attachment->getCreatedBy()?->getId());
            $this->assertNull($attachment->getRelatedId());
            $this->assertNull($attachment->getParent());
        }

        $contents = '<p>Forwarded</p>' . $body;

        $email = $service->create($this->composeDraftData($contents), CreateParams::create())->getEntity();

        $this->assertBodyEquals($email->getId(), $contents);

        foreach ($copiedIds as $id) {
            $this->assertRelated($id, $email->getId());
        }

        foreach ($sourceIds as $id) {
            $this->assertNotRelated($id);
        }
    }

    private function createTestUser(): User
    {
        return $this->createUser([
            User::FIELD_USER_NAME => 'test',
            Field::EMAIL_ADDRESS => self::USER_ADDRESS,
        ], [
            Role::FIELD_DATA => [
                Email::ENTITY_TYPE => [
                    Table::ACTION_CREATE => Table::LEVEL_YES,
                    Table::ACTION_READ => Table::LEVEL_OWN,
                    Table::ACTION_EDIT => Table::LEVEL_OWN,
                    Table::ACTION_DELETE => Table::LEVEL_NO,
                ],
            ],
        ]);
    }

    /**
     * Mimics an email fetched from a mailbox: the body references an image stored with the 'Attachment' role
     * (also listed in the attachments field) and a regular inline image.
     */
    private function createReceivedEmail(bool $readable, string $userId): Email
    {
        $em = $this->getEntityManager();

        $email = $em->getRDBRepositoryByClass(Email::class)->getNew();

        $email
            ->setSubject('Received')
            ->setStatus(Email::STATUS_ARCHIVED)
            ->setIsHtml()
            ->setFromAddress(self::SENDER_ADDRESS)
            ->setToAddressList($readable ? [self::USER_ADDRESS] : [self::OTHER_ADDRESS])
            ->setDateSent(DateTime::createNow());

        if ($readable) {
            $email->addUserId($userId);
        }

        $em->saveEntity($email);

        $attachmentRoleId = $this->createAttachment(
            Attachment::ROLE_ATTACHMENT,
            null,
            $email,
            self::FIELD_ATTACHMENTS
        );

        $inlineRoleId = $this->createAttachment(
            Attachment::ROLE_INLINE_ATTACHMENT,
            null,
            $email
        );

        $email->addLinkMultipleId(self::FIELD_ATTACHMENTS, $attachmentRoleId);
        $email->setBody('<p>Hello</p>' . $this->composeContents($attachmentRoleId, $inlineRoleId));

        $em->saveEntity($email);

        $this->assertEquals(Attachment::ROLE_ATTACHMENT, $this->getAttachment($attachmentRoleId)->getRole());

        return $email;
    }

    private function createAttachment(
        string $role,
        ?string $createdById,
        ?Email $parent = null,
        string $targetField = self::FIELD_BODY,
    ): string {

        $attachment = $this->getEntityManager()->getRDBRepositoryByClass(Attachment::class)->getNew();

        $attachment
            ->setName('test.png')
            ->setType('image/png')
            ->setRole($role)
            ->setContents('test')
            ->setTargetField($targetField);

        if ($parent) {
            $attachment->setParent($parent);
        }

        $this->getEntityManager()->saveEntity($attachment, [SaveOption::CREATED_BY_ID => $createdById]);

        return $attachment->getId();
    }

    private function composeDraftData(string $body, ?string $repliedId = null): object
    {
        $data = (object) [
            Field::NAME => 'Test',
            'status' => Email::STATUS_DRAFT,
            'isHtml' => true,
            'from' => self::USER_ADDRESS,
            'to' => self::SENDER_ADDRESS,
            self::FIELD_BODY => $body,
        ];

        if ($repliedId) {
            $data->repliedId = $repliedId;
        }

        return $data;
    }

    private function composeQuote(Email $email): string
    {
        return '<p data-quote-start="true"><br></p><p>Wrote:</p><blockquote>' . $email->getBody() . '</blockquote>';
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
     * @return string[]
     */
    private function parseIds(string $contents): array
    {
        preg_match_all(Saver::REG_EXP_INLINE_ATTACHMENT, $contents, $matches);

        return array_values(array_unique($matches[1]));
    }

    /**
     * @return Service<Email>
     */
    private function getEmailService(): Service
    {
        return $this->getContainer()
            ->getByClass(ServiceContainer::class)
            ->getByClass(Email::class);
    }

    private function getAttachment(string $id): Attachment
    {
        $attachment = $this->getEntityManager()->getRDBRepositoryByClass(Attachment::class)->getById($id);

        $this->assertNotNull($attachment);

        return $attachment;
    }

    private function assertForbidden(callable $callback, ?string $messagePart = null): void
    {
        $exception = null;

        try {
            $callback();
        } catch (Forbidden $e) {
            $exception = $e;
        }

        $this->assertNotNull($exception, 'Forbidden is not thrown.');

        if ($messagePart !== null) {
            $this->assertStringContainsString($messagePart, $exception->getMessage());
        }
    }

    private function assertBodyEquals(string $id, string $expected): void
    {
        $email = $this->getEntityManager()->getRDBRepositoryByClass(Email::class)->getById($id);

        $this->assertNotNull($email);
        $this->assertEquals($expected, $email->getBody());
    }

    private function assertRelated(string $attachmentId, string $emailId): void
    {
        $attachment = $this->getAttachment($attachmentId);

        $this->assertEquals(Email::ENTITY_TYPE, $attachment->getRelated()?->getEntityType());
        $this->assertEquals($emailId, $attachment->getRelated()?->getId());
    }

    private function assertNotRelated(string $attachmentId): void
    {
        $this->assertNull($this->getAttachment($attachmentId)->getRelatedId());
    }
}
