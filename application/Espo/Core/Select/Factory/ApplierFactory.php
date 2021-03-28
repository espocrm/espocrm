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

namespace Espo\Core\Select\Factory;

use Espo\Core\{
    InjectableFactory,
    Utils\Metadata,
    Select\SelectManagerFactory,
};

use Espo\{
    Entities\User,
};

class ApplierFactory
{
    const SELECT = 'select';
    const WHERE = 'where';
    const ORDER = 'order';
    const LIMIT = 'limit';
    const ACCESS_CONTROL_FILTER = 'accessControlFilter';
    const TEXT_FILTER = 'textFilter';
    const PRIMARY_FILTER = 'primaryFilter';
    const BOOL_FILTER_LIST = 'boolFilterList';
    const ADDITIONAL = 'additional';

    private $injectableFactory;

    private $metadata;

    private $selectManagerFactory;

    public function __construct(
        InjectableFactory $injectableFactory, Metadata $metadata, SelectManagerFactory $selectManagerFactory
    ) {
        $this->injectableFactory = $injectableFactory;
        $this->metadata = $metadata;
        $this->selectManagerFactory = $selectManagerFactory;
    }

    public function create(string $entityType, User $user, string $type) : object
    {
        $className = $this->metadata->get(
            [
                'selectDefs',
                $entityType,
                'applierClassNameMap',
                $type,
            ]
        ) ?? $this->getDefaultClassName($type);

        $selectManager = $this->selectManagerFactory->create($entityType, $user);

        return $this->injectableFactory->createWith($className, [
            'entityType' => $entityType,
            'user' => $user,
            'selectManager' => $selectManager, // to use for backward compatibility
        ]);
    }

    protected function getDefaultClassName(string $type) : string
    {
        $className = 'Espo\\Core\\Select\\Appliers\\' . ucfirst($type) . 'Applier';

        return $className;
    }
}
