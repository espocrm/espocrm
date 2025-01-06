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

class ConverterFactory
{
    public function __construct(
        private InjectableFactory $injectableFactory,
        private Metadata $metadata
    ) {}

    public function create(string $entityType, User $user): Converter
    {
        $dateTimeItemTransformer = $this->createDateTimeItemTransformer($entityType, $user);

        $itemConverter = $this->createItemConverter($entityType, $user, $dateTimeItemTransformer);

        $className = $this->getConverterClassName($entityType);

        $bindingData = new BindingData();

        $binder = new Binder($bindingData);
        $binder
            ->bindInstance(User::class, $user);
        $binder
            ->for($className)
            ->bindValue('$entityType', $entityType)
            ->bindInstance(ItemConverter::class, $itemConverter);

        $bindingContainer = new BindingContainer($bindingData);

        return $this->injectableFactory->createWithBinding($className, $bindingContainer);
    }

    private function createDateTimeItemTransformer(string $entityType, User $user): DateTimeItemTransformer
    {
        $className = $this->getDateTimeItemTransformerClassName($entityType);

        $bindingData = new BindingData();

        $binder = new Binder($bindingData);
        $binder->bindInstance(User::class, $user);
        $binder
            ->for($className)
            ->bindValue('$entityType', $entityType);
        $binder
            ->for(DefaultDateTimeItemTransformer::class)
            ->bindValue('$entityType', $entityType);

        $bindingContainer = new BindingContainer($bindingData);

        return $this->injectableFactory->createWithBinding($className, $bindingContainer);
    }

    private function createItemConverter(
        string $entityType,
        User $user,
        DateTimeItemTransformer $dateTimeItemTransformer
    ): ItemConverter {

        $className = $this->getItemConverterClassName($entityType);

        $bindingData = new BindingData();

        $binder = new Binder($bindingData);
        $binder
            ->bindInstance(User::class, $user);
        $binder
            ->for($className)
            ->bindValue('$entityType', $entityType)
            ->bindInstance(DateTimeItemTransformer::class, $dateTimeItemTransformer);
        $binder
            ->for(ItemGeneralConverter::class)
            ->bindValue('$entityType', $entityType)
            ->bindInstance(DateTimeItemTransformer::class, $dateTimeItemTransformer);

        $bindingContainer = new BindingContainer($bindingData);

        return $this->injectableFactory->createWithBinding($className, $bindingContainer);
    }

    /**
     * @return class-string<Converter>
     */
    private function getConverterClassName(string $entityType): string
    {
        $className = $this->metadata->get(['selectDefs', $entityType, 'whereConverterClassName']);

        if ($className) {
            return $className;
        }

        return Converter::class;
    }

    /**
     * @return class-string<ItemGeneralConverter>
     */
    private function getItemConverterClassName(string $entityType): string
    {
        $className = $this->metadata->get(['selectDefs', $entityType, 'whereItemConverterClassName']);

        if ($className) {
            return $className;
        }

        return ItemGeneralConverter::class;
    }

    /**
     * @return class-string<DateTimeItemTransformer>
     */
    private function getDateTimeItemTransformerClassName(string $entityType): string
    {
        $className = $this->metadata
            ->get(['selectDefs', $entityType, 'whereDateTimeItemTransformerClassName']);

        if ($className) {
            return $className;
        }

        return DefaultDateTimeItemTransformer::class;
    }
}
