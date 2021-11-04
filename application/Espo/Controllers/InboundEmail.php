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

namespace Espo\Controllers;

use Espo\Services\InboundEmail as Service;

use Espo\Core\Exceptions\Error;

use Espo\Core\Di\CryptAware;
use Espo\Core\Di\CryptSetter;

use Espo\Core\{
    Controllers\Record,
    Api\Request,
};

class InboundEmail extends Record implements CryptAware
{
    use CryptSetter;

    protected function checkAccess(): bool
    {
        return $this->getUser()->isAdmin();
    }

    public function postActionGetFolders(Request $request): array
    {
        $data = $request->getParsedBody();

        $params = [
            'host' => $data->host ?? null,
            'port' => $data->port ?? null,
            'security' =>  $data->security ?? null,
            'username' => $data->username ?? null,
            'password' => $data->password ?? null,
            'id' => $data->id ?? null,
        ];

        return $this->getInboundEmailService()->getFolders($params);
    }

    public function postActionTestConnection(Request $request): bool
    {
        $data = $request->getParsedBody();

        if (is_null($data->password)) {
            $inboundEmail = $this->entityManager->getEntity('InboundEmail', $data->id);

            if (!$inboundEmail || !$inboundEmail->getId()) {
                throw new Error();
            }

            $data->password = $this->crypt->decrypt($inboundEmail->get('password'));
        }

        $this->getInboundEmailService()->testConnection(get_object_vars($data));

        return true;
    }

    private function getInboundEmailService(): Service
    {
        /** @var Service */
        return $this->getRecordService();
    }
}
