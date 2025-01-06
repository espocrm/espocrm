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

namespace Espo\Modules\Crm\Tools\Calendar\Api;

use Espo\Core\Api\Action;
use Espo\Core\Api\Request;
use Espo\Core\Api\Response;
use Espo\Core\Api\ResponseComposer;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Field\DateTime;
use Espo\Modules\Crm\Tools\Calendar\Item as CalendarItem;
use Espo\Modules\Crm\Tools\Calendar\Service;

use stdClass;

/**
 * Busy-ranges.
 */
class GetBusyRanges implements Action
{
    public function __construct(private Service $calendarService) {}

    public function process(Request $request): Response
    {
        $from = $request->getQueryParam('from');
        $to = $request->getQueryParam('to');
        $userIdListString = $request->getQueryParam('userIdList');

        if (!$from || !$to || !$userIdListString) {
            throw new BadRequest();
        }

        $userIdList = explode(',', $userIdListString);

        $map = $this->calendarService->fetchBusyRangesForUsers(
            $userIdList,
            DateTime::fromString($from),
            DateTime::fromString($to),
            $request->getQueryParam('entityType'),
            $request->getQueryParam('entityId')
        );

        $result = (object) [];

        foreach ($map as $userId => $itemList) {
            $result->$userId = self::itemListToRaw($itemList);
        }

        return ResponseComposer::json($result);
    }

    /**
     * @param CalendarItem[] $itemList
     * @return stdClass[]
     */
    private static function itemListToRaw(array $itemList): array
    {
        return array_map(fn (CalendarItem $item) => $item->getRaw(), $itemList);
    }
}
