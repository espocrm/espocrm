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

namespace Espo\Core\Job;

class QueueName
{
    /**
     * Executes as soon as possible. Non-parallel.
     */
    public const Q0 = 'q0';

    /**
     * Executes every minute. Non-parallel.
     */
    public const Q1 = 'q1';

    /**
     * Executes as soon as possible. For email processing. Non-parallel.
     */
    public const E0 = 'e0';

    /**
     * Executes in the main queue pool in parallel. Along with jobs without specified queue.
     * A portion is always picked for a queue iteration, even if there are no-queue
     * jobs ordered before. E.g. if the portion size is 100, and there are 200 empty-queue
     * jobs and 5 m0 jobs, 95 and 5 will be picked respectfully.
     */
    const M0 = 'm0';
}
