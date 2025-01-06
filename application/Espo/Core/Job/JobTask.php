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

use Espo\ORM\Name\Attribute;
use Spatie\Async\Task as AsyncTask;

use Espo\Core\Application;
use Espo\Core\Application\Runner\Params as RunnerParams;
use Espo\Core\ApplicationRunners\Job as JobRunner;
use Espo\Core\Utils\Log;

use Throwable;

class JobTask extends AsyncTask
{
    private string $jobId;

    public function __construct(string $jobId)
    {
        $this->jobId = $jobId;
    }

    /**
     * @return void
     */
    public function configure()
    {}

    /**
     * @return void
     */
    public function run()
    {
        $app = new Application();

        $params = RunnerParams::create()->with(Attribute::ID, $this->jobId);

        try {
            $app->run(JobRunner::class, $params);
        } catch (Throwable $e) {
            $log = $app->getContainer()->getByClass(Log::class);

            $log->error("JobTask: Failed to run job '$this->jobId'. Error: " . $e->getMessage());
        }
    }
}
