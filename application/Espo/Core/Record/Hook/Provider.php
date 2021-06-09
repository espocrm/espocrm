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

namespace Espo\Core\Record\Hook;

use Espo\Core\Utils\Metadata;
use Espo\Core\InjectableFactory;

use ReflectionClass;
use RuntimeException;

class Provider
{
    private $metadata;

    private $injectableFactory;

    private $map = [];

    private $typeInterfaceMap = [
        Type::BEFORE_CREATE => CreateHook::class,
        Type::BEFORE_READ => ReadHook::class,
        Type::BEFORE_UPDATE => UpdateHook::class,
        Type::BEFORE_DELETE => DeleteHook::class,
        Type::BEFORE_LINK => LinkHook::class,
        Type::BEFORE_UNLINK => UnlinkHook::class,
    ];

    public function __construct(Metadata $metadata, InjectableFactory $injectableFactory)
    {
        $this->metadata = $metadata;
        $this->injectableFactory = $injectableFactory;
    }

    /**
     * @return object[]
     */
    public function getList(string $entityType, string $type): array
    {
        $key = $entityType . '_' . $type;

        if (!array_key_exists($key, $this->map)) {
            $this->map[$key] = $this->loadList($entityType, $type);
        }

        return $this->map[$key];
    }

    /**
     * @return object[]
     */
    private function loadList(string $entityType, string $type): array
    {
        $key = $type . 'HookClassNameList';

        $classNameList = $this->metadata->get(['recordDefs', $entityType, $key]) ?? [];

        $interfaceName = $this->typeInterfaceMap[$type] ?? null;

        if (!$interfaceName) {
            throw new RuntimeException("Unsupported record hook type '{$type}'.");
        }

        $list = [];

        foreach ($classNameList as $className) {
            $class = new ReflectionClass($className);

            if (!$class->implementsInterface($interfaceName)) {
                throw new RuntimeException("Hook '$className' does not implement '{$interfaceName}'.");
            }

            $list[] = $this->injectableFactory->create($className);
        }

        return $list;
    }
}
