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

namespace Espo\Modules\Crm\Services;

use Espo\Modules\Crm\Entities\MassEmail as MassEmailEntity;

use Espo\Core\{
    Exceptions\Forbidden,
    Exceptions\BadRequest,
    Exceptions\NotFound,
    Exceptions\Error,
};

use Espo\{
    ORM\Entity,
    Services\Record as RecordService,
    Modules\Crm\Tools\MassEmail\Processor,
    Modules\Crm\Tools\MassEmail\Queue,
};

class MassEmail extends RecordService
{
    protected $mandatorySelectAttributeList = [
        'campaignId',
    ];

    protected function beforeCreateEntity(Entity $entity, $data)
    {
        parent::beforeCreateEntity($entity, $data);

        if (!$this->getAcl()->check($entity, 'edit')) {
            throw new Forbidden();
        }
    }

    protected function afterDeleteEntity(Entity $massEmail)
    {
        parent::afterDeleteEntity($massEmail);

        $delete = $this->getEntityManager()
            ->getQueryBuilder()
            ->delete()
            ->from('EmailQueueItem')
            ->where([
                 'massEmailId' => $massEmail->getId(),
            ])
            ->build();

        $this->getEntityManager()->getQueryExecutor()->execute($delete);
    }

    public function processTest(string $id, array $targetDataList): void
    {
        $targetList = [];

        if (count($targetDataList) === 0) {
            throw new BadRequest("Empty target list.");
        }

        foreach ($targetDataList as $item) {
            if (empty($item->id) || empty($item->type)) {
                throw new BadRequest();
            }

            $targetId = $item->id;
            $targetType = $item->type;

            $target = $this->getEntityManager()->getEntity($targetType, $targetId);

            if (!$target) {
                throw new Error("Target not found.");
            }

            if (!$this->getAcl()->check($target, 'read')) {
                throw new Forbidden();
            }

            $targetList[] = $target;
        }

        $massEmail = $this->getEntityManager()->getEntity('MassEmail', $id);

        if (!$massEmail) {
            throw new NotFound();
        }

        if (!$this->getAcl()->check($massEmail, 'read')) {
            throw new Forbidden();
        }

        $this->createTestQueue($massEmail, $targetList);

        $this->processTestSending($massEmail);
    }

    protected function createTestQueue(MassEmailEntity $massEmail, iterable $targetList): void
    {
        $queue = $this->injectableFactory->create(Queue::class);

        $queue->create($massEmail, true, $targetList);
    }

    protected function processTestSending(MassEmailEntity $massEmail): void
    {
        $processor = $this->injectableFactory->create(Processor::class);

        $processor->process($massEmail, true);
    }

    public function getSmtpAccountDataList(): array
    {
        if (
            !$this->getAcl()->checkScope('MassEmail', 'create') &&
            !$this->getAcl()->checkScope('MassEmail', 'edit')
        ) {
            throw new Forbidden();
        }

        $dataList = [];

        $inboundEmailList = $this->getEntityManager()
            ->getRDBRepository('InboundEmail')
            ->where([
                'useSmtp' => true,
                'status' => 'Active',
                'smtpIsForMassEmail' => true,
                ['emailAddress!=' => ''],
                ['emailAddress!=' => null],
            ])
            ->find();

        foreach ($inboundEmailList as $inboundEmail) {
            $item = (object) [];

            $key = 'inboundEmail:' . $inboundEmail->getId();

            $item->key = $key;
            $item->emailAddress = $inboundEmail->get('emailAddress');
            $item->fromName = $inboundEmail->get('fromName');

            $dataList[] = $item;
        }

        return $dataList;
    }
}
