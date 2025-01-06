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

namespace Espo\Core\Select\Order;

use Espo\Core\InjectableFactory;
use Espo\Core\Utils\Metadata;
use Espo\Core\Binding\BindingContainerBuilder;
use Espo\Core\Binding\ContextualBinder;

use Espo\Entities\User;
use RuntimeException;

class OrdererFactory
{
    public function __construct(
        private InjectableFactory $injectableFactory,
        private Metadata $metadata,
        private User $user
    ) {}

    public function has(string $entityType, string $field): bool
    {
        return (bool) $this->getClassName($entityType, $field);
    }

    public function create(string $entityType, string $field): Orderer
    {
        $className = $this->getClassName($entityType, $field);

        if (!$className) {
            throw new RuntimeException("Orderer class name is not defined.");
        }

        $container = BindingContainerBuilder::create()
            ->bindInstance(User::class, $this->user)
            ->inContext($className, function (ContextualBinder $binder) use ($entityType) {
                $binder->bindValue('$entityType', $entityType);
            })
            ->build();

        return $this->injectableFactory->createWithBinding($className, $container);
    }

    /**
     * @return ?class-string<Orderer>
     */
    private function getClassName(string $entityType, string $field): ?string
    {
        /** @var ?class-string<Orderer> */
        return $this->metadata->get([
            'selectDefs', $entityType, 'ordererClassNameMap', $field
        ]);
    }
}
