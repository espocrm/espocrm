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

namespace Espo\Core\ORM\QueryComposer;

use Espo\Core\Binding\BindingContainerBuilder;
use Espo\Core\InjectableFactory;
use Espo\ORM\EventDispatcher;
use Espo\ORM\QueryComposer\Part\FunctionConverterFactory;
use Espo\Core\Utils\Metadata;
use Espo\ORM\PDO\PDOProvider;
use Espo\ORM\QueryComposer\QueryComposer;
use Espo\ORM\Metadata as OrmMetadata;
use Espo\ORM\EntityFactory;

use PDO;
use RuntimeException;

class QueryComposerFactory implements \Espo\ORM\QueryComposer\QueryComposerFactory
{
    public function __construct(
        private Metadata $metadata,
        private InjectableFactory $injectableFactory,
        private PDOProvider $pdoProvider,
        private OrmMetadata $ormMetadata,
        private EntityFactory $entityFactory,
        private FunctionConverterFactory $functionConverterFactory,
        private EventDispatcher $eventDispatcher
    ) {}

    public function create(string $platform): QueryComposer
    {
        /** @var ?class-string<QueryComposer> $className */
        $className =
            $this->metadata->get(['app', 'orm', 'platforms', $platform, 'queryComposerClassName']) ??
            $this->metadata->get(['app', 'orm', 'queryComposerClassNameMap', $platform]);

        if (!$className) {
            /** @var class-string<QueryComposer> $className */
            $className = "Espo\\ORM\\QueryComposer\\{$platform}QueryComposer";
        }

        if (!class_exists($className)) {
            throw new RuntimeException("Query composer for '{$platform}' platform does not exits.");
        }

        $bindingContainer = BindingContainerBuilder::create()
            ->bindInstance(PDO::class, $this->pdoProvider->get())
            ->bindInstance(OrmMetadata::class, $this->ormMetadata)
            ->bindInstance(EntityFactory::class, $this->entityFactory)
            ->bindInstance(FunctionConverterFactory::class, $this->functionConverterFactory)
            ->bindInstance(EventDispatcher::class, $this->eventDispatcher)
            ->build();

        return $this->injectableFactory->createWithBinding($className, $bindingContainer);
    }
}
