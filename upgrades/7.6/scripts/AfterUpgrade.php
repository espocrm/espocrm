<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2022 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

use Espo\Core\Container;
use Espo\Entities\Role;
use Espo\ORM\EntityManager;
use Espo\ORM\Query\Part\Expression;
use Espo\ORM\Query\UpdateBuilder;
use Espo\Core\Templates\Entities\Company;
use Espo\Core\Templates\Entities\Person;
use Espo\Core\Templates\Entities\Base;
use Espo\Core\Templates\Entities\BasePlus;
use Espo\Core\Utils\Metadata;

class AfterUpgrade
{
    public function run(Container $container): void
    {
        $this->updateRoles(
            $container->getByClass(EntityManager::class)
        );

        $this->updateMetadata(
            $container->getByClass(Metadata::class)
        );
    }

    private function updateRoles(EntityManager $entityManager): void
    {
        $query = UpdateBuilder::create()
            ->in(Role::ENTITY_TYPE)
            ->set(['messagePermission' => Expression::column('assignmentPermission')])
            ->build();

        $entityManager->getQueryExecutor()->execute($query);
    }

    private function updateMetadata(Metadata $metadata): void
    {
        $defs = $metadata->get(['scopes']);

        foreach ($defs as $entityType => $item) {
            $isCustom = $item['isCustom'] ?? false;
            $type = $item['type'] ?? false;

            if (!$isCustom) {
                continue;
            }

            if (
                !in_array($type, [
                    BasePlus::TEMPLATE_TYPE,
                    Base::TEMPLATE_TYPE,
                    Company::TEMPLATE_TYPE,
                    Person::TEMPLATE_TYPE
                ])
            ) {
                continue;
            }

            $recordDefs = $metadata->getCustom('recordDefs', $entityType) ?? (object) [];
            $scopes = $metadata->getCustom('scopes', $entityType) ?? (object) [];

            $recordDefs->duplicateWhereBuilderClassName = "Espo\\Classes\\DuplicateWhereBuilders\\General";

            $scopes->duplicateCheckFieldList = [];

            if ($type === Company::TEMPLATE_TYPE || $type === Person::TEMPLATE_TYPE) {
                $scopes->duplicateCheckFieldList = ['name', 'emailAddress'];
            }

            $metadata->saveCustom('recordDefs', $entityType, $recordDefs);
            $metadata->saveCustom('scopes', $entityType, $scopes);
        }
    }
}
