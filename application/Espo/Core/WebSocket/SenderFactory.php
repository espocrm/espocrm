<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

namespace Espo\Core\WebSocket;

use Espo\Core\InjectableFactory;
use Espo\Core\Utils\Metadata;
use Espo\Core\Binding\Factory;

use RuntimeException;

/**
 * @implements Factory<Sender>
 */
class SenderFactory implements Factory
{
    private const DEFAULT_MESSAGER = 'ZeroMQ';

    public function __construct(
        private InjectableFactory $injectableFactory,
        private ConfigDataProvider $config,
        private Metadata $metadata,
    ) {}

    public function create(): Sender
    {
        $messager = $this->config->getMessager() ?? self::DEFAULT_MESSAGER;

        /** @var ?class-string<Sender> $className */
        $className = $this->metadata->get(['app', 'webSocket', 'messagers', $messager, 'senderClassName']);

        if (!$className) {
            throw new RuntimeException("No sender for messager '$messager'.");
        }

        return $this->injectableFactory->create($className);
    }
}
