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

namespace Espo\Tools\Stream;

use Espo\Core\Utils\Config\ApplicationConfig;
use Espo\Entities\Note;

/**
 * @internal
 */
class NoteUtil
{
    public function __construct(private ApplicationConfig $applicationConfig) {}

    public function handlePostText(Note $entity): void
    {
        $post = $entity->getPost();

        if (!$post) {
            return;
        }

        $siteUrl = $this->applicationConfig->getSiteUrl();

        // PhpStorm inspection highlights RegExpRedundantEscape by a mistake.
        /** @noinspection RegExpRedundantEscape */
        $regexp = '/(\s|^)' . preg_quote($siteUrl, '/') .
            '(\/portal|\/portal\/[a-zA-Z0-9]*)?\/#([A-Z][a-zA-Z0-9]*)\/view\/([a-zA-Z0-9-]*)/';

        $post = preg_replace($regexp, '\1[\3/\4](#\3/view/\4)', $post);

        $entity->setPost($post);
    }
}
