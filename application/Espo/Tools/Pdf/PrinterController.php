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

namespace Espo\Tools\Pdf;

use Espo\Core\Exceptions\Error;
use Espo\Core\InjectableFactory;
use Espo\Core\Utils\Metadata;
use Espo\ORM\Collection;
use Espo\ORM\Entity;

class PrinterController
{
    public function __construct(
        private Metadata $metadata,
        private InjectableFactory $injectableFactory,
        private Template $template,
        private string $engine
    ) {}

    /**
     * @throws Error
     */
    public function printEntity(Entity $entity, ?Params $params, ?Data $data = null): Contents
    {
        $params = $params ?? new Params();
        $data = $data ?? new Data();

        return $this->createEntityPrinter()->print($this->template, $entity, $params,  $data);
    }

    /**
     * @param Collection<Entity> $collection
     * @throws Error
     */
    public function printCollection(
        Collection $collection,
        ?Params $params,
        ?IdDataMap $idDataMap = null
    ): Contents {

        $params = $params ?? new Params();
        $idDataMap = $idDataMap ?? new IdDataMap();

        if ($this->hasCollectionPrinter()) {
            return $this->createCollectionPrinter()->print($this->template, $collection, $params, $idDataMap);
        }

        $printer = $this->createEntityPrinter();

        $zipper = new Zipper();

        foreach ($collection as $entity) {
            $data = $idDataMap->get($entity->getId()) ?? new Data();

            $itemContents = $printer->print($this->template, $entity, $params, $data);

            $zipper->add($itemContents, $entity->getId());
        }

        $zipper->archive();

        return new ZipContents($zipper->getFilePath());
    }

    /**
     * @throws Error
     */
    private function createEntityPrinter(): EntityPrinter
    {
        /** @var ?class-string<EntityPrinter> $className */
        $className = $this->metadata
            ->get(['app', 'pdfEngines', $this->engine, 'implementationClassNameMap', 'entity']) ?? null;

        if (!$className) {
            throw new Error("Unknown PDF engine '{$this->engine}', type 'entity'.");
        }

        return $this->injectableFactory->create($className);
    }

    /**
     * @throws Error
     */
    private function createCollectionPrinter(): CollectionPrinter
    {
        $className = $this->getCollectionPrinterClassName();

        if (!$className) {
            throw new Error("Unknown PDF engine '{$this->engine}', type 'collection'.");
        }

        return $this->injectableFactory->create($className);
    }

    private function hasCollectionPrinter(): bool
    {
        return (bool) $this->getCollectionPrinterClassName();
    }

    /**
     * @return ?class-string<CollectionPrinter>
     */
    private function getCollectionPrinterClassName(): ?string
    {
        /** @var ?class-string<CollectionPrinter> */
        return $this->metadata
            ->get(['app', 'pdfEngines', $this->engine, 'implementationClassNameMap', 'collection']) ?? null;
    }

}
