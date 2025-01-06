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

namespace Espo\Services;

use Espo\Repositories\Import as Repository;
use Espo\Entities\Import as ImportEntity;
use Espo\Core\Acl\Table;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\NotFoundSilent;
use Espo\Core\FieldProcessing\ListLoadProcessor;
use Espo\Core\Record\Collection as RecordCollection;
use Espo\Core\Select\SearchParams;

/**
 * @extends Record<ImportEntity>
 */
class Import extends Record
{
    public function findLinked(string $id, string $link, SearchParams $searchParams): RecordCollection
    {
        if (!in_array($link, ['imported', 'duplicates', 'updated'])) {
            return parent::findLinked($id, $link, $searchParams);
        }

        /** @var ?ImportEntity $entity */
        $entity = $this->getImportRepository()->getById($id);

        if (!$entity) {
            throw new NotFoundSilent();
        }

        $foreignEntityType = $entity->get('entityType');

        if (!$this->acl->check($entity, Table::ACTION_READ)) {
            throw new Forbidden();
        }

        if (!$this->acl->check($foreignEntityType, Table::ACTION_READ)) {
            throw new Forbidden();
        }

        $query = $this->selectBuilderFactory
            ->create()
            ->from($foreignEntityType)
            ->withStrictAccessControl()
            ->withSearchParams($searchParams)
            ->build();

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
