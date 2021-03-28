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

namespace Espo\Core\Select\AccessControl;

use Espo\Core\{
    Exceptions\Error,
    Select\Filters\AccessControlFilter,
    Select\Helpers\FieldHelper,
    InjectableFactory,
    Utils\Metadata,
};

use Espo\{
    Entities\User,
};

class FilterFactory
{
    private $injectableFactory;

    private $metadata;

    public function __construct(InjectableFactory $injectableFactory, Metadata $metadata)
    {
        $this->injectableFactory = $injectableFactory;
        $this->metadata = $metadata;
    }

    public function create(string $entityType, User $user, string $name) : AccessControlFilter
    {
        $className = $this->getClassName($entityType, $name);

        if (!$className) {
            throw new Error("Access control filter '{$name}' for '{$entityType}' does not exist.");
        }

        $fieldHelper = $this->injectableFactory->createWith(FieldHelper::class, [
            'entityType' => $entityType,
        ]);

        return $this->injectableFactory->createWith($className, [
            'entityType' => $entityType,
            'user' => $user,
            'fieldHelper' => $fieldHelper,
        ]);
    }

    public function has(string $entityType, string $name) : bool
    {
        return (bool) $this->getClassName($entityType, $name);
    }

    private function getClassName(string $entityType, string $name) : ?string
    {
        if (!$name) {
            throw new Error("Empty access control filter name.");
        }

        $className = $this->metadata->get(
            [
                'selectDefs',
                $entityType,
                'accessControlFilterClassNameMap',
                $name,
            ]
        );

        if ($className) {
            return $className;
        }

        $className = $this->getDefaultClassName($name);

        if (!class_exists($className)) {
            return null;
        }

        return $className;
    }

    private function getDefaultClassName(string $name) : string
    {
        $className = 'Espo\\Core\\Select\\AccessControlFilters\\' . ucfirst($name);

        return $className;
    }
}
