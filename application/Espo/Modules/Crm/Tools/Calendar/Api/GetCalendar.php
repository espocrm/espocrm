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
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Acl;
use Espo\Core\Field\DateTime;
use Espo\Entities\User;
use Espo\Modules\Crm\Tools\Calendar\FetchParams;
use Espo\Modules\Crm\Tools\Calendar\Item as CalendarItem;
use Espo\Modules\Crm\Tools\Calendar\Service;

use stdClass;

/**
 * Calendar events.
 */
class GetCalendar implements Action
{
    private const MAX_CALENDAR_RANGE = 123;

    public function __construct(
        private Service $calendarService,
        private Acl $acl,
        private User $user
    ) {}

    public function process(Request $request): Response
    {
        if (!$this->acl->check('Calendar')) {
            throw new Forbidden();
        }

        $from = $request->getQueryParam('from');
        $to = $request->getQueryParam('to');
        $isAgenda = $request->getQueryParam('agenda') === 'true';

        if (empty($from) || empty($to)) {
            throw new BadRequest();
        }

        if (strtotime($to) - strtotime($from) > self::MAX_CALENDAR_RANGE * 24 * 3600) {
            throw new Forbidden('Too long range.');
        }

        $scopeList = null;

        if ($request->getQueryParam('scopeList') !== null) {
            $scopeList = explode(',', $request->getQueryParam('scopeList'));
        }

        $userId = $request->getQueryParam('userId');
        $userIdList = $request->getQueryParam('userIdList');
        $teamIdList = $request->getQueryParam('teamIdList');

        $fetchParams = FetchParams
            ::create(
                DateTime::fromString($from),
                DateTime::fromString($to)
            )
            ->withScopeList($scopeList);

        if ($teamIdList) {
            $teamIdList = explode(',', $teamIdList);

            $raw = self::itemListToRaw(
                $this->calendarService->fetchForTeams($teamIdList, $fetchParams)
            );

            return ResponseComposer::json($raw);
        }

        if ($userIdList) {
            $userIdList = explode(',', $userIdList);

            $raw = self::itemListToRaw(
                $this->calendarService->fetchForUsers($userIdList, $fetchParams)
            );

            return ResponseComposer::json($raw);
        }

        if (!$userId) {
            $userId = $this->user->getId();
        }

        $fetchParams = $fetchParams
            ->withIsAgenda($isAgenda)
            ->withWorkingTimeRanges();

        $raw = self::itemListToRaw(
            $this->calendarService->fetch($userId, $fetchParams)
        );

        return ResponseComposer::json($raw);
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
