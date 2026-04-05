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

namespace tests\integration\Espo\Tools\Pipeline;

use Espo\Classes\AppParams\Pipelines;
use Espo\Core\Acl\Table;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Field\Date;
use Espo\Core\Name\Field;
use Espo\Core\Record\CreateParams;
use Espo\Core\Record\DeleteParams;
use Espo\Core\Record\ServiceContainer;
use Espo\Core\Record\UpdateParams;
use Espo\Entities\Pipeline;
use Espo\Entities\PipelineStage;
use Espo\Entities\Role;
use Espo\Entities\Team;
use Espo\Modules\Crm\Entities\Opportunity;
use Espo\Tools\EntityManager\EntityManager as EntityManagerTool;
use tests\integration\Core\BaseTestCase;

class PipelineTest extends BaseTestCase
{
    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testPipeline1(): void
    {
        $this->initPipeline(Opportunity::ENTITY_TYPE);

        $em = $this->getEntityManager();

        $role = $em->getRDBRepositoryByClass(Role::class)->getNew();
        $role
            ->set(Field::NAME, 'Test')
            ->setRawData([
                Opportunity::ENTITY_TYPE => [
                    Table::ACTION_EDIT => Table::LEVEL_TEAM,
                ],
            ]);
        $em->saveEntity($role);

        $team = $em->createEntity(Team::ENTITY_TYPE, [
            Field::NAME => 'Test',
            'rolesIds' => [$role->getId()],
        ]);

        $user1 = $this->createUser([
            'userName' => 'test-1',
            'teamsIds' => [$team->getId()],
        ]);

        $pipelineService = $this->getContainer()
            ->getByClass(ServiceContainer::class)
            ->getByClass(Pipeline::class);

        $pipelineStageService = $this->getContainer()
            ->getByClass(ServiceContainer::class)
            ->getByClass(PipelineStage::class);

        $oppService = $this->getContainer()
            ->getByClass(ServiceContainer::class)
            ->getByClass(Opportunity::class);

        //

        $pipeline1 = $pipelineService->create((object) [
            Field::NAME => 'Test 1',
            Pipeline::FIELD_ENTITY_TYPE => Opportunity::ENTITY_TYPE,
            Pipeline::FIELD_IS_AVAILABLE_FOR_ALL => false,
        ], CreateParams::create());

        $this->assertCount(6, $pipeline1->getStages());

        //

        $pipeline2 = $pipelineService->create((object) [
            Field::NAME => 'Test 2',
            Pipeline::FIELD_ENTITY_TYPE => Opportunity::ENTITY_TYPE,
            Pipeline::FIELD_IS_AVAILABLE_FOR_ALL => false,
            Field::TEAMS . 'Ids' => [$team->getId()],
        ], CreateParams::create());

        foreach ($pipeline2->getStages() as $stage) {
            $pipelineStageService->delete($stage->getId(), DeleteParams::create());
        }

        $pipelineStageService->create((object) [
            Field::NAME => 'Open',
            PipelineStage::ATTR_PIPELINE_ID => $pipeline2->getId(),
            PipelineStage::FIELD_MAPPED_STATUS => 'Prospecting',
        ], CreateParams::create());

        $pipelineStageService->create((object) [
            Field::NAME => 'Closed Won',
            PipelineStage::ATTR_PIPELINE_ID => $pipeline2->getId(),
            PipelineStage::FIELD_MAPPED_STATUS => Opportunity::STAGE_CLOSED_WON,
        ], CreateParams::create());

        $pipelineStageService->create((object) [
            Field::NAME => 'Closed Lost',
            PipelineStage::ATTR_PIPELINE_ID => $pipeline2->getId(),
            PipelineStage::FIELD_MAPPED_STATUS => Opportunity::STAGE_CLOSED_LOST,
        ], CreateParams::create());

        $thrown = false;

        try {
            // Non-existing status.
            $pipelineStageService->create((object) [
                Field::NAME => 'Test',
                PipelineStage::ATTR_PIPELINE_ID => $pipeline2->getId(),
                PipelineStage::FIELD_MAPPED_STATUS => 'Test',
            ], CreateParams::create());
        } catch (BadRequest) {
            $thrown = true;
        }

        $this->assertTrue($thrown);

        $em->refreshEntity($pipeline2);

        $this->assertCount(3, $pipeline2->getStages());

        //

        $opp = $oppService->create((object) [
            Field::NAME => 'Opp 1',
            Field::PIPELINE . 'Id' => $pipeline1->getId(),
            Field::PIPELINE_STAGE . 'Id' => $pipeline1->getStages()[0]->getId(),
            Opportunity::FIELD_CLOSED_DATE => Date::fromString('2026-01-01')->toString(),
            Opportunity::FIELD_AMOUNT => 1000,
        ], CreateParams::create());

        $opp = $oppService->update($opp->getId(), (object) [
            Field::PIPELINE . 'Id' => $pipeline2->getId(),
            Field::PIPELINE_STAGE . 'Id' => $pipeline2->getStages()[0]->getId(),
        ], UpdateParams::create());

        $this->assertEquals('Prospecting', $opp->getStage());

        //

        $opp = $oppService->create((object) [
            Field::NAME => 'Opp 2',
            Field::PIPELINE . 'Id' => $pipeline1->getId(),
            Field::PIPELINE_STAGE . 'Id' => $pipeline1->getStages()[4]->getId(),
            Opportunity::FIELD_CLOSED_DATE => Date::fromString('2026-01-01')->toString(),
            Opportunity::FIELD_AMOUNT => 1000,
        ], CreateParams::create());

        $this->assertEquals(Opportunity::STAGE_CLOSED_WON, $opp->getStage());

        //

        $opp = $oppService->create((object) [
            Field::NAME => 'Opp 3',
            Opportunity::FIELD_CLOSED_DATE => Date::fromString('2026-01-01')->toString(),
            Opportunity::FIELD_AMOUNT => 1000,
        ], CreateParams::create());

        $this->assertEquals($pipeline1->getId(), $opp->get(Field::PIPELINE . 'Id'));
        $this->assertEquals($pipeline1->getStages()[0]->getId(), $opp->get(Field::PIPELINE_STAGE . 'Id'));

        //

        $pipelinesParam = $this->getInjectableFactory()->create(Pipelines::class);

        $pipelines = $pipelinesParam->get();

        $this->assertCount(2, $pipelines[Opportunity::ENTITY_TYPE]);

        //

        $this->auth($user1->getUserName());
        $this->reCreateApplication();

        $pipelinesParam = $this->getInjectableFactory()->create(Pipelines::class);

        $pipelines = $pipelinesParam->get();

        $this->assertCount(1, $pipelines[Opportunity::ENTITY_TYPE]);
    }

    /**
     * @noinspection PhpUnhandledExceptionInspection
     * @noinspection PhpArrayKeyDoesNotMatchArrayShapeInspection
     * @noinspection PhpSameParameterValueInspection
     */
    private function initPipeline(string $entityType): void
    {
        $entityManager = $this->getInjectableFactory()->create(EntityManagerTool::class);

        $entityManager->update($entityType, [
            'pipelines' => true
        ]);
    }
}
