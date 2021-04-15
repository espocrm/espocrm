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

namespace Espo\Core\Select\Where;

use Espo\Core\{
    Utils\Metadata,
    InjectableFactory,
    Binding\BindingContainer,
    Binding\Binder,
    Binding\BindingData,
};

use Espo\{
    Entities\User,
};

class ConverterFactory
{
    private $injectableFactory;

    private $metadata;

    public function __construct(InjectableFactory $injectableFactory, Metadata $metadata)
    {
        $this->injectableFactory = $injectableFactory;
        $this->metadata = $metadata;
    }

    public function create(string $entityType, User $user): Converter
    {
        $dateTimeItemTransformer = $this->createDateTimeItemTranformer($entityType, $user);

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

    private function createDateTimeItemTranformer(string $entityType, User $user): DateTimeItemTransformer
    {
        $className = $this->getDateTimeItemTransformerClassName($entityType);

        $bindingData = new BindingData();

        $binder = new Binder($bindingData);

        $binder
            ->bindInstance(User::class, $user);

        $binder
            ->for($className)
            ->bindValue('$entityType', $entityType);

        $binder
            ->for(DateTimeItemTransformer::class)
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

    private function getConverterClassName(string $entityType): string
    {
        $className = $this->metadata->get(['selectDefs', $entityType, 'whereConverterClassName']);

        if ($className) {
            return $className;
        }

        return Converter::class;
    }

    private function getItemConverterClassName(string $entityType): string
    {
        $className = $this->metadata->get(['selectDefs', $entityType, 'whereItemConverterClassName']);

        if ($className) {
            return $className;
        }

        return ItemGeneralConverter::class;
    }

    private function getDateTimeItemTransformerClassName(string $entityType): string
    {
        $className = $this->metadata
            ->get(['selectDefs', $entityType, 'whereDateTimeItemTransformerClassName']);

        if ($className) {
            return $className;
        }

        return DateTimeItemTransformer::class;
    }
}
