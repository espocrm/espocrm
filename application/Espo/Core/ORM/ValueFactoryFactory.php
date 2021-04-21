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

namespace Espo\Core\ORM;

use Espo\Core\{
    Utils\Metadata,
    InjectableFactory,
};

use Espo\ORM\{
    Value\ValueFactoryFactory as ValueFactoryFactoryInteface,
    Value\ValueFactory,
    Metadata as OrmMetadata,
};

use RuntimeException;

class ValueFactoryFactory implements ValueFactoryFactoryInteface
{
    private $metadata;

    private $ormMetadata;

    private $injectableFactory;

    public function __construct(Metadata $metadata, OrmMetadata $ormMetadata, InjectableFactory $injectableFactory)
    {
        $this->metadata = $metadata;
        $this->ormMetadata = $ormMetadata;
        $this->injectableFactory = $injectableFactory;
    }

    public function isCreatable(string $entityType, string $field): bool
    {
        return $this->getClassName($entityType, $field) !== null;
    }

    public function create(string $entityType, string $field): ValueFactory
    {
        $className = $this->getClassName($entityType, $field);

        if (!$className) {
            throw new RuntimeException("Could not get ValueFactory for '{$entityType}.{$field}'.");
        }

        return $this->injectableFactory->create($className);
    }

    private function getClassName(string $entityType, string $field): ?string
    {
        $fieldDefs = $this->ormMetadata
            ->getDefs()
            ->getEntity($entityType)
            ->getField($field);

        $className = $fieldDefs->getParam('valueFactoryClassName');

        if ($className) {
            return $className;
        }

        $type = $fieldDefs->getType();

        return $this->metadata->get(['fields', $type, 'valueFactoryClassName']);
    }
}
