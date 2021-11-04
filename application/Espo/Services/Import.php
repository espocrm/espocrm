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

namespace Espo\Services;

use Espo\Repositories\Import as Repository;
use Espo\Entities\Import as ImportEntity;

use Espo\Core\{
    Exceptions\Forbidden,
    Record\Collection as RecordCollection,
    Select\SearchParams,
    FieldProcessing\ListLoadProcessor,
};

use Espo\Services\Record;

class Import extends Record
{
    public function findLinked(string $id, string $link, SearchParams $searchParams): RecordCollection
    {
        if (!in_array($link, ['imported', 'duplicates', 'updated'])) {
            return parent::findLinked($id, $link, $searchParams);
        }

        /** @var ImportEntity $entity */
        $entity = $this->getImportRepository()->get($id);

        $foreignEntityType = $entity->get('entityType');

        if (!$this->acl->check($entity, 'read')) {
            throw new Forbidden();
        }

        if (!$this->acl->check($foreignEntityType, 'read')) {
            throw new Forbidden();
        }

        $query = $this->selectBuilderFactory
            ->create()
            ->from($foreignEntityType)
            ->withStrictAccessControl()
            ->withSearchParams($searchParams)
            ->build();

        /** @var iterable<\Espo\ORM\Entity> */
        $collection = $this->getImportRepository()->findResultRecords($entity, $link, $query);

        $listLoadProcessor = $this->injectableFactory->create(ListLoadProcessor::class);

        $recordService = $this->recordServiceContainer->get($foreignEntityType);

        foreach ($collection as $e) {
            $listLoadProcessor->process($e);
            $recordService->prepareEntityForOutput($e);
        }

        $total = $this->getImportRepository()->countResultRecords($entity, $link, $query);

        return new RecordCollection($collection, $total);
    }

    private function getImportRepository(): Repository
    {
        /** @var Repository */
        return $this->getRepository();
    }
}
