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

namespace Espo\Core\Job;

use Espo\Core\{
    Exceptions\Error,
    Utils\ClassFinder,
    InjectableFactory,
};

class JobFactory
{
    private $classFinder;

    private $injectableFactory;

    public function __construct(ClassFinder $classFinder, InjectableFactory $injectableFactory)
    {
        $this->classFinder = $classFinder;
        $this->injectableFactory = $injectableFactory;
    }

    /**
     * Create a job implementation.
     *
     * @return Job|JobTargeted
     * @throws Error
     */
    public function create(string $name) : object
    {
        $className = $this->getClassName($name);

        if (!$className) {
            throw new Error("Job '{$name}' not found.");
        }

        return $this->injectableFactory->create($className);
    }

    /**
     * Whether a job has prepare method. Prepare method creates job records from a scheduled job record.
     *
     * @throws Error
     */
    public function isPreparable(string $name) : bool
    {
        $className = $this->getClassName($name);

        if (!$className) {
            throw new Error("Job '{$name}' not found.");
        }

        if (method_exists($className, 'prepare')) {
            return true;
        }

        return false;
    }

    private function getClassName(string $name) : ?string
    {
        return $this->classFinder->find('Jobs', ucfirst($name));
    }
}