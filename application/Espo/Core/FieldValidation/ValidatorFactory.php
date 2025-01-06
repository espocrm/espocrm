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

use Espo\Core\InjectableFactory;
use Espo\Core\Utils\Metadata;
use Espo\Core\Utils\FieldUtil;

use Espo\ORM\Entity;
use RuntimeException;

class ValidatorFactory
{

    public function __construct(
        private InjectableFactory $injectableFactory,
        private Metadata $metadata,
        private FieldUtil $fieldUtil
    ) {}

    public function isCreatable(string $entityType, string $field, string $type): bool
    {
        return $this->getClassName($entityType, $field, $type) !== null;
    }

    /**
     * @return Validator<Entity>
     */
    public function create(string $entityType, string $field, string $type): Validator
    {
        $className = $this->getClassName($entityType, $field, $type);

        if (!$className) {
            throw new RuntimeException("No validator.");
        }

        return $this->injectableFactory->create($className);
    }

    /**
     * @return ?class-string<Validator<Entity>>
     */
    private function getClassName(string $entityType, string $field, string $type): ?string
    {
        /** @var ?string $fieldType */
        $fieldType = $this->fieldUtil->getEntityTypeFieldParam($entityType, $field, 'type');

        return
            $this->metadata->get(['entityDefs', $entityType, 'fields', $field, 'validatorClassNameMap', $type]) ??
            $this->metadata->get(['fields', $fieldType ?? '', 'validatorClassNameMap', $type]);
    }

    /**
     * @return Validator<Entity>[]
     */
    public function createAdditionalList(string $entityType, string $field): array
    {
        /** @var class-string<Validator<Entity>>[] $classNameList */
        $classNameList = $this->metadata
            ->get(['entityDefs', $entityType, 'fields', $field, 'validatorClassNameList']) ?? [];

        $list = [];

        foreach ($classNameList as $className) {
            $list[] = $this->injectableFactory->create($className);
        }

        return $list;
    }
}
