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

use Espo\Core\Field\LinkParent;
use Espo\Core\Name\Field;
use Espo\Core\ORM\Entity;

class UserReaction extends Entity
{
    const ENTITY_TYPE = 'UserReaction';

    public function getType(): string
    {
        return $this->get('type');
    }

    public function getParent(): LinkParent
    {
        /** @var LinkParent */
        return $this->getValueObject(Field::PARENT);
    }

    public function setType(string $type): self
    {
        $this->set('type', $type);

        return $this;
    }

    public function setParent(Note $note): self
    {
        $this->relations->set(Field::PARENT, $note);

        return $this;
    }

    public function setUser(User $user): self
    {
        $this->relations->set('user', $user);

        return $this;
    }
}
