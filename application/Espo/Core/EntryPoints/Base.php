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

namespace Espo\Core\EntryPoints;

use Espo\Core\Container;

/**
 * @deprecated
 * @todo Remove in v10.0.
 */
abstract class Base
{
    /**
     * @var bool
     */
    public static $authRequired = true;

    /**
     * @var bool
     */
    public static $notStrictAuth = false;

    private $container; /** @phpstan-ignore-line */

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    protected function getContainer() /** @phpstan-ignore-line */
    {
        return $this->container;
    }

    protected function getUser() /** @phpstan-ignore-line */
    {
        return $this->getContainer()->get('user');
    }

    protected function getAcl() /** @phpstan-ignore-line */
    {
        return $this->getContainer()->get('acl');
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

    protected function getDateTime() /** @phpstan-ignore-line */
    {
        return $this->getContainer()->get('dateTime');
    }

    protected function getNumber() /** @phpstan-ignore-line */
    {
        return $this->getContainer()->get('number');
    }

    protected function getFileManager() /** @phpstan-ignore-line */
    {
        return $this->getContainer()->get('fileManager');
    }

    protected function getLanguage() /** @phpstan-ignore-line */
    {
        return $this->getContainer()->get('language');
    }

    protected function getClientManager() /** @phpstan-ignore-line */
    {
        return $this->getContainer()->get('clientManager');
    }
}
