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

namespace Espo\Tools\Stream\RecordService;

use Espo\Core\Name\Field;
use Espo\Core\ORM\Type\FieldType;
use Espo\Core\Utils\FieldUtil;
use Espo\Entities\Note;
use Espo\ORM\EntityManager;
use Espo\ORM\Name\Attribute;
use stdClass;

class NoteHelper
{
    public function __construct(
        private EntityManager $entityManager,
        private FieldUtil $fieldUtil
    ) {}

    public function prepare(Note $note): void
    {
        if ($note->getType() === Note::TYPE_UPDATE) {
            $this->prepareNoteUpdate($note);
        }
    }

    private function prepareNoteUpdate(Note $note): void
    {
        $data = $note->getData();

        /** @var ?string[] $fieldList */
        $fieldList = $data->fields ?? null;
        $attributes = $data->attributes ?? null;

        if (!$attributes instanceof stdClass) {
            return;
        }

        $was = $attributes->was ?? null;

        if (!$was instanceof stdClass) {
            return;
        }

        if (!is_array($fieldList)) {
            return;
        }

        foreach ($fieldList as $field) {
            if ($this->loadNoteUpdateWasForField($note, $field, $was)) {
                $note->setData($data);
            }
        }
    }

    private function loadNoteUpdateWasForField(Note $note, string $field, stdClass $was): bool
    {
        if (!$note->getParentType() || !$note->getParentId()) {
            return false;
        }

        $type = $this->fieldUtil->getFieldType($note->getParentType(), $field);

        if ($type === FieldType::LINK_MULTIPLE) {
            $this->loadNoteUpdateWasForFieldLinkMultiple($note, $field, $was);

            return true;
        }

        return false;
    }

    private function loadNoteUpdateWasForFieldLinkMultiple(Note $note, string $field, stdClass $was): void
    {
        /** @var ?string[] $ids */
        $ids = $was->{$field . 'Ids'} ?? null;

        $names = (object) [];

        if (!is_array($ids)) {
            return;
        }

        $entityType = $note->getParentType();

        if (!$entityType) {
            return;
        }

        $foreignEntityType = $this->entityManager
            ->getDefs()
            ->getEntity($entityType)
            ->tryGetRelation($field)
            ?->tryGetForeignEntityType();

        if (!$foreignEntityType) {
            return;
        }

        $collection = $this->entityManager
            ->getRDBRepository($foreignEntityType)
            ->select([Attribute::ID, Field::NAME])
            ->where([Attribute::ID => $ids])
            ->find();

        foreach ($collection as $entity) {
            $names->{$entity->getId()} = $entity->get(Field::NAME);
        }

        $was->{$field . 'Names'} = $names;
    }
}
