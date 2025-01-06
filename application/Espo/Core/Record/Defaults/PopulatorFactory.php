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

namespace Espo\Core\Record\Defaults;

use Espo\Core\Acl;
use Espo\Core\Binding\BindingContainerBuilder;
use Espo\Core\InjectableFactory;
use Espo\Core\Utils\Metadata;
use Espo\Entities\User;
use Espo\ORM\Entity;

class PopulatorFactory
{
    /** @var class-string<DefaultPopulator> */
    private string $defaultClassName = DefaultPopulator::class;

    public function __construct(
        private InjectableFactory $injectableFactory,
        private Metadata $metadata,
        private User $user,
        private Acl $acl
    ) {}

    /**
     * @return Populator<Entity>
     */
    public function create(string $entityType): Populator
    {
        $binding = BindingContainerBuilder::create()
            ->bindInstance(User::class, $this->user)
            ->bindInstance(Acl::class, $this->acl)
            ->build();

        return $this->injectableFactory->createWithBinding($this->getClassName($entityType), $binding);
    }

    /**
     * @return class-string<Populator<Entity>>
     */
    private function getClassName(string $entityType): string
    {
        /** @var ?class-string<Populator<Entity>> $className */
        $className = $this->metadata->get("recordDefs.$entityType.defaultsPopulatorClassName");

        if ($className) {
            return $className;
        }

        return $this->defaultClassName;
    }
}
