<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2026 EspoCRM, Inc.
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

namespace tests\integration\Espo\Core\Utils\ScheduledJob;

use Espo\Core\Utils\ScheduledJob\Populator;
use Espo\Entities\ScheduledJob;
use Espo\ORM\Query\DeleteBuilder;
use tests\integration\Core\BaseTestCase;

class PopulatorTest extends BaseTestCase
{
    public function testPopulate(): void
    {
        $deleteQuery = DeleteBuilder::create()
            ->from(ScheduledJob::ENTITY_TYPE)
            ->where([
                ScheduledJob::FIELD_JOB => 'Cleanup',
            ])
            ->build();

        $this->getEntityManager()->getQueryExecutor()->execute($deleteQuery);


        $this->assertEquals(0, $this->getJobCount());

        $populator = $this->getInjectableFactory()->create(Populator::class);

        $populator->populate();

        $this->assertEquals(1, $this->getJobCount());
    }

    private function getJobCount(): int
    {
        return $this->getEntityManager()
            ->getRDBRepositoryByClass(ScheduledJob::class)
            ->where([
                ScheduledJob::FIELD_JOB => 'Cleanup',
            ])
            ->count();
    }
}
