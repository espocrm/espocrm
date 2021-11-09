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

namespace Espo\Core\ORM\Repositories;

use Espo\Core\ORM\EntityManager;
use Espo\Core\ORM\EntityFactory;

use Espo\Core\Interfaces\Injectable;

use Espo\Core\{
    Utils\Metadata,
    HookManager,
    ApplicationState,
    Utils\Id\RecordIdGenerator,
};

/** @deprecated */
class RDB extends \Espo\Core\Repositories\Database implements Injectable
{
    protected $dependencyList = [
        'config',
    ];

    protected $dependencies = [];

    protected $injections = [];

    protected function addDependency($name)
    {
        $this->dependencyList[] = $name;
    }

    protected function addDependencyList(array $list)
    {
        foreach ($list as $item) {
            $this->addDependency($item);
        }
    }

    public function inject($name, $object)
    {
        $this->injections[$name] = $object;
    }

    protected function getInjection($name)
    {
        return $this->injections[$name] ?? $this->$name ?? null;
    }

    public function getDependencyList()
    {
        return array_merge($this->dependencyList, $this->dependencies);
    }

    protected function getMetadata()
    {
        return $this->getInjection('metadata');
    }

    protected function getConfig()
    {
        return $this->getInjection('config');
    }

    public function __construct(
        string $entityType,
        EntityManager $entityManager,
        EntityFactory $entityFactory,
        Metadata $metadata,
        HookManager $hookManager,
        ApplicationState $applicationState,
        RecordIdGenerator $recordIdGenerator
    ) {
        parent::__construct(
            $entityType,
            $entityManager,
            $entityFactory,
            $metadata,
            $hookManager,
            $applicationState,
            $recordIdGenerator
        );

        $this->init();
    }

    protected function init()
    {
    }
}
