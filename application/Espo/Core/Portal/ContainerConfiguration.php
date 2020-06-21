<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2020 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

namespace Espo\Core\Portal;

use Espo\Core\{
    Utils\Log,
    Utils\Metadata,
};

use Espo\Core\{
    ContainerConfiguration as BaseContainerConfiguration
};

class ContainerConfiguration extends BaseContainerConfiguration
{
    protected $log;
    protected $metadata;

    public function __construct(Log $log, Metadata $metadata)
    {
        // log must be loaded before enything
        $this->log = $log;
        $this->metadata = $metadata;
    }

    public function getLoaderClassName(string $name) : ?string
    {
        try {
            $className = $this->metadata->get(['app', 'portalContainerServices', $name, 'loaderClassName']);
        } catch (\Exception $e) {}

        if ($className && class_exists($className)) {
            return $className;
        }

        $className = 'Espo\Custom\Core\Portal\Loaders\\' . ucfirst($name);
        if (!class_exists($className)) {
            $className = 'Espo\Core\Portal\Loaders\\' . ucfirst($name);
        }

        if (class_exists($className)) {
            return $className;
        }

        return parent::getLoaderClassName($name);
    }

    public function getServiceClassName(string $name) : ?string
    {
        $className =
            $this->metadata->get(['app', 'portalContainerServices', $name, 'className']) ??
            parent::getServiceClassName($name);

        return $className;
    }

    public function getServiceDependencyList(string $name) : ?array
    {
        return
            $this->metadata->get(['app', 'portalContainerServices', $name, 'dependencyList']) ??
            parent::getServiceDependencyList($name);
    }
}
