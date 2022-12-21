<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2022 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

namespace Espo\Core\FieldValidation;

use Espo\Core\InjectableFactory;
use Espo\Core\Utils\Metadata;

use Espo\ORM\Entity;
use RuntimeException;

class ValidatorFactory
{
    private InjectableFactory $injectableFactory;
    private Metadata $metadata;

    public function __construct(InjectableFactory $injectableFactory, Metadata $metadata)
    {
        $this->injectableFactory = $injectableFactory;
        $this->metadata = $metadata;
    }

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
        return
            $this->metadata->get(['entityDefs', $entityType, 'fields', $field, 'validatorClassNameMap', $type]) ??
            $this->metadata->get(['fields', $field, 'validatorClassNameMap', $type]);
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
