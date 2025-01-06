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

namespace Espo\Core\Record\Hook;

use Espo\Core\Acl;
use Espo\Core\Binding\BindingContainer;
use Espo\Core\Binding\BindingContainerBuilder;
use Espo\Core\Utils\Metadata;
use Espo\Core\InjectableFactory;

use Espo\Entities\User;
use ReflectionClass;
use RuntimeException;

class Provider
{
    /** @var array<string, object[]> */
    private $map = [];

    /** @var array<string, class-string[]> */
    private $typeInterfaceListMap = [
        Type::BEFORE_READ => [ReadHook::class],
        Type::EARLY_BEFORE_CREATE => [CreateHook::class, SaveHook::class],
        Type::BEFORE_CREATE => [CreateHook::class, SaveHook::class],
        Type::AFTER_CREATE => [CreateHook::class, SaveHook::class],
        Type::EARLY_BEFORE_UPDATE => [UpdateHook::class, SaveHook::class],
        Type::BEFORE_UPDATE => [UpdateHook::class, SaveHook::class],
        Type::AFTER_UPDATE => [UpdateHook::class, SaveHook::class],
        Type::BEFORE_DELETE => [DeleteHook::class],
        Type::AFTER_DELETE => [DeleteHook::class],
        Type::BEFORE_LINK => [LinkHook::class],
        Type::BEFORE_UNLINK => [UnlinkHook::class],
        Type::AFTER_LINK => [LinkHook::class],
        Type::AFTER_UNLINK => [UnlinkHook::class],
    ];

    private BindingContainer $bindingContainer;

    public function __construct(
        private Metadata $metadata,
        private InjectableFactory $injectableFactory,
        private Acl $acl,
        private User $user
    ) {
        $this->bindingContainer = BindingContainerBuilder::create()
            ->bindInstance(User::class, $this->user)
            ->bindInstance(Acl::class, $this->acl)
            ->build();
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

        /** @var class-string[] $classNameList */
        $classNameList = $this->metadata->get(['recordDefs', $entityType, $key]) ?? [];

        $interfaces = $this->typeInterfaceListMap[$type] ?? null;

        if (!$interfaces) {
            throw new RuntimeException("Unsupported record hook type '$type'.");
        }

        $list = [];

        foreach ($classNameList as $className) {
            $class = new ReflectionClass($className);

            $found = false;

            foreach ($interfaces as $interface) {
                if ($class->implementsInterface($interface)) {
                    $found = true;

                    break;
                }
            }

            if (!$found) {
                throw new RuntimeException("Hook '$className' does not implement any required interface.");
            }

            $list[] = $this->injectableFactory->createWithBinding($className, $this->bindingContainer);
        }

        return $list;
    }
}
