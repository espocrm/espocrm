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

namespace Espo\Core\Select\Applier\Appliers;

use Espo\Core\Binding\ContextualBinder;
use Espo\Core\Utils\Metadata;
use Espo\ORM\Query\SelectBuilder as QueryBuilder;
use Espo\Core\Select\SearchParams;
use Espo\Core\InjectableFactory;
use Espo\Core\Select\Applier\AdditionalApplier;
use Espo\Core\Binding\BindingContainerBuilder;
use Espo\Entities\User;

class Additional
{
    public function __construct(
        private User $user,
        private InjectableFactory $injectableFactory,
        private string $entityType,
        private Metadata $metadata,
    ) {}

    /**
     * @param class-string<AdditionalApplier>[] $classNameList
     */
    public function apply(array $classNameList, QueryBuilder $queryBuilder, SearchParams $searchParams): void
    {
        $classNameList = array_merge($this->getMandatoryClassNameList(), $classNameList);

        foreach ($classNameList as $className) {
            $applier = $this->createApplier($className);

            $applier->apply($queryBuilder, $searchParams);
        }
    }

    /**
     * @param class-string<AdditionalApplier> $className
     */
    private function createApplier(string $className): AdditionalApplier
    {
        return $this->injectableFactory->createWithBinding(
            $className,
            BindingContainerBuilder::create()
                ->bindInstance(User::class, $this->user)
                ->inContext($className, function (ContextualBinder $binder) {
                    $binder->bindValue('$entityType', $this->entityType);
                })
                ->build()
        );
    }

    /**
     * @return class-string<AdditionalApplier>[]
     */
    private function getMandatoryClassNameList(): array
    {
        /** @var class-string<AdditionalApplier>[] */
        return $this->metadata->get("selectDefs.$this->entityType.additionalApplierClassNameList") ?? [];
    }
}
