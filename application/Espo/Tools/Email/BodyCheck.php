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

namespace Espo\Tools\Email;

use Espo\Core\Acl;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\FieldProcessing\Wysiwyg\Saver;
use Espo\Core\Record\Access\LinkCheck\WysiwygCheck;
use Espo\Entities\Attachment;
use Espo\Entities\Email;
use Espo\ORM\EntityManager;

/**
 * @internal
 */
class BodyCheck
{
    private const string FIELD_BODY = 'body';

    public function __construct(
        private EntityManager $entityManager,
        private Acl $acl,
    ) {}

    /**
     * @throws Forbidden
     */
    public function assert(Email $email): void
    {
        if (!$email->isAttributeChanged(self::FIELD_BODY)) {
            return;
        }

        $contents = $email->getBody();
        $previousContents = $email->getFetched(self::FIELD_BODY);

        if (!is_string($contents)) {
            return;
        }

        $ids = BodyCheck::parseIds($contents);
        $previousIds = BodyCheck::parseIds($previousContents ?? '');

        $repliedIds = $email->getReplied() ?
            BodyCheck::parseIds($email->getReplied()->getBody() ?? '') :
            [];

        foreach (array_diff($ids, $previousIds, $repliedIds) as $id) {
            $attachment = $this->entityManager->getRDBRepositoryByClass(Attachment::class)->getById($id);

            if (!$attachment) {
                continue;
            }

            if ($attachment->getRole() !== Attachment::ROLE_INLINE_ATTACHMENT) {
                throw new Forbidden("Not allowed inline attachment '$id'. Not inline attachment record.");
            }

            if (!$this->acl->checkEntityRead($attachment)) {
                throw new Forbidden("No access to inline attachment '$id'.");
            }
        }
    }

    /**
     * @return string[]
     * @internal
     */
    public static function parseIds(string $content): array
    {
        return WysiwygCheck::parseIds($content);
    }
}
