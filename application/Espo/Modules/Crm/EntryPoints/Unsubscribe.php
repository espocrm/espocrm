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

namespace Espo\Modules\Crm\EntryPoints;

use Espo\Core\Utils\Client\ActionRenderer;
use Espo\Modules\Crm\Tools\MassEmail\UnsubscribeService;
use Espo\Core\Api\Request;
use Espo\Core\Api\Response;
use Espo\Core\EntryPoint\EntryPoint;
use Espo\Core\EntryPoint\Traits\NoAuth;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\NotFound;
use Espo\Core\Utils\Metadata;

/**
 * @noinspection PhpUnused
 */
class Unsubscribe implements EntryPoint
{
    use NoAuth;

    public function __construct(
        private Metadata $metadata,
        private ActionRenderer $actionRenderer,
        private UnsubscribeService $unsubscribeService
    ) {}

    /**
     * @throws BadRequest
     * @throws NotFound
     */
    public function run(Request $request, Response $response): void
    {
        $id = $request->getQueryParam('id') ?? null;
        $emailAddress = $request->getQueryParam('emailAddress') ?? null;
        $hash = $request->getQueryParam('hash') ?? null;

        if ($emailAddress && $hash) {
            $this->processWithHash($response, $emailAddress, $hash);

            return;
        }

        if (!$id) {
            throw new BadRequest("No id.");
        }

        $this->display($response, [
            'queueItemId' => $id,
            'isSubscribed' => $this->unsubscribeService->isSubscribed($id),
        ]);
    }

    /**
     * @param array<string, mixed> $actionData
     */
    private function display(Response $response, array $actionData): void
    {
        $data = [
            'actionData' => $actionData,
            'view' => $this->metadata->get(['clientDefs', 'Campaign', 'unsubscribeView']),
            'template' => $this->metadata->get(['clientDefs', 'Campaign', 'unsubscribeTemplate']),
        ];

        $params = ActionRenderer\Params::create('crm:controllers/unsubscribe', 'unsubscribe', $data);

        $this->actionRenderer->write($response, $params);
    }

    /**
     * @throws NotFound
     */
    private function processWithHash(Response $response, string $emailAddress, string $hash): void
    {
        $this->display($response, [
            'emailAddress' => $emailAddress,
            'hash' => $hash,
            'isSubscribed' => $this->unsubscribeService->isSubscribedWithHash($emailAddress, $hash),
        ]);
    }
}
