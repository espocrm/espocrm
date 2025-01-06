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

namespace Espo\Controllers;

use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\NotFound;

use Espo\Entities\ExternalAccount as ExternalAccountEntity;
use Espo\Entities\Integration as IntegrationEntity;
use Espo\Services\ExternalAccount as Service;

use Espo\Core\Api\Request;
use Espo\Core\Api\Response;
use Espo\Core\Controllers\RecordBase;
use Espo\Core\Record\ReadParams;

use stdClass;

class ExternalAccount extends RecordBase
{
    public static $defaultAction = 'list';

    protected function checkAccess(): bool
    {
        return $this->acl->checkScope(ExternalAccountEntity::ENTITY_TYPE);
    }

    public function getActionList(Request $request, Response $response): stdClass
    {
        $integrations = $this->entityManager
            ->getRDBRepository(IntegrationEntity::ENTITY_TYPE)
            ->find();

        $list = [];

        foreach ($integrations as $entity) {
            if (
                $entity->get('enabled') &&
                $this->metadata->get('integrations.' . $entity->getId() .'.allowUserAccounts')
            ) {
                /** @var string $id */
                $id = $entity->getId();

                $userAccountAclScope = $this->metadata
                    ->get(['integrations', $id, 'userAccountAclScope']);

                if ($userAccountAclScope) {
                    if (!$this->acl->checkScope($userAccountAclScope)) {
                        continue;
                    }
                }

                $list[] = [
                    'id' => $id,
                ];
            }
        }

        return (object) [
            'list' => $list
        ];
    }

    public function getActionGetOAuth2Info(Request $request): ?stdClass
    {
        $id = $request->getQueryParam('id');

        if ($id === null) {
            throw new BadRequest();
        }

        list($integration, $userId) = explode('__', $id);

        if ($this->user->getId() != $userId && !$this->user->isAdmin()) {
            throw new Forbidden();
        }

        $entity = $this->entityManager->getEntityById(IntegrationEntity::ENTITY_TYPE, $integration);

        if ($entity) {
            return (object) [
                'clientId' => $entity->get('clientId'),
                'redirectUri' => $this->config->get('siteUrl') . '?entryPoint=oauthCallback',
                'isConnected' => $this->getExternalAccount()->ping($integration, $userId)
            ];
        }

        return null;
    }

    public function getActionRead(Request $request, Response $response): stdClass
    {
        /** @var string $id */
        $id = $request->getRouteParam('id');

        if ($id === '') {
            throw new BadRequest();
        }

        return $this->getRecordService()
            ->read($id, ReadParams::create())
            ->getValueMap();
    }

    public function putActionUpdate(Request $request, Response $response): stdClass
    {
        /** @var string $id */
        $id = $request->getRouteParam('id');

        $data = $request->getParsedBody();

        list($integration, $userId) = explode('__', $id);

        if ($this->user->getId() !== $userId && !$this->user->isAdmin()) {
            throw new Forbidden();
        }

        if (isset($data->enabled) && !$data->enabled) {
            $data->data = null;
        }

        $entity = $this->entityManager->getEntityById(ExternalAccountEntity::ENTITY_TYPE, $id);

        if (!$entity) {
            throw new NotFound();
        }

        $entity->set($data);

        $this->entityManager->saveEntity($entity);

        return $entity->getValueMap();
    }

    public function postActionAuthorizationCode(Request $request): bool
    {
        $data = $request->getParsedBody();

        $id = $data->id;
        $code = $data->code;

        list ($integration, $userId) = explode('__', $id);

        if ($this->user->getId() != $userId && !$this->user->isAdmin()) {
            throw new Forbidden();
        }

        $this->getExternalAccount()->authorizationCode($integration, $userId, $code);

        return true;
    }

    private function getExternalAccount(): Service
    {
        /** @var Service */
        return $this->getRecordService();
    }
}
