<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2024 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

namespace Espo\EntryPoints;

use Espo\Core\Exceptions\BadRequest;
use Espo\Entities\PasswordChangeRequest;
use Espo\Core\Utils\Client\ActionRenderer;
use Espo\Core\EntryPoint\EntryPoint;
use Espo\Core\EntryPoint\Traits\NoAuth;
use Espo\Core\Utils\Config;
use Espo\Core\Api\Request;
use Espo\Core\Api\Response;
use Espo\ORM\EntityManager;

class ChangePassword implements EntryPoint
{
    use NoAuth;

    public function __construct(
        private Config $config,
        private EntityManager $entityManager,
        private ActionRenderer $actionRenderer
    ) {}

    public function run(Request $request, Response $response): void
    {
        $requestId = $request->getQueryParam('id');

        if (!$requestId) {
            throw new BadRequest("No request ID.");
        }

        $passwordChangeRequest = $this->entityManager
            ->getRDBRepository(PasswordChangeRequest::ENTITY_TYPE)
            ->where([
                'requestId' => $requestId,
            ])
            ->findOne();

        $strengthParams = [
            'passwordGenerateLength' => $this->config->get('passwordGenerateLength'),
            'passwordGenerateLetterCount' => $this->config->get('passwordGenerateLetterCount'),
            'generateNumberCount' => $this->config->get('generateNumberCount'),
            'passwordStrengthLength' => $this->config->get('passwordStrengthLength'),
            'passwordStrengthLetterCount' => $this->config->get('passwordStrengthLetterCount'),
            'passwordStrengthNumberCount' => $this->config->get('passwordStrengthNumberCount'),
            'passwordStrengthBothCases' => $this->config->get('passwordStrengthBothCases'),
        ];

        $options = [
            'id' => $requestId,
            'strengthParams' => $strengthParams,
            'notFound' => !$passwordChangeRequest,
        ];

        $params = new ActionRenderer\Params('controllers/password-change-request', 'passwordChange', $options);

        $this->actionRenderer->write($response, $params);
    }
}
