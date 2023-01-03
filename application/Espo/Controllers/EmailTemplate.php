<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2023 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

use Espo\Core\Exceptions\BadRequest;

use Espo\Core\Exceptions\ForbiddenSilent;
use Espo\Core\Exceptions\NotFound;
use Espo\Tools\EmailTemplate\Data;
use Espo\Tools\EmailTemplate\Service;

use Espo\Core\Api\Request;
use Espo\Core\Controllers\Record;

use stdClass;

class EmailTemplate extends Record
{
    /**
     * @throws BadRequest
     * @throws NotFound
     * @throws ForbiddenSilent
     */
    public function actionParse(Request $request): stdClass
    {
        $id = $request->getQueryParam('id');

        if ($id === null) {
            throw new BadRequest("No `id`.");
        }

        $data = Data::create()
            ->withRelatedType($request->getQueryParam('relatedType'))
            ->withRelatedId($request->getQueryParam('relatedId'))
            ->withParentType($request->getQueryParam('parentType'))
            ->withParentId($request->getQueryParam('parentId'))
            ->withEmailAddress($request->getQueryParam('emailAddress'));

        $result = $this->getEmailTemplateService()->process($id, $data);

        return $result->getValueMap();

    }

    private function getEmailTemplateService(): Service
    {
        return $this->injectableFactory->create(Service::class);
    }
}
