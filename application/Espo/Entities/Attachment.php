<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2021 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
 * Website: https://www.espocrm.com
 *
 * EspoCRM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * EspoCRM is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with EspoCRM. If not, see http://www.gnu.org/licenses/.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

namespace Espo\Entities;

use Espo\Core\ORM\Entity;

use Espo\Core\Field\LinkParent;

class Attachment extends Entity
{
    public const ENTITY_TYPE = 'Attachment';

    /**
     * Multiple attachment can refer to one file. Source ID is an original attachment.
     */
    public function getSourceId(): ?string
    {
        $sourceId = $this->get('sourceId');

        if (!$sourceId) {
            $sourceId = $this->id;
        }

        return $sourceId;
    }

    public function getStorage(): ?string
    {
        return $this->get('storage');
    }

    public function getName(): ?string
    {
        return $this->get('name');
    }

    public function getSize(): ?int
    {
        return $this->get('size');
    }

    public function getType(): ?string
    {
        return $this->get('type');
    }

    public function getTargetField(): ?string
    {
        return $this->get('field');
    }

    public function getParent(): ?LinkParent
    {
        return $this->getValueObject('parent');
    }

    public function getRelated(): ?LinkParent
    {
        return $this->getValueObject('related');
    }

    /**
     * Multiple attachment can refer to one file. Source ID is an original attachment.
     */
    public function setSourceId(?string $sourceId): self
    {
        $this->set('sourceId', $sourceId);

        return $this;
    }

    public function setName(?string $name): self
    {
        $this->set('name', $name);

        return $this;
    }

    public function setType(?string $type): self
    {
        $this->set('type', $type);

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

    public function setParent(?LinkParent $parent): self
    {
        $this->setValueObject('parent', $parent);

        return $this;
    }

    public function setRelated(?LinkParent $related): self
    {
        $this->setValueObject('related', $related);

        return $this;
    }
}
