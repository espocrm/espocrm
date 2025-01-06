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

namespace Espo\Core\Select\Primary;

use Espo\Core\Binding\Binder;
use Espo\Core\Binding\BindingContainer;
use Espo\Core\Binding\BindingData;
use Espo\Core\InjectableFactory;
use Espo\Core\Select\Helpers\FieldHelper;
use Espo\Core\Utils\Metadata;
use Espo\Entities\User;

use RuntimeException;

class FilterFactory
{
    public function __construct(private InjectableFactory $injectableFactory, private Metadata $metadata)
    {}

    public function create(string $entityType, User $user, string $name): Filter
    {
        $className = $this->getClassName($entityType, $name);

        if (!$className) {
            throw new RuntimeException("Primary filter '{$name}' for '{$entityType}' does not exist.");
        }

        $bindingData = new BindingData();

        $binder = new Binder($bindingData);

        $binder
            ->bindInstance(User::class, $user)
            ->for($className)
            ->bindValue('$entityType', $entityType)
            ->bindValue('$name', $name);

        $binder
            ->for(FieldHelper::class)
            ->bindValue('$entityType', $entityType);

        $bindingContainer = new BindingContainer($bindingData);

        return $this->injectableFactory->createWithBinding($className, $bindingContainer);
    }

    public function has(string $entityType, string $name): bool
    {
        return (bool) $this->getClassName($entityType, $name);
    }

    /**
     * @return ?class-string<Filter>
     */
    protected function getClassName(string $entityType, string $name): ?string
    {
        if (!$name) {
            throw new RuntimeException("Empty primary filter name.");
        }

        $className = $this->metadata->get(
            [
                'selectDefs',
                $entityType,
                'primaryFilterClassNameMap',
                $name,
            ]
        );

        if ($className) {
            return $className;
        }

        return $this->getDefaultClassName($name);
    }

    /**
     * @return ?class-string<Filter>
     */
    protected function getDefaultClassName(string $name): ?string
    {
        $className = 'Espo\\Core\\Select\\Primary\\Filters\\' . ucfirst($name);

        if (!class_exists($className)) {
            return null;
        }

        /** @var class-string<Filter> */
        return $className;
    }
}
