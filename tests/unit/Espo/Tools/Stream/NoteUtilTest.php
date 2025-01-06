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

namespace tests\unit\Espo\Tools\Stream;

use Espo\Core\Utils\Config;
use Espo\Entities\Note;
use Espo\Tools\Stream\NoteUtil;
use PHPUnit\Framework\TestCase;

class NoteUtilTest extends TestCase
{
    public function testLink(): void
    {
        $post = "https://site.com/#Account/view/100 https://site.com/#Account/view/100";
        $newPost = "[Account/100](#Account/view/100) [Account/100](#Account/view/100)";

        $this->initText($post, $newPost);

        $post = " https://site.com/#Account/view/100";
        $newPost = " [Account/100](#Account/view/100)";

        $this->initText($post, $newPost);

        $post = "\nhttps://site.com/#Account/view/100";
        $newPost = "\n[Account/100](#Account/view/100)";

        $this->initText($post, $newPost);
    }

    public function testLinkWrapped(): void
    {
        $post = "[Test](https://site.com/#Account/view/100) https://site.com/#Account/view/100";
        $newPost = "[Test](https://site.com/#Account/view/100) [Account/100](#Account/view/100)";

        $this->initText($post, $newPost);
    }

    private function initText(string $post, string $newPost): void
    {
        $config = $this->createMock(Config\ApplicationConfig::class);
        $note = $this->createMock(Note::class);

        $config->expects($this->once())
            ->method('getSiteUrl')
            ->willReturn('https://site.com');

        $util = new NoteUtil($config);

        $note->expects($this->once())
            ->method('getPost')
            ->willReturn($post);

        $note->expects($this->once())
            ->method('setPost')
            ->with($newPost);

        $util->handlePostText($note);
    }
}
