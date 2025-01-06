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

namespace Espo\Tools\Pdf\Data;

use Espo\ORM\Entity;

use Espo\Core\Utils\Metadata;
use Espo\Core\InjectableFactory;

use Espo\Tools\Pdf\Data;
use Espo\Tools\Pdf\Params;

class DataLoaderManager
{
    private Metadata $metadata;
    private InjectableFactory $injectableFactory;

    public function __construct(Metadata $metadata, InjectableFactory $injectableFactory)
    {
        $this->metadata = $metadata;
        $this->injectableFactory = $injectableFactory;
    }

    public function load(Entity $entity, ?Params $params = null, ?Data $data = null): Data
    {
        if (!$params) {
            $params = Params::create();
        }

        if (!$data) {
            $data = Data::create();
        }

        /** @var class-string<DataLoader>[] $classNameList */
        $classNameList = $this->metadata->get(['pdfDefs', $entity->getEntityType(), 'dataLoaderClassNameList']) ?? [];

        foreach ($classNameList as $className) {
            $loader = $this->createLoader($className);

            $loadedData = $loader->load($entity, $params);

            $data = $data->withAdditionalTemplateData($loadedData);
        }

        return $data;
    }

    /**
     * @param class-string<DataLoader> $className
     */
    private function createLoader(string $className): DataLoader
    {
        return $this->injectableFactory->create($className);
    }
}
