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

namespace Espo\Core\Select\Order;

use Espo\Core\{
    Exceptions\Error,
    InjectableFactory,
    Utils\Metadata,
};

class ItemConverterFactory
{
    private $injectableFactory;

    private $metadata;

    public function __construct(InjectableFactory $injectableFactory, Metadata $metadata)
    {
        $this->injectableFactory = $injectableFactory;
        $this->metadata = $metadata;
    }

    public function has(string $entityType, string $field): bool
    {
        return (bool) $this->getClassName($entityType, $field);
    }

    public function create(string $entityType, string $field): ItemConverter
    {
        $className = $this->getClassName($entityType, $field);

        if (!$className) {
            throw new Error("Order item converter class name is not defined.");
        }

        return $this->injectableFactory->createWith($className, [
            'entityType' => $entityType,
        ]);
    }

    private function getClassName(string $entityType, string $field): ?string
    {
        $className = $this->metadata->get([
            'selectDefs', $entityType, 'orderItemConverterClassNameMap', $field
        ]);

        if ($className) {
            return $className;
        }

        $type = $this->metadata->get([
            'entityDefs', $entityType, 'fields', $field, 'type'
        ]);

        if (!$type) {
            return null;
        }

        $className = $this->metadata->get([
            'app', 'select', 'orderItemConverterClassNameMap', $type
        ]);

        if ($className) {
            return $className;
        }

        $className = 'Espo\\Core\\Select\\Order\\ItemConverters\\' . ucfirst($type) . 'Type';

        if (class_exists($className)) {
            return $className;
        }

        return null;
    }
}
