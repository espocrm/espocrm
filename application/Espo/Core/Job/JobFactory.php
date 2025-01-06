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

namespace Espo\Core\Job;

use Espo\Core\InjectableFactory;
use Espo\Core\Utils\ClassFinder;
use RuntimeException;

class JobFactory
{
    public function __construct(
        private ClassFinder $classFinder,
        private InjectableFactory $injectableFactory,
        private MetadataProvider $metadataProvider
    ) {}

    /**
     * Create a job by a scheduled job name.
     *
     * @return Job|JobDataLess
     */
    public function create(string $name): object
    {
        $className = $this->getClassName($name);

        if (!$className) {
            throw new RuntimeException("Job '$name' not found.");
        }

        return $this->createByClassName($className);
    }

    /**
     * Create a job by a class name.
     *
     * @param class-string<Job|JobDataLess> $className
     * @return Job|JobDataLess
     */
    public function createByClassName(string $className): object
    {
        return $this->injectableFactory->create($className);
    }

    /**
     * @return ?class-string<Job|JobDataLess>
     */
    private function getClassName(string $name): ?string
    {
        /** @var ?class-string<Job|JobDataLess> $className */
        $className = $this->metadataProvider->getJobClassName($name);

        if ($className) {
            return $className;
        }

        /** @var ?class-string<Job|JobDataLess> */
        return $this->classFinder->find('Jobs', ucfirst($name));
    }
}
