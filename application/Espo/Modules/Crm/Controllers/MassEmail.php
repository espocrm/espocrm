<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2022 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

namespace Espo\Modules\Crm\Controllers;

use Espo\Modules\Crm\Services\MassEmail as Service;

use Espo\Core\{
    Exceptions\BadRequest,
    Exceptions\Forbidden,
    Api\Request,
};

use stdClass;

class MassEmail extends \Espo\Core\Controllers\Record
{
    public function postActionSendTest(Request $request): bool
    {
        $id = $request->getParsedBody()->id ?? null;
        $targetList = $request->getParsedBody()->targetList ?? null;

        if (!$id || !is_array($targetList)) {
            throw new BadRequest();
        }

        $this->getMassEmailService()->processTest($id, $targetList);

        return true;
    }

    /**
     * @return stdClass[]
     * @throws Forbidden
     */
    public function getActionSmtpAccountDataList(): array
    {
        if (
            !$this->getAcl()->checkScope('MassEmail', 'create') &&
            !$this->getAcl()->checkScope('MassEmail', 'edit')
        ) {
            throw new Forbidden();
        }

        return $this->getMassEmailService()->getSmtpAccountDataList();
    }

    private function getMassEmailService(): Service
    {
        /** @var Service */
        return $this->getServiceFactory()->create('MassEmail');
    }
}
