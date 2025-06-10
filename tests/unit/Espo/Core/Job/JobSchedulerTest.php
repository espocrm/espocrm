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

namespace tests\unit\Espo\Core\Job;

use Espo\Core\Field\DateTime as DateTimeField;
use Espo\Core\Job\JobScheduler;
use Espo\Core\Job\JobScheduler\Creator;
use Espo\Core\Job\QueueName;
use Espo\Core\Job\Job\Data;
use PHPUnit\Framework\TestCase;
use tests\unit\testClasses\Core\Job\TestJob;

use DateTimeImmutable;
use DateInterval;

class JobSchedulerTest extends TestCase
{
    private ?JobScheduler\Creator $creator = null;

    protected function setUp(): void
    {
        $this->creator = $this->createMock(JobScheduler\Creator::class);
    }

    public function testSchedule1(): void
    {
        $scheduler = new JobScheduler($this->creator);

        $time = new DateTimeImmutable();

        $delay = DateInterval::createFromDateString('1 minute');

        $expectedData = new Creator\Data(
            className: TestJob::class,
            queue: QueueName::Q0,
            group: null,
            data: new Data((object) ['test' => '1']),
            time: DateTimeField::fromDateTime($time)->addMinutes(1),
        );

        $this->creator
            ->expects($this->once())
            ->method('create')
            ->with($expectedData);

        $scheduler
            ->setClassName(TestJob::class)
            ->setQueue(QueueName::Q0)
            ->setData([
                'test' => '1',
            ])
            ->setTime($time)
            ->setDelay($delay)
            ->schedule();
    }

    public function testSchedule2(): void
    {
        $scheduler = new JobScheduler($this->creator);

        $time = new DateTimeImmutable();

        $expectedData = new Creator\Data(
            className: TestJob::class,
            queue: null,
            group: 'g-1',
            data: (new Data((object) ['test' => '1']))->withTargetType('TestType')->withTargetId('test-id'),
            time: DateTimeField::fromDateTime($time),
        );

        $this->creator
            ->expects($this->once())
            ->method('create')
            ->with($expectedData);

        $data = Data
            ::create([
                'test' => '1',
            ])
            ->withTargetId('test-id')
            ->withTargetType('TestType');

        $scheduler
            ->setClassName(TestJob::class)
            ->setGroup('g-1')
            ->setData($data)
            ->setTime($time)
            ->schedule();
    }
}
