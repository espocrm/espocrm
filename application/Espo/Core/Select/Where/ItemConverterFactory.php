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

namespace Espo\Core\Select\Where;

use Espo\Core\Binding\Binder;
use Espo\Core\Binding\BindingContainer;
use Espo\Core\Binding\BindingData;
use Espo\Core\InjectableFactory;
use Espo\Core\Utils\Metadata;

use Espo\Entities\User;
use RuntimeException;

class ItemConverterFactory
{
    public function __construct(private InjectableFactory $injectableFactory, private Metadata $metadata)
    {}

    public function hasForType(string $type): bool
    {
        return (bool) $this->getClassNameForType($type);
    }

    public function createForType(string $type, string $entityType, User $user): ItemConverter
    {
        $className = $this->getClassNameForType($type);

        if (!$className) {
            throw new RuntimeException("Where item converter class name is not defined.");
        }

        $bindingData = new BindingData();

        $binder = new Binder($bindingData);

        $binder
            ->bindInstance(User::class, $user);

        $binder
            ->for($className)
            ->bindValue('$entityType', $entityType);

        $bindingContainer = new BindingContainer($bindingData);

        return $this->injectableFactory->createWithBinding($className, $bindingContainer);
    }

    /**
     * @return ?class-string<ItemConverter>
     */
    protected function getClassNameForType(string $type): ?string
    {
        return $this->metadata->get(['app', 'select', 'whereItemConverterClassNameMap', $type]);
    }

    public function has(string $entityType, string $attribute, string $type): bool
    {
        return (bool) $this->getClassName($entityType, $attribute, $type);
    }

    public function create(string $entityType, string $attribute, string $type, User $user): ItemConverter
    {
        $className = $this->getClassName($entityType, $attribute, $type);

        if (!$className) {
            throw new RuntimeException("Where item converter class name is not defined.");
        }

        $bindingData = new BindingData();

        $binder = new Binder($bindingData);
        $binder
            ->bindInstance(User::class, $user);
        $binder
            ->for($className)
            ->bindValue('$entityType', $entityType);

        $bindingContainer = new BindingContainer($bindingData);

        return $this->injectableFactory->createWithBinding($className, $bindingContainer);
    }

    /**
     * @return ?class-string<ItemConverter>
     */
    protected function getClassName(string $entityType, string $attribute, string $type): ?string
    {
        return $this->metadata
            ->get(['selectDefs', $entityType, 'whereItemConverterClassNameMap', $attribute . '_' . $type]);
    }
}
