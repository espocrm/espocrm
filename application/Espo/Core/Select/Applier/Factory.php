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

namespace Espo\Core\Select\Applier;

use Espo\Core\{
    InjectableFactory,
    Utils\Metadata,
    Select\SelectManagerFactory,
    Select\SelectManager,
    Binding\BindingContainer,
    Binding\Binder,
    Binding\BindingData,
};

use Espo\Entities\User;

class Factory
{
    public const SELECT = 'select';

    public const WHERE = 'where';

    public const ORDER = 'order';

    public const LIMIT = 'limit';

    public const ACCESS_CONTROL_FILTER = 'accessControlFilter';

    public const TEXT_FILTER = 'textFilter';

    public const PRIMARY_FILTER = 'primaryFilter';

    public const BOOL_FILTER_LIST = 'boolFilterList';

    public const ADDITIONAL = 'additional';

    private $injectableFactory;

    private $metadata;

    private $selectManagerFactory;

    public function __construct(
        InjectableFactory $injectableFactory,
        Metadata $metadata,
        SelectManagerFactory $selectManagerFactory
    ) {
        $this->injectableFactory = $injectableFactory;
        $this->metadata = $metadata;
        $this->selectManagerFactory = $selectManagerFactory;
    }

    public function create(string $entityType, User $user, string $type): object
    {
        $className = $this->metadata->get(
            [
                'selectDefs',
                $entityType,
                'applierClassNameMap',
                $type,
            ]
        ) ?? $this->getDefaultClassName($type);

        // SelectManager is used for backward compatibility.
        $selectManager = $this->selectManagerFactory->create($entityType, $user);

        $bindingData = new BindingData();

        $binder = new Binder($bindingData);

        $binder
            ->bindInstance(User::class, $user)
            ->bindInstance(SelectManager::class, $selectManager)
            ->for($className)
            ->bindValue('$entityType', $entityType)
            ->bindValue('$selectManager', $selectManager);

        $bindingContainer = new BindingContainer($bindingData);

        return $this->injectableFactory->createWithBinding($className, $bindingContainer);
    }

    protected function getDefaultClassName(string $type): string
    {
        $className = 'Espo\\Core\\Select\\Applier\Appliers\\' . ucfirst($type);

        return $className;
    }
}
