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

class AppSecret extends Entity
{
    public const ENTITY_TYPE = 'AppSecret';

    public function getName(): string
    {
        return $this->get(Field::NAME);
    }

    public function getValue(): string
    {
        return (string) $this->get('value');
    }

    public function setName(string $name): self
    {
        $this->set(Field::NAME, $name);

        return $this;
    }

    /**
     * @internal Do not use.
     * @todo Rename to setValue in v9.0.
     */
    public function setSecretValue(string $value): self
    {
        $this->set('value', $value);

        return $this;
    }
}
