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

namespace Espo\Core\FieldProcessing\Wysiwyg;

use Espo\Core\ORM\Type\FieldType;
use Espo\Core\Utils\InlineAttachment\InlineAttachmentUtil;
use Espo\Entities\Attachment;
use Espo\ORM\Entity;
use Espo\Core\FieldProcessing\Saver as SaverInterface;
use Espo\Core\FieldProcessing\Saver\Params;
use Espo\Core\ORM\EntityManager;

/**
 * @implements SaverInterface<Entity>
 */
class Saver implements SaverInterface
{
    /** @var array<string, string[]> */
    private $fieldListMapCache = [];

    private const string FIELD_IS_HTML = 'isHtml';

    public function __construct(private EntityManager $entityManager)
    {}

    public function process(Entity $entity, Params $params): void
    {
        foreach ($this->getFieldList($entity->getEntityType()) as $name) {
            $this->processItem($entity, $name);
        }
    }

    private function processItem(Entity $entity, string $name): void
    {
        if (!$entity->has($name)) {
            return;
        }

        $hasBodyPlain = $this->hasBodyPlain($entity, $name);

        if ($hasBodyPlain) {
            if (
                !$entity->isAttributeChanged($name) &&
                !$entity->isAttributeChanged(self::FIELD_IS_HTML)
            ) {
                return;
            }

            if (!$entity->get(self::FIELD_IS_HTML)) {
                return;
            }
        } else {
            if (!$entity->isAttributeChanged($name)) {
                return;
            }
        }

        $contents = $entity->get($name);

        if (!$contents) {
            return;
        }

        foreach (InlineAttachmentUtil::extractIds($contents) as $id) {
            $attachment = $this->entityManager->getRDBRepositoryByClass(Attachment::class)->getById($id);

            if (
                !$attachment ||
                $attachment->getRole() !== Attachment::ROLE_INLINE_ATTACHMENT ||
                $attachment->getRelated() ||
                $attachment->getParent()
            ) {
                continue;
            }

            $attachment->setRelated($entity);

            $this->entityManager->saveEntity($attachment);
        }
    }

    /**
     * @return string[]
     */
    private function getFieldList(string $entityType): array
    {
        if (array_key_exists($entityType, $this->fieldListMapCache)) {
            return $this->fieldListMapCache[$entityType];
        }

        $entityDefs = $this->entityManager
            ->getDefs()
            ->getEntity($entityType);

        $list = [];

        foreach ($entityDefs->getFieldNameList() as $name) {
            $defs = $entityDefs->getField($name);

            if ($defs->getType() !== FieldType::WYSIWYG) {
                continue;
            }

            $list[] = $name;
        }

        $this->fieldListMapCache[$entityType] = $list;

        return $list;
    }

    private function hasBodyPlain(Entity $entity, string $name): bool
    {
        return (bool) $this->entityManager
            ->getDefs()
            ->getEntity($entity->getEntityType())
            ->getField($name)
            ->getParam('hasBodyPlain');
    }
}
