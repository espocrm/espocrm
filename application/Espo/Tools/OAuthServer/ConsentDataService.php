<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2026 EspoCRM, Inc.
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

namespace Espo\Tools\OAuthServer;

use Espo\Core\Exceptions\NotFound;
use Espo\Core\Utils\Config\ApplicationConfig;
use Espo\Core\Utils\Language;
use Espo\Entities\User;
use Espo\Tools\OAuthServer\Entities\Client;
use Espo\Tools\OAuthServer\Repository\ClientRepository;
use stdClass;

class ConsentDataService
{
    public function __construct(
        private ClientRepository $clientRepository,
        private User $user,
        private Language $language,
        private ApplicationConfig $applicationConfig,
    ) {}

    /**
     * @throws NotFound
     */
    public function getData(string $clientId): stdClass
    {
        $client = $this->getClient($clientId);

        // @todo Check user is associated.

        $scopes = $client->getScopes();

        // @todo Filter scopes.

        $scopeDataList = array_map(function ($scope) {
            return (object) [
                'name' => $scope,
                'label' => $this->translateScope($scope),
            ];
        }, $scopes);

        return (object) [
            'scopeDataList' => $scopeDataList,
            'labels' => (object) [
                'allow' => $this->language->translateLabel('allow', 'strings', Client::ENTITY_TYPE),
                'cancel' => $this->language->translateLabel('Cancel'),
                'info' => $this->composeInfoText($client),
            ],
        ];
    }

    /**
     * @throws NotFound
     */
    private function getClient(string $clientId): Entities\Client
    {
        $client = $this->clientRepository->getActiveByIdentifier($clientId);

        if (!$client) {
            throw new NotFound("Client not found.");
        }

        return $client;
    }

    private function translateScope(string $scope): string
    {
        $appScope = $scope;
        $actionLabel = null;

        if (str_contains($scope, ':')) {
            [$appScope, $action] = explode(':', $appScope, 2);

            $actionLabel = $this->language->translateOption($action, 'levelList', 'Role');
        }

        $scopeLabel = $this->language->translateLabel($appScope, 'scopeNames');

        $label = $scopeLabel;

        if ($actionLabel) {
            $label .= ' . ' . $actionLabel;
        }

        return $label;
    }

    private function composeInfoText(Client $client): string
    {
        return strtr($this->language->translateLabel('allowAccessInfo', 'messages', Client::ENTITY_TYPE), [
            '{clientName}' => $client->getName(),
            '{applicationName}' => $this->applicationConfig->getApplicationName(),
            '{username}' => $this->user->getUserName(),
        ]);
    }
}
