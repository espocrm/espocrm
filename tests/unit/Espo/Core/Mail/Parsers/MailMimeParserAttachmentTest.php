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

namespace tests\unit\Espo\Core\Mail\Parsers;

use Espo\Core\Mail\MessageWrapper;
use Espo\Core\Mail\Parsers\MailMimeParser;
use Espo\Core\Utils\File\MimeType;
use Espo\Entities\Attachment;
use Espo\Entities\Email;
use Espo\ORM\EntityManager;
use Espo\ORM\Name\Attribute;
use Espo\ORM\Repository\RDBRepository;
use Espo\ORM\Value\ValueAccessor;
use Espo\ORM\Value\ValueAccessorFactory;
use PHPUnit\Framework\TestCase;

class MailMimeParserAttachmentTest extends TestCase
{
    private const string BOUNDARY_MIXED = 'mixed-boundary';
    private const string BOUNDARY_RELATED = 'related-boundary';

    private const string FIELD_ATTACHMENTS = 'attachments';

    private ?EntityManager $entityManager = null;
    private ?ValueAccessorFactory $valueAccessorFactory = null;
    private ?MimeType $mimeType = null;

    /** @var array<string, mixed> */
    private array $emailDefs;
    /** @var array<string, mixed> */
    private array $attachmentDefs;

    /** @var Attachment[] */
    private array $createdAttachmentList = [];

    protected function setUp(): void
    {
        $this->createdAttachmentList = [];

        $this->entityManager = $this->createMock(EntityManager::class);

        $this->valueAccessorFactory = $this->createMock(ValueAccessorFactory::class);

        $this->valueAccessorFactory
            ->method('create')
            ->willReturn($this->createMock(ValueAccessor::class));

        $this->emailDefs = require('tests/unit/testData/Core/Mail/email_defs.php');
        $this->attachmentDefs = require('tests/unit/testData/Core/Mail/attachment_defs.php');

        $attachmentRepository = $this->createMock(RDBRepository::class);

        $attachmentRepository
            ->method('getNew')
            ->willReturnCallback(function () {
                $attachment = new Attachment(
                    entityType: Attachment::ENTITY_TYPE,
                    defs: $this->attachmentDefs,
                    entityManager: $this->entityManager,
                    valueAccessorFactory: $this->valueAccessorFactory,
                );

                $this->createdAttachmentList[] = $attachment;

                return $attachment;
            });

        $this->entityManager
            ->method('getRDBRepositoryByClass')
            ->willReturnCallback(fn ($className) => $className === Attachment::class ? $attachmentRepository : null);

        $this->entityManager
            ->method('saveEntity')
            ->willReturnCallback(function (Attachment $attachment) {
                $id = 'a-' . (array_search($attachment, $this->createdAttachmentList, true) + 1);

                $attachment->set(Attribute::ID, $id);
            });

        $this->mimeType = $this->createMock(MimeType::class);

        $this->mimeType
            ->method('isImage')
            ->willReturnCallback(function ($type) {
                return in_array($type, [
                    'image/jpeg',
                    'image/png',
                    'image/gif',
                    'image/webp',
                    'image/svg+xml',
                    'image/avif',
                ], true);
            });
    }

    public function testImagesWithoutDispositionAreInline(): void
    {
        // Outlook-style: inline images have no Content-Disposition header.
        /** @noinspection HtmlUnknownTarget, HtmlRequiredAltAttribute */
        $email = $this->parse(
            html: '<p>Hi</p><img src="cid:image001.png@01DD728D.48165040">' .
            '<img src="cid:image002.jpg@01DD728D.48165040">',
            parts: [
                $this->part('image/png', 'image001.png', cid: 'image001.png@01DD728D.48165040'),
                $this->part('image/jpeg', 'image002.jpg', cid: 'image002.jpg@01DD728D.48165040'),
                $this->part('application/pdf', 'doc.pdf', disposition: 'attachment'),
            ],
            inlineList: $inlineList,
        );

        $image1 = $this->getAttachment('image001.png');
        $image2 = $this->getAttachment('image002.jpg');
        $pdf = $this->getAttachment('doc.pdf');

        $this->assertInline($image1);
        $this->assertInline($image2);
        $this->assertRegular($pdf);

        $this->assertSame([$image1, $image2], $inlineList);
        $this->assertSame([$pdf->getId()], $email->getLinkMultipleIdList(self::FIELD_ATTACHMENTS));

        $this->assertStringContainsString('src="?entryPoint=attachment&amp;id=' . $image1->getId() . '"', $email->getBody());
        $this->assertStringContainsString('src="?entryPoint=attachment&amp;id=' . $image2->getId() . '"', $email->getBody());
        $this->assertStringNotContainsString('cid:', $email->getBody());
    }

    public function testDispositionIsIgnored(): void
    {
        /** @noinspection HtmlUnknownTarget, HtmlRequiredAltAttribute */
        $email = $this->parse(
            html: '<img src="cid:a@test"><img src="cid:b@test">',
            parts: [
                $this->part('image/png', 'a.png', cid: 'a@test', disposition: 'inline'),
                $this->part('image/png', 'b.png', cid: 'b@test', disposition: 'attachment'),
            ],
        );

        $this->assertInline($this->getAttachment('a.png'));
        $this->assertInline($this->getAttachment('b.png'));
        $this->assertSame([], $email->getLinkMultipleIdList(self::FIELD_ATTACHMENTS));
    }

    public function testNotReferencedImageIsRegular(): void
    {
        $email = $this->parse(
            html: '<p>No images here.</p>',
            parts: [
                $this->part(
                    type: 'image/png',
                    name: 'a.png',
                    cid: 'a@test',
                    disposition: 'inline',
                ),
            ],
            inlineList: $inlineList,
        );

        $image = $this->getAttachment('a.png');

        $this->assertRegular($image);
        $this->assertSame([], $inlineList);
        $this->assertSame([$image->getId()], $email->getLinkMultipleIdList(self::FIELD_ATTACHMENTS));
    }

    public function testImageWithoutContentIdIsRegular(): void
    {
        $email = $this->parse(
            html: '<p>Hi</p>',
            parts: [
                $this->part(
                    type: 'image/png',
                    name: 'a.png',
                    disposition: 'inline',
                ),
            ],
        );

        $image = $this->getAttachment('a.png');

        $this->assertRegular($image);
        $this->assertSame([$image->getId()], $email->getLinkMultipleIdList(self::FIELD_ATTACHMENTS));
    }

    public function testReferencedNonImageIsRegular(): void
    {
        /** @noinspection HtmlUnknownTarget, HtmlRequiredAltAttribute */
        $email = $this->parse(
            html: '<img src="cid:doc@test">',
            parts: [
                $this->part(
                    type: 'application/pdf',
                    name: 'doc.pdf',
                    cid: 'doc@test',
                    disposition: 'inline',
                ),
            ],
            inlineList: $inlineList,
        );

        $pdf = $this->getAttachment('doc.pdf');

        $this->assertRegular($pdf);
        $this->assertSame([], $inlineList);
        $this->assertSame([$pdf->getId()], $email->getLinkMultipleIdList(self::FIELD_ATTACHMENTS));

        // The reference is left untouched.
        $this->assertStringContainsString('src="cid:doc@test"', $email->getBody());
    }

    public function testOctetStreamImageDetectedByExtensionIsInline(): void
    {
        /** @noinspection HtmlUnknownTarget, HtmlRequiredAltAttribute */
        $email = $this->parse(
            html: '<img src="cid:a@test">',
            parts: [
                $this->part('application/octet-stream', 'a.png', cid: 'a@test'),
            ],
        );

        $image = $this->getAttachment('a.png');

        $this->assertSame('image/png', $image->getType());
        $this->assertInline($image);
        $this->assertSame([], $email->getLinkMultipleIdList(self::FIELD_ATTACHMENTS));
    }

    public function testContentIdPrefixDoesNotMatch(): void
    {
        // Only 'abcd' is referenced. The 'abc' must not be considered referenced.
        /** @noinspection HtmlUnknownTarget, HtmlRequiredAltAttribute */
        $email = $this->parse(
            html: '<img src="cid:abcd">',
            parts: [
                $this->part('image/png', 'abc.png', cid: 'abc'),
                $this->part('image/png', 'abcd.png', cid: 'abcd'),
            ],
        );

        $abc = $this->getAttachment('abc.png');
        $abcd = $this->getAttachment('abcd.png');

        $this->assertRegular($abc);
        $this->assertInline($abcd);

        $this->assertSame('<img src="?entryPoint=attachment&amp;id=' . $abcd->getId() . '">', $email->getBody());
    }

    public function testContentIdPrefixReplacementDoesNotAffectLongerReference(): void
    {
        // The 'abc' is inline, the 'abcd' is demoted. The 'cid:abcd' reference must be left untouched.
        /** @noinspection HtmlUnknownTarget, HtmlRequiredAltAttribute */
        $email = $this->parse(
            html: '<img src="cid:abc"><img src="cid:abcd">',
            parts: [
                $this->part('image/png', 'abc.png', cid: 'abc'),
                $this->part('application/pdf', 'abcd.pdf', cid: 'abcd'),
            ],
        );

        $abc = $this->getAttachment('abc.png');
        $abcd = $this->getAttachment('abcd.pdf');

        $this->assertInline($abc);
        $this->assertRegular($abcd);

        /** @noinspection HtmlUnknownTarget, HtmlRequiredAltAttribute */
        $this->assertSame(
            '<img src="?entryPoint=attachment&amp;id=' . $abc->getId() . '"><img src="cid:abcd">',
            $email->getBody()
        );
    }

    public function testCaseInsensitiveScheme(): void
    {
        /** @noinspection HtmlUnknownTarget, HtmlRequiredAltAttribute */
        $email = $this->parse(
            html: '<img src="CID:a@test">',
            parts: [
                $this->part('image/png', 'a.png', cid: 'a@test'),
            ],
        );

        $image = $this->getAttachment('a.png');

        $this->assertInline($image);
        $this->assertSame('<img src="?entryPoint=attachment&amp;id=' . $image->getId() . '">', $email->getBody());
    }

    public function testCssUrlReference(): void
    {
        /** @noinspection CssUnknownTarget */
        $email = $this->parse(
            html: '<div style="background-image: url(cid:a@test)"></div>',
            parts: [
                $this->part('image/png', 'a.png', cid: 'a@test'),
            ],
        );

        $image = $this->getAttachment('a.png');

        $this->assertInline($image);
        $this->assertStringContainsString('url(?entryPoint=attachment&amp;id=' . $image->getId() . ')', $email->getBody());
    }

    public function testDuplicateContentId(): void
    {
        /** @noinspection HtmlUnknownTarget, HtmlRequiredAltAttribute */
        $email = $this->parse(
            html: '<img src="cid:a@test">',
            parts: [
                $this->part('image/png', 'first.png', cid: 'a@test'),
                $this->part('image/png', 'second.png', cid: 'a@test'),
            ],
            inlineList: $inlineList,
        );

        $first = $this->getAttachment('first.png');
        $second = $this->getAttachment('second.png');

        $this->assertInline($first);
        $this->assertRegular($second);

        $this->assertSame([$first], $inlineList);
        $this->assertSame([$second->getId()], $email->getLinkMultipleIdList(self::FIELD_ATTACHMENTS));
        $this->assertSame('<img src="?entryPoint=attachment&amp;id=' . $first->getId() . '">', $email->getBody());
    }

    public function testPlainTextEmail(): void
    {
        $email = $this->parse(
            html: null,
            parts: [
                $this->part('image/png', 'a.png', cid: 'a@test', disposition: 'inline'),
            ],
            text: 'See cid:a@test',
            inlineList: $inlineList,
        );

        $image = $this->getAttachment('a.png');

        $this->assertFalse($email->isHtml());
        $this->assertRegular($image);
        $this->assertSame([], $inlineList);
        $this->assertSame([$image->getId()], $email->getLinkMultipleIdList(self::FIELD_ATTACHMENTS));
        $this->assertSame('See cid:a@test', $email->getBody());
    }

    /**
     * @param array<int, array{headers: string[], content: string}> $parts
     * @param-out Attachment[] $inlineList
     * @noinspection PhpUnhandledExceptionInspection
     */
    private function parse(
        ?string $html,
        array $parts,
        ?string $text = null,
        ?array &$inlineList = null,
    ): Email {

        $raw = $this->buildRaw($html, $text, $parts);

        $parser = new MailMimeParser(
            entityManager: $this->entityManager,
            mimeTypeUtil: $this->mimeType,
        );

        $message = new MessageWrapper(0, null, $parser, $raw);

        $email = new Email(
            entityType: Email::ENTITY_TYPE,
            defs: $this->emailDefs,
            entityManager: $this->entityManager,
            valueAccessorFactory: $this->valueAccessorFactory,
        );

        $inlineList = $parser->getInlineAttachmentList($message, $email);

        return $email;
    }

    /**
     * @return array{headers: string[], content: string}
     */
    private function part(
        string $type,
        string $name,
        ?string $cid = null,
        ?string $disposition = null,
    ): array {

        $headers = [
            "Content-Type: $type; name=\"$name\"",
            'Content-Transfer-Encoding: base64',
        ];

        if ($cid !== null) {
            $headers[] = "Content-ID: <$cid>";
        }

        if ($disposition !== null) {
            $headers[] = "Content-Disposition: $disposition; filename=\"$name\"";
        }

        return [
            'headers' => $headers,
            'content' => base64_encode('content of ' . $name),
        ];
    }

    /**
     * Builds multipart/mixed > multipart/related > (body, parts...).
     *
     * @param array<int, array{headers: string[], content: string}> $parts
     */
    private function buildRaw(?string $html, ?string $text, array $parts): string
    {
        $mixed = self::BOUNDARY_MIXED;
        $related = self::BOUNDARY_RELATED;

        $bodyPart = $html !== null ?
            "Content-Type: text/html; charset=utf-8\r\n\r\n$html" :
            "Content-Type: text/plain; charset=utf-8\r\n\r\n$text";

        $lines = [
            'From: sender@test.com',
            'To: recipient@test.com',
            'Subject: Test',
            'Message-ID: <test@test.com>',
            'MIME-Version: 1.0',
            "Content-Type: multipart/mixed; boundary=\"$mixed\"",
            '',
            "--$mixed",
            "Content-Type: multipart/related; boundary=\"$related\"",
            '',
            "--$related",
            $bodyPart,
        ];

        foreach ($parts as $part) {
            $lines[] = "--$related";
            $lines = array_merge($lines, $part['headers']);
            $lines[] = '';
            $lines[] = $part['content'];
        }

        $lines[] = "--$related--";
        $lines[] = "--$mixed--";
        $lines[] = '';

        return implode("\r\n", $lines);
    }

    private function getAttachment(string $name): Attachment
    {
        foreach ($this->createdAttachmentList as $attachment) {
            if ($attachment->getName() === $name) {
                return $attachment;
            }
        }

        $this->fail("Attachment '$name' not created.");
    }

    private function assertInline(Attachment $attachment): void
    {
        $this->assertSame(Attachment::ROLE_INLINE_ATTACHMENT, $attachment->getRole());
        $this->assertSame('body', $attachment->getTargetField());
    }

    private function assertRegular(Attachment $attachment): void
    {
        $this->assertSame(Attachment::ROLE_ATTACHMENT, $attachment->getRole());
        $this->assertSame(self::FIELD_ATTACHMENTS, $attachment->getTargetField());
    }
}
