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

namespace Espo\Core\Portal\Acl\AccessChecker;

use Espo\Core\Acl\ScopeData;
use Espo\Core\Portal\Acl\Table;

/**
 * Checks scope access.
 */
class ScopeChecker
{
    public function __construct()
    {}

    public function check(ScopeData $data, ?string $action = null, ?ScopeCheckerData $checkerData = null): bool
    {
        if ($data->isFalse()) {
            return false;
        }

        if ($data->isTrue()) {
            return true;
        }

        if ($action === null) {
            return true;
        }

        $level = $data->get($action);

        if ($level === Table::LEVEL_ALL || $level === Table::LEVEL_YES) {
            return true;
        }

        if ($level === Table::LEVEL_NO) {
            return false;
        }

        if (!$checkerData) {
            return false;
        }

        if ($level === Table::LEVEL_OWN || $level === Table::LEVEL_ACCOUNT || $level === Table::LEVEL_CONTACT) {
            if ($checkerData->isOwn()) {
                return true;
            }
        }

        if ($level === Table::LEVEL_ACCOUNT || $level === Table::LEVEL_CONTACT) {
            if ($checkerData->inContact()) {
                return true;
            }
        }

        if ($level === Table::LEVEL_ACCOUNT) {
            if ($checkerData->inAccount()) {
                return true;
            }
        }

        return false;
    }
}
