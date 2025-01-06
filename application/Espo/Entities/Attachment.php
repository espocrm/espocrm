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

namespace Espo\Entities;

use Espo\Core\Name\Field;
use Espo\Core\ORM\Entity;

use Espo\Core\Field\LinkParent;

class Attachment extends Entity
{
    public const ENTITY_TYPE = 'Attachment';

    public const ROLE_ATTACHMENT = 'Attachment';
    public const ROLE_INLINE_ATTACHMENT = 'Inline Attachment';
    public const ROLE_EXPORT_FILE = 'Export File';

    /**
     * Multiple attachment can refer to one file. Source ID is an original attachment.
     */
    public function getSourceId(): ?string
    {
        $sourceId = $this->get('sourceId');

        if (!$sourceId && $this->hasId()) {
            $sourceId = $this->getId();
        }

        return $sourceId;
    }

    /**
     * A storage.
     */
    public function getStorage(): ?string
    {
        return $this->get('storage');
    }

    /**
     * A file name.
     */
    public function getName(): ?string
    {
        return $this->get(Field::NAME);
    }

    /**
     * A size in bytes.
     */
    public function getSize(): ?int
    {
        return $this->get('size');
    }

    /**
     * A mime-type.
     */
    public function getType(): ?string
    {
        return $this->get('type');
    }

    /**
     * A field the attachment is related through.
     */
    public function getTargetField(): ?string
    {
        return $this->get('field');
    }

    public function getParent(): ?LinkParent
    {
        /** @var ?LinkParent */
        return $this->getValueObject(Field::PARENT);
    }

    public function getRelated(): ?LinkParent
    {
        /** @var ?LinkParent */
        return $this->getValueObject('related');
    }

    public function getParentType(): ?string
    {
        return $this->get('parentType');
    }

    public function getRelatedType(): ?string
    {
        return $this->get('relatedType');
    }

    public function isBeingUploaded(): bool
    {
        return (bool) $this->get('isBeingUploaded');
    }

    /**
     * A role.
     */
    public function getRole(): ?string
    {
        return $this->get('role');
    }

    /**
     * Multiple attachment can refer to one file. Source ID is an original attachment.
     */
    public function setSourceId(?string $sourceId): self
    {
        $this->set('sourceId', $sourceId);

        return $this;
    }

    public function setStorage(?string $storage): self
    {
        $this->set('storage', $storage);

        return $this;
    }

    public function setName(?string $name): self
    {
        $this->set(Field::NAME, $name);

        return $this;
    }

    public function setType(?string $type): self
    {
        $this->set('type', $type);

        return $this;
    }

    public function setRole(?string $type): self
    {
        $this->set('role', $type);

        return $this;
    }

    public function setSize(?int $size): self
    {
        $this->set('size', $size);

        return $this;
    }

    public function setContents(?string $contents): self
    {
        $this->set('contents', $contents);

        return $this;
    }

    public function setTargetField(?string $field): self
    {
        $this->set('field', $field);

        return $this;
    }

    public function setParent(LinkParent|Entity|null $parent): self
    {
        if ($parent instanceof LinkParent) {
            $this->setValueObject(Field::PARENT, $parent);

            return $this;
        }

        $this->relations->set(Field::PARENT, $parent);

        return $this;
    }

    public function setRelated(LinkParent|Entity|null $related): self
    {
        if ($related instanceof LinkParent) {
            $this->setValueObject('related', $related);

            return $this;
        }

        $this->relations->set('related', $related);

        return $this;
    }
}
