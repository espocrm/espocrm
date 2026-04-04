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

use Espo\Core\Acl\Table;
use Espo\Core\Name\Field;
use Espo\Core\Record\CreateParams;
use Espo\Core\Record\DeleteParams;
use Espo\Core\Record\ServiceContainer;
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
            'userName' => 'test1',
            'teamsIds' => [$team->getId()],
        ]);

        $pipelineService = $this->getContainer()
            ->getByClass(ServiceContainer::class)
            ->getByClass(Pipeline::class);

        $pipelineStageService = $this->getContainer()
            ->getByClass(ServiceContainer::class)
            ->getByClass(PipelineStage::class);

        //

        $pipeline1 = $pipelineService->create((object) [
            Field::NAME => 'Test 1',
            Pipeline::FIELD_ENTITY_TYPE => Opportunity::ENTITY_TYPE,
        ], CreateParams::create());

        $this->assertCount(6, $pipeline1->getStages());

        //

        $pipeline2 = $pipelineService->create((object) [
            Field::NAME => 'Test 2',
            Pipeline::FIELD_ENTITY_TYPE => Opportunity::ENTITY_TYPE,
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
            PipelineStage::FIELD_MAPPED_STATUS => 'Closed Won',
        ], CreateParams::create());

        $pipelineStageService->create((object) [
            Field::NAME => 'Closed List',
            PipelineStage::ATTR_PIPELINE_ID => $pipeline2->getId(),
            PipelineStage::FIELD_MAPPED_STATUS => 'Closed List',
        ], CreateParams::create());

        $em->refreshEntity($pipeline2);

        $this->assertCount(3, $pipeline2->getStages());
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
