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

namespace tests\unit\Espo\Core\Utils\InlineAttachment;

use Espo\Core\Utils\InlineAttachment\InlineAttachmentUtil;
use PHPUnit\Framework\TestCase;

class InlineAttachmentUtilTest extends TestCase
{
    public function testExtractIds(): void
    {
        $content =
            '<img src="?entryPoint=attachment&amp;id=a1">' .
            '<img src="?entryPoint=attachment&id=a2">' .
            '<img src="?entryPoint=attachment&amp;id=a1">' .
            '<a href="?entryPoint=download&amp;id=b1">';

        $this->assertEquals(['a1', 'a2'], InlineAttachmentUtil::extractIds($content));
        $this->assertEquals([], InlineAttachmentUtil::extractIds('no attachments'));
    }

    public function testReplaceIds(): void
    {
        $content =
            '<img src="?entryPoint=attachment&amp;id=a1">' .
            '<img src="?entryPoint=attachment&id=a1">' .
            '<img src="?entryPoint=attachment&amp;id=a12">' .
            '<img src="?entryPoint=attachment&amp;id=a2">';

        $expected =
            '<img src="?entryPoint=attachment&amp;id=c1">' .
            '<img src="?entryPoint=attachment&id=c1">' .
            '<img src="?entryPoint=attachment&amp;id=a12">' .
            '<img src="?entryPoint=attachment&amp;id=a2">';

        $this->assertEquals($expected, InlineAttachmentUtil::replaceIds($content, ['a1' => 'c1']));
        $this->assertEquals($content, InlineAttachmentUtil::replaceIds($content, []));
    }

    public function testReplace(): void
    {
        $content =
            '<img src="?entryPoint=attachment&amp;id=a1">' .
            "<img src='?entryPoint=attachment&id=a1'>" .
            '<img src="https://host/?entryPoint=attachment&amp;id=a1">' .
            '<img src=?entryPoint=attachment&amp;id=a1>' .
            '<img src="?entryPoint=attachment&amp;id=a2">';

        $expected =
            '<img src="cid:a1@espo">' .
            "<img src='cid:a1@espo'>" .
            '<img src="https://host/?entryPoint=attachment&amp;id=a1">' .
            '<img src=?entryPoint=attachment&amp;id=a1>' .
            '<img src="?entryPoint=attachment&amp;id=a2">';

        $replacer = fn (string $id) => $id === 'a1' ? "cid:$id@espo" : null;

        $this->assertEquals($expected, InlineAttachmentUtil::replace($content, $replacer));
    }

    public function testReplaceAtStart(): void
    {
        $content = '?entryPoint=attachment&amp;id=a1"';

        $this->assertEquals($content, InlineAttachmentUtil::replace($content, fn () => 'x'));
    }
}
