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

namespace Espo\Tools\Pdf\Util;

use Espo\Core\Acl;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\Forbidden;
use Espo\Entities\Attachment;
use Espo\Entities\Template;
use Espo\ORM\EntityManager;
use RuntimeException;

/**
 * @internal
 */
class Validation
{
    public function __construct(
        private Acl $acl,
        private EntityManager $entityManager,
    ) {}

    /**
     * @throws BadRequest
     * @throws Forbidden
     */
    public function process(Template $template): void
    {
        $fields = [
            Template::FIELD_BODY,
            Template::FIELD_HEADER,
            Template::FIELD_FOOTER,
        ];

        foreach ($fields as $field) {
            if (!$template->isAttributeChanged($field)) {
                continue;
            }

            $content = $template->get($field);

            if ($content === null) {
                continue;
            }

            $previous = $template->getFetched($field);

            if (!is_string($content)) {
                throw new RuntimeException();
            }

            if ($previous !== null && !is_string($previous)) {
                throw new RuntimeException();
            }

            $this->assertContent($content, $previous);
        }
    }

    /**
     * @throws BadRequest
     * @throws Forbidden
     */
    private function assertContent(string $content, ?string $previous): void
    {
        $previousIds = $this->parseIds($previous ?? '');
        $ids = $this->parseIds($content);

        foreach (array_diff($ids, $previousIds) as $id) {
            $attachment = $this->entityManager->getRDBRepositoryByClass(Attachment::class)->getById($id);

            if (!$attachment) {
                throw new BadRequest("Inline attachment '$id' not found. Remove the inline image.");
            }

            if (!$this->acl->checkEntityRead($attachment)) {
                throw new Forbidden("No access to inline attachment '$id'.");
            }
        }
    }

    /**
     * @return string[]
     */
    private function parseIds(string $content): array
    {
        $ids = [];

        preg_match_all(RegExp::INLINE_ATTACHMENT, $content, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $id = $match[1];

            if (!$id) {
                continue;
            }

            $ids[] = $id;
        }

        return $ids;
    }
}
