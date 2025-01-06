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

namespace Espo\Core\Jobs;

use Espo\Core\Container;

/**
 * @deprecated As of v7.0. Create classes that implement Espo\Core\Job\Job and Espo\Core\Job\JobDataLess
 * interfaces. Pass needed dependencies via a constructor.
 * @todo Remove in v10.0.
 */
abstract class Base
{
    private $container; /** @phpstan-ignore-line */

    protected function getContainer() /** @phpstan-ignore-line */
    {
        return $this->container;
    }

    protected function getEntityManager() /** @phpstan-ignore-line */
    {
        return $this->getContainer()->get('entityManager');
    }

    protected function getServiceFactory() /** @phpstan-ignore-line */
    {
        return $this->getContainer()->get('serviceFactory');
    }

    protected function getConfig() /** @phpstan-ignore-line */
    {
        return $this->getContainer()->get('config');
    }

    protected function getMetadata() /** @phpstan-ignore-line */
    {
        return $this->getContainer()->get('metadata');
    }

    protected function getUser() /** @phpstan-ignore-line */
    {
        return $this->getContainer()->get('user');
    }

    public function __construct(Container $container) /** @phpstan-ignore-line */
    {
        $this->container = $container;
    }
}
