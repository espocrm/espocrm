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

use Espo\Core\Field\Link;
use Espo\Core\ORM\Entity;

use LogicException;

class ImportError extends Entity
{
    public const ENTITY_TYPE = 'ImportError';

    public const TYPE_VALIDATION = 'Validation';
    public const TYPE_NO_ACCESS = 'No-Access';
    public const TYPE_NOT_FOUND = 'Not-Found';
    public const TYPE_INTEGRITY_CONSTRAINT_VIOLATION = 'Integrity-Constraint-Violation';

    /**
     * @return self::TYPE_*|null
     */
    public function getType(): ?string
    {
        return $this->get('type');
    }

    public function getExportRowIndex(): int
    {
        return $this->get('exportRowIndex');
    }

    public function getRowIndex(): int
    {
        return $this->get('rowIndex');
    }

    public function getValidationField(): ?string
    {
        return $this->get('validationField');
    }

    public function getValidationType(): ?string
    {
        return $this->get('validationType');
    }

    /**
     * @return string[]
     */
    public function getRow(): array
    {
        /** @var ?string[] $value */
        $value = $this->get('row');

        if ($value === null) {
            throw new LogicException();
        }

        return $value;
    }

    public function getImportLink(): Link
    {
        /** @var ?Link $link */
        $link = $this->getValueObject('import');

        if ($link === null) {
            throw new LogicException();
        }

        return $link;
    }
}
