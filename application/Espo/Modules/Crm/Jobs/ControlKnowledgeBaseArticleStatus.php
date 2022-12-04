<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014  Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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
 ************************************************************************/

namespace Espo\Modules\Crm\Jobs;

use Espo\Core\Utils\DateTime;
use Espo\Modules\Crm\Entities\KnowledgeBaseArticle;
use Espo\Core\Job\JobDataLess;
use Espo\Core\ORM\EntityManager;

class ControlKnowledgeBaseArticleStatus implements JobDataLess
{
    private EntityManager $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function run(): void
    {
        $list = $this->entityManager
            ->getRDBRepository(KnowledgeBaseArticle::ENTITY_TYPE)
            ->where([
                'expirationDate<=' => date(DateTime::SYSTEM_DATE_FORMAT),
                'status' => KnowledgeBaseArticle::STATUS_PUBLISHED,
            ])
            ->find();

        foreach ($list as $e) {
            $e->set('status', KnowledgeBaseArticle::STATUS_ARCHIVED);

            $this->entityManager->saveEntity($e);
        }
    }
}
