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

use Espo\Tools\Notification\RecordService as Service;

use Espo\Core\{
    Controllers\RecordBase,
    Api\Request,
    Api\Response,
    Exceptions\BadRequest,
    Exceptions\Error,
    Exceptions\Forbidden,
    Select\SearchParams,
    Select\Where\Item as WhereItem};

use stdClass;

class Notification extends RecordBase
{
    public static $defaultAction = 'list';

    /**
     * @throws BadRequest
     * @throws Forbidden
     * @throws Error
     */
    public function getActionList(Request $request, Response $response): stdClass
    {
        $searchParamsAux = $this->searchParamsFetcher->fetch($request);

        $offset = $searchParamsAux->getOffset();
        $maxSize = $searchParamsAux->getMaxSize();

        $after = $request->getQueryParam('after');

        $searchParams = SearchParams
            ::create()
            ->withOffset($offset)
            ->withMaxSize($maxSize);

        if ($after) {
            $searchParams = $searchParams
                ->withWhereAdded(
                    WhereItem
                        ::createBuilder()
                        ->setAttribute('createdAt')
                        ->setType(WhereItem\Type::AFTER)
                        ->setValue($after)
                        ->build()
                );

        }

        $userId = $this->user->getId();

        $recordCollection = $this->getNotificationService()->get($userId, $searchParams);

        return (object) [
            'total' => $recordCollection->getTotal(),
            'list' => $recordCollection->getValueMapList(),
        ];
    }

    public function getActionNotReadCount(): int
    {
        $userId = $this->user->getId();

        return $this->getNotificationService()->getNotReadCount($userId);
    }

    public function postActionMarkAllRead(Request $request): bool
    {
        $userId = $this->user->getId();

        $this->getNotificationService()->markAllRead($userId);

        return true;
    }

    private function getNotificationService(): Service
    {
        return $this->injectableFactory->create(Service::class);
    }
}
