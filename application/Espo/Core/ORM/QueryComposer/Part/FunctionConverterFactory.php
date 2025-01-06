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

namespace Espo\Core\ORM\QueryComposer\Part;

use Espo\ORM\QueryComposer\Part\FunctionConverterFactory as FunctionConverterFactoryInterface;
use Espo\ORM\QueryComposer\Part\FunctionConverter;
use Espo\ORM\DatabaseParams;
use Espo\Core\Utils\Metadata;
use Espo\Core\InjectableFactory;

use LogicException;

class FunctionConverterFactory implements FunctionConverterFactoryInterface
{
    /** @var array<string, FunctionConverter> */
    private $hash = [];

    public function __construct(
        private Metadata $metadata,
        private InjectableFactory $injectableFactory,
        private DatabaseParams $databaseParams
    ) {}

    public function create(string $name): FunctionConverter
    {
        $className = $this->getClassName($name);

        if ($className === null) {
            throw new LogicException();
        }

        return $this->injectableFactory->create($className);
    }

    public function isCreatable(string $name): bool
    {
        if ($this->getClassName($name) === null) {
            return false;
        }

        return true;
    }

    /**
     * @return ?class-string<FunctionConverter>
     */
    private function getClassName(string $name): ?string
    {
        if (!array_key_exists($name, $this->hash)) {
            /** @var string $platform */
            $platform = $this->databaseParams->getPlatform();

            $this->hash[$name] =
                $this->metadata->get(['app', 'orm', 'platforms', $platform, 'functionConverterClassNameMap', $name]) ??
                $this->metadata->get(['app', 'orm', 'functionConverterClassNameMap_' . $platform, $name]);

        }

        /** @var ?class-string<FunctionConverter> */
        return $this->hash[$name];
    }
}
