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

namespace Espo\Tools\PopupNotification;

use Espo\Core\InjectableFactory;
use Espo\Core\ServiceFactory;
use Espo\Core\Utils\Log;
use Espo\Core\Utils\Metadata;

use Espo\Entities\User;

use stdClass;
use Throwable;

class Service
{
    private Metadata $metadata;
    private ServiceFactory $serviceFactory;
    private User $user;
    private Log $log;
    private InjectableFactory $injectableFactory;

    public function __construct(
        Metadata $metadata,
        ServiceFactory $serviceFactory,
        User $user,
        Log $log,
        InjectableFactory $injectableFactory
    ) {
        $this->metadata = $metadata;
        $this->serviceFactory = $serviceFactory;
        $this->user = $user;
        $this->log = $log;
        $this->injectableFactory = $injectableFactory;
    }

    /**
     * @return array<string, Item[]> Items grouped by type.
     */
    public function getGrouped(): array
    {
        $data = $this->metadata->get(['app', 'popupNotifications']) ?? [];

        $data = array_filter($data, function ($item) {
            if (!($item['grouped'] ?? false)) {
                return false;
            }

            if ($item['disabled'] ?? false) {
                return false;
            }

            if (
                empty($item['providerClassName']) &&
                (empty($item['serviceName']) || empty($item['methodName']))
            ) {
                return false;
            }

            $portalDisabled = $item['portalDisabled'] ?? false;

            if ($portalDisabled && $this->user->isPortal()) {
                return false;
            }

            return true;
        });

        $result = [];

        foreach ($data as $type => $item) {
            /** @var ?class-string<Provider> $className */
            $className = $item['providerClassName'] ?? null;

            try {
                if ($className) {
                    $provider = $this->injectableFactory->create($className);

                    $result[$type] = $provider->get($this->user);

                    continue;
                }

                // For bc.

                $serviceName = $item['serviceName'];
                $methodName = $item['methodName'];

                $service = $this->serviceFactory->create($serviceName);

                $itemList = array_map(
                    function ($raw) {
                        if ($raw instanceof stdClass) {
                            return new Item($raw->id ?? null, $raw->data);
                        }

                        return new Item($raw['id'] ?? null, $raw['data']);
                    },
                    $service->$methodName($this->user->getId())
                );

                $result[$type] = $itemList;
            } catch (Throwable $e) {
                $this->log->error("Popup notifications: " . $e->getMessage());
            }
        }

        return $result;
    }
}
