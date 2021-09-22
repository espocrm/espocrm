<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2021 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

namespace tests\integration\Espo\Core\Job;

use Espo\Core\{
    Job\JobManager,
    Job\Job\Status,
    Job\JobSchedulerFactory,
    Job\QueueName,
    ORM\EntitManager,
};

use tests\integration\testClasses\Job\Job as TestJob;

class JobTest extends \tests\integration\Core\BaseTestCase
{
    /**
     * @var JobManager
     */
    private $jobManager;

    /**
     * @var EntitManager
     */
    private $entityManager;

    /**
     * @var JobSchedulerFactory
     */
    private $schedulerFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->jobManager = $this->getContainer()->get('jobManager');

        $this->entityManager = $this->getContainer()->get('entityManager');

        $this->schedulerFactory = $this->getContainer()
            ->get('injectableFactory')
            ->create(JobSchedulerFactory::class);
    }

    public function testScheduler(): void
    {
        $job = $this->schedulerFactory
            ->create()
            ->setClassName(TestJob::class)
            ->setQueue(QueueName::Q0)
            ->schedule();

        $this->jobManager->processQueue(QueueName::Q0, 10);

        $jobReloaded = $this->entityManager->getEntity('Job', $job->getId());

        $this->assertEquals(Status::SUCCESS, $jobReloaded->getStatus());
    }

    public function testProcessQueueNoGroup(): void
    {
        $job = $this->entityManager->createEntity('Job', [
            'job' => 'Dummy',
            'queue' => 'q0',
        ]);

        $this->jobManager->processQueue('q0', 10);

        $jobReloaded = $this->entityManager->getEntity('Job', $job->id);

        $this->assertEquals(Status::SUCCESS, $jobReloaded->getStatus());
    }

    public function testProcessQueueGroupAll(): void
    {
        $job1 = $this->entityManager->createEntity('Job', [
            'job' => 'Dummy',
            'group' => 'group-0',
        ]);

        $job2 = $this->entityManager->createEntity('Job', [
            'job' => 'Dummy',
            'group' => 'group-1',
        ]);

        $job3 = $this->entityManager->createEntity('Job', [
            'job' => 'Dummy',
            'group' => 'group-1',
        ]);

        $this->jobManager->process();

        $job1Reloaded = $this->entityManager->getEntity('Job', $job1->getId());
        $job2Reloaded = $this->entityManager->getEntity('Job', $job2->getId());
        $job3Reloaded = $this->entityManager->getEntity('Job', $job3->getId());

        $this->assertEquals(Status::SUCCESS, $job1Reloaded->getStatus());
        $this->assertEquals(Status::SUCCESS, $job2Reloaded->getStatus());
        $this->assertEquals(Status::SUCCESS, $job3Reloaded->getStatus());
    }

    public function testProcessQueueGroupSeparate(): void
    {
        $job1 = $this->entityManager->createEntity('Job', [
            'job' => 'Dummy',
            'group' => 'group-0',
        ]);

        $job2 = $this->entityManager->createEntity('Job', [
            'job' => 'Dummy',
            'group' => 'group-1',
        ]);

        $job3 = $this->entityManager->createEntity('Job', [
            'job' => 'Dummy',
            'group' => 'group-1',
        ]);

        $this->jobManager->processGroup('group-1', 100);

        $job1Reloaded = $this->entityManager->getEntity('Job', $job1->getId());
        $job2Reloaded = $this->entityManager->getEntity('Job', $job2->getId());
        $job3Reloaded = $this->entityManager->getEntity('Job', $job3->getId());

        $this->assertEquals(Status::PENDING, $job1Reloaded->getStatus());
        $this->assertEquals(Status::SUCCESS, $job2Reloaded->getStatus());
        $this->assertEquals(Status::SUCCESS, $job3Reloaded->getStatus());

        $this->jobManager->processGroup('group-0', 100);

        $job1Reloaded2 = $this->entityManager->getEntity('Job', $job1->getId());

        $this->assertEquals(Status::SUCCESS, $job1Reloaded2->getStatus());
    }

    public function testRunJobById(): void
    {
        $job = $this->entityManager->createEntity('Job', [
            'job' => 'Dummy',
            'status' => Status::READY,
        ]);

        $this->jobManager->runJobById($job->id);

        $jobReloaded = $this->entityManager->getEntity('Job', $job->id);

        $this->assertEquals(Status::SUCCESS, $jobReloaded->getStatus());
    }

    public function testRunJobByEntity(): void
    {
        $job = $this->entityManager->createEntity('Job', [
            'job' => 'Dummy',
        ]);

        $this->jobManager->runJob($job);

        $jobReloaded = $this->entityManager->getEntity('Job', $job->id);

        $this->assertEquals(Status::SUCCESS, $jobReloaded->getStatus());
    }

    public function testRunJobWithClassName(): void
    {
        $job = $this->entityManager->createEntity('Job', [
            'className' => TestJob::class,
            'data' => (object) [
                'test' => '1',
            ],
        ]);

        $this->jobManager->runJob($job);

        $jobReloaded = $this->entityManager->getEntity('Job', $job->getId());

        $this->assertEquals(Status::SUCCESS, $jobReloaded->getStatus());
    }
}
