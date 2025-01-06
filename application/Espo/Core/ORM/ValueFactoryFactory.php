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

namespace Espo\Core\ORM;

use Espo\Core\InjectableFactory;
use Espo\Core\Utils\Metadata;
use Espo\ORM\Metadata as OrmMetadata;
use Espo\ORM\Value\ValueFactory;
use Espo\ORM\Value\ValueFactoryFactory as ValueFactoryFactoryInteface;

use RuntimeException;

class ValueFactoryFactory implements ValueFactoryFactoryInteface
{
    public function __construct(
        private Metadata $metadata,
        private OrmMetadata $ormMetadata,
        private InjectableFactory $injectableFactory
    ) {}

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

    /**
     * @return ?class-string<ValueFactory>
     */
    private function getClassName(string $entityType, string $field): ?string
    {
        $fieldDefs = $this->ormMetadata
            ->getDefs()
            ->getEntity($entityType)
            ->getField($field);

        /** @var ?class-string<ValueFactory> $className */
        $className = $fieldDefs->getParam('valueFactoryClassName');

        if ($className) {
            return $className;
        }

        $type = $fieldDefs->getType();

        /** @var ?class-string<ValueFactory> */
        return $this->metadata->get(['fields', $type, 'valueFactoryClassName']);
    }
}
