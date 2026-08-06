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

namespace integration\Espo\Tools\EmailTemplate;

use DateTimeImmutable;
use Espo\Core\Binding\Binder;
use Espo\Core\Binding\BindingProcessor;
use Espo\Core\Field\Link;
use Espo\Core\Field\LinkMultiple;
use Espo\Core\Utils\DateTime\Clock;
use Espo\Entities\EmailTemplate;
use Espo\Entities\Team;
use Espo\Entities\User;
use Espo\Modules\Crm\Entities\Account;
use Espo\Modules\Crm\Entities\Lead;
use Espo\Tools\EmailTemplate\Data as TemplateData;
use Espo\Tools\EmailTemplate\Params as TemplateParams;
use Espo\Tools\EmailTemplate\Processor;
use tests\integration\Core\BaseTestCase;

class ProcessorTest extends BaseTestCase
{
    public function testProcessAccess(): void
    {
        $em = $this->getEntityManager();

        $team = $em->getRDBRepositoryByClass(Team::class)->getNew();
        $team
            ->setName('Team 1');
        $em->saveEntity($team);

        $user = $em->getRDBRepositoryByClass(User::class)->getNew();
        $user
            ->setFirstName('Test')
            ->setLastName('Hello')
            ->setUserName('test')
            ->setDefaultTeam(Link::fromEntity($team))
            ->setTeams(LinkMultiple::create()->withAddedId($team->getId()));
        $em->saveEntity($user);

        $template1 = $em->getRDBRepositoryByClass(EmailTemplate::class)->getNew();
        $template1->setMultiple([
            'subject' => '{Lead.name} {Lead.assignedUser.name} {Lead.assignedUser.password}',
            'body' => '{Lead.name} {Lead.assignedUser.name} {Lead.assignedUser.password}',
        ]);
        $em->saveEntity($template1);

        $template2 = $em->getRDBRepositoryByClass(EmailTemplate::class)->getNew();
        $template2->setMultiple([
            'subject' => '{{name}} {{assignedUser.name}} {{assignedUser.password}}',
            'body' => '{{name}} {{assignedUser.name}} {{assignedUser.password}}',
        ]);
        $em->saveEntity($template2);

        $template3 = $em->getRDBRepositoryByClass(EmailTemplate::class)->getNew();
        $template3->setMultiple([
            'subject' => '{{name}} {{password}} {{defaultTeam.name}}',
            'body' => '{User.name} {User.password} {User.defaultTeam.name}',
        ]);
        $em->saveEntity($template3);

        $template4 = $em->getRDBRepositoryByClass(EmailTemplate::class)->getNew();
        $template4->setMultiple([
            'subject' => '{{name}} {{password}} {{defaultTeam.name}}',
            'body' => '{User.name} {User.password} {User.defaultTeam.name}',
        ]);
        $em->saveEntity($template4);

        $lead = $em->getRDBRepositoryByClass(Lead::class)->getNew();
        $lead
            ->setFirstName('Lead')
            ->setLastName('Abc')
            ->setAssignedUser($user);
        $em->saveEntity($lead);

        $processor = $this->getInjectableFactory()->create(Processor::class);

        $params = TemplateParams::create()
            ->withApplyAcl(false);

        $data = TemplateData::create();

        //

        $emailData1 = $processor->process($template1, $params, $data->withParent($lead));

        $this->assertEquals(
            'Lead Abc Test Hello {Lead.assignedUser.password}',
            $emailData1->getSubject(),
        );

        $this->assertEquals(
            'Lead Abc Test Hello {Lead.assignedUser.password}',
            $emailData1->getBody(),
        );

        //

        $emailData2 = $processor->process($template2, $params, $data->withParent($lead));

        $this->assertEquals(
            'Lead Abc Test Hello ',
            $emailData2->getSubject(),
        );

        $this->assertEquals(
            'Lead Abc Test Hello ',
            $emailData2->getBody(),
        );

        //

        $emailData3 = $processor->process($template3, $params, $data->withParent($user));

        $this->assertEquals(
            'Test Hello  ',
            $emailData3->getSubject(),
        );

        $this->assertEquals(
            'Test Hello {User.password} {User.defaultTeam.name}',
            $emailData3->getBody(),
        );
    }

    public function testProcessAndCleanup(): void
    {
        $this->setConfigParams([
            'dateFormat' => 'MM/DD/YYYY',
        ]);

        $clock = $this->createMock(Clock::class);
        $clock->method('now')
            ->willReturn(new DateTimeImmutable('2030-01-02 00:00'));

        $this->setApplication(
            $this->createApplication(
                binding: new class ($clock) implements BindingProcessor {

                    public function __construct(private Clock $clock) {}

                    public function process(Binder $binder): void
                    {
                        $binder->bindInstance(Clock::class, $this->clock);
                    }
                },
                reuse: true,
            )
        );

        //

        $em = $this->getEntityManager();

        $account = $em->getRDBRepositoryByClass(Account::class)->getNew();
        $account->setName('Account 1');
        $em->saveEntity($account);

        $lead = $em->getRDBRepositoryByClass(Lead::class)->getNew();
        $lead->setFirstName('One');
        $em->saveEntity($lead);
        $em->refreshEntity($lead);

        $template1 = $em->getRDBRepositoryByClass(EmailTemplate::class)->getNew();
        $template1->setMultiple([
            'subject' => 'Test {Person.firstName} test {Account.name}',
            'body' => 'Test {Person.name} test {Account.name} {today}.',
        ]);
        $em->saveEntity($template1);

        //

        $processor = $this->getInjectableFactory()->create(Processor::class);

        $params = TemplateParams::create()
            ->withApplyAcl(false);

        $data = TemplateData::create();

        //

        $emailData1 = $processor->process($template1, $params, $data->withParent($account));

        $this->assertEquals(
            'Test  test Account 1',
            $emailData1->getSubject(),
        );

        $this->assertEquals(
            'Test  test Account 1 01/02/2030.',
            $emailData1->getBody(),
        );

        //

        $emailData2 = $processor->process($template1, $params, $data->withParent($lead));

        $this->assertEquals(
            'Test One test {Account.name}',
            $emailData2->getSubject(),
        );

        $this->assertEquals(
            'Test One test {Account.name} 01/02/2030.',
            $emailData2->getBody(),
        );
    }
}
