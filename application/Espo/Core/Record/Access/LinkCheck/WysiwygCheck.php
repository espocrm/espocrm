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

namespace Espo\Core\Record\Access\LinkCheck;

use Espo\Core\Acl;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\FieldProcessing\Wysiwyg\Saver;
use Espo\Core\ORM\Type\FieldType;
use Espo\Entities\Attachment;
use Espo\ORM\Entity;
use Espo\ORM\EntityManager;

class WysiwygCheck
{
    public function __construct(
        private EntityManager $entityManager,
        private Acl $acl,
    ) {}

    /**
     * @throws Forbidden
     */
    public function process(Entity $entity): void
    {
        $fieldDefsList = $this->entityManager
            ->getDefs()
            ->getEntity($entity->getEntityType())
            ->getFieldList();

        $fieldDefsList = array_filter($fieldDefsList, fn ($it) => $it->getType() === FieldType::WYSIWYG);

        foreach ($fieldDefsList as $fieldDefs) {
            $this->checkField($entity, $fieldDefs->getName());
        }
    }

    /**
     * @throws Forbidden
     */
    private function checkField(Entity $entity, string $field): void
    {
        if (!$entity->isAttributeChanged($field)) {
            return;
        }

        $contents = $entity->get($field);
        $previousContents = $entity->getFetched($field);

        if (!is_string($contents)) {
            return;
        }

        $ids = $this->parseIds($contents);
        $previousIds = $this->parseIds($previousContents ?? '');

        foreach (array_diff($ids, $previousIds) as $id) {
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
     */
    private function parseIds(string $content): array
    {
        $ids = [];

        preg_match_all(Saver::REG_EXP_INLINE_ATTACHMENT, $content, $matches, PREG_SET_ORDER);

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
