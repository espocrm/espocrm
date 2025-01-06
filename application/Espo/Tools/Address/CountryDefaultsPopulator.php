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

namespace Espo\Tools\Address;

use Espo\Core\Utils\DataCache;
use Espo\Core\Utils\File\Manager;
use Espo\Core\Utils\Id\RecordIdGenerator;
use Espo\Core\Utils\Json;
use Espo\Entities\AddressCountry;
use Espo\ORM\EntityManager;
use Espo\ORM\Query\DeleteBuilder;
use RuntimeException;

class CountryDefaultsPopulator
{
    private string $file = 'application/Espo/Resources/data/locale/en_US/countryList.json';

    private const CACHE_KEY = 'addressCountryData';

    public function __construct(
        private Manager $fileManager,
        private EntityManager $entityManager,
        private DataCache $dataCache,
        private RecordIdGenerator $recordIdGenerator
    ) {}

    public function populate(): void
    {
        if (!$this->fileManager->exists($this->file)) {
            throw new RuntimeException("No file '$this->file'.");
        }

        $contents = $this->fileManager->getContents($this->file);

        $dataList = Json::decode($contents, true);

        if (!is_array($dataList)) {
            throw new RuntimeException("Bad data.");
        }

        $collection = $this->entityManager->getCollectionFactory()->create(AddressCountry::ENTITY_TYPE);

        foreach ($dataList as $data) {
            if (!is_array($data)) {
                throw new RuntimeException("Bad data.");
            }

            $name = $data['name'] ?? null;
            $code = $data['code'] ?? null;
            $isPreferred = $data['isPreferred'] ?? false;

            if (!is_string($name) || !is_string($code)) {
                throw new RuntimeException("Bad data.");
            }

            $entity = $this->entityManager->getNewEntity(AddressCountry::ENTITY_TYPE);

            $entity->setMultiple([
                'id' => $this->recordIdGenerator->generate(),
                'name' => $name,
                'code' => $code,
                'isPreferred' => $isPreferred,
            ]);

            $collection->append($entity);
        }

        $this->entityManager->getQueryExecutor()->execute(
            DeleteBuilder::create()
                ->from(AddressCountry::ENTITY_TYPE)
                ->build()
        );

        $this->entityManager->getMapper()->massInsert($collection);

        $this->dataCache->clear(self::CACHE_KEY);
    }
}
