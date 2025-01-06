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

namespace Espo\Core\FieldValidation;

use Espo\Core\Utils\Metadata;
use Espo\Core\InjectableFactory;

use RuntimeException;

class CheckerFactory
{
    /** @var array<string, ?class-string> */
    private $classNameCache = [];

    public function __construct(
        private Metadata $metadata,
        private InjectableFactory $injectableFactory
    ) {}

    /**
     * Whether a field validator can be created.
     */
    public function isCreatable(string $entityType, string $field): bool
    {
        return (bool) $this->getClassName($entityType, $field);
    }

    /**
     * Create a validator (checker).
     *
     * @throws RuntimeException
     */
    public function create(string $entityType, string $field): object
    {
        $className = $this->getClassName($entityType, $field);

        if (!$className) {
            throw new RuntimeException("Validator for '{$entityType}.{$field}' does not exist.");
        }

        return $this->injectableFactory->create($className);
    }

    /**
     * @return ?class-string
     */
    private function getClassName(string $entityType, string $field): ?string
    {
        $key = $entityType . '_' . $field;

        if (!array_key_exists($key, $this->classNameCache)) {
            $this->classNameCache[$key] = $this->getClassNameNoCache($entityType, $field);
        }

        return $this->classNameCache[$key];
    }

    /**
     * @return ?class-string
     */
    private function getClassNameNoCache(string $entityType, string $field): ?string
    {
        $className1 = $this->metadata
            ->get(['entityDefs', $entityType, 'fields', $field, 'validatorClassName']);

        if ($className1) {
            return $className1;
        }

        $fieldType = $this->metadata
            ->get(['entityDefs', $entityType, 'fields', $field, 'type']);

        $className2 = $this->metadata
            ->get(['fields', $fieldType, 'validatorClassName']);

        if ($className2) {
            return $className2;
        }

        $className3 = 'Espo\\Classes\\FieldValidators\\' . ucfirst($fieldType) . 'Type';

        if (class_exists($className3)) {
            return $className3;
        }

        return null;
    }
}
