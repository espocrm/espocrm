<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2024 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

namespace Espo\Modules\Crm\EntryPoints;

use Espo\Core\Exceptions\Error;
use Espo\Core\Utils\Client\ActionRenderer;
use Espo\Entities\EmailAddress;
use Espo\Modules\Crm\Entities\Campaign;
use Espo\Modules\Crm\Entities\EmailQueueItem;
use Espo\Modules\Crm\Entities\MassEmail;
use Espo\Modules\Crm\Entities\TargetList;
use Espo\Modules\Crm\Tools\Campaign\LogService;
use Espo\ORM\Collection;
use Espo\Repositories\EmailAddress as EmailAddressRepository;
use Espo\Modules\Crm\Tools\MassEmail\Util as MassEmailUtil;

use Espo\Core\Api\Request;
use Espo\Core\Api\Response;
use Espo\Core\EntryPoint\EntryPoint;
use Espo\Core\EntryPoint\Traits\NoAuth;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\NotFound;
use Espo\Core\HookManager;
use Espo\Core\ORM\EntityManager;
use Espo\Core\Utils\Hasher;
use Espo\Core\Utils\Metadata;

class Unsubscribe implements EntryPoint
{
    use NoAuth;

    public function __construct(
        private EntityManager $entityManager,
        private HookManager $hookManager,
        private Metadata $metadata,
        private Hasher $hasher,
        private LogService $service,
        private MassEmailUtil $util,
        private ActionRenderer $actionRenderer
    ) {}

    /**
     * @throws BadRequest
     * @throws Error
     * @throws NotFound
     */
    public function run(Request $request, Response $response): void
    {
        $id = $request->getQueryParam('id') ?? null;
        $emailAddress = $request->getQueryParam('emailAddress') ?? null;
        $hash = $request->getQueryParam('hash') ?? null;

        if ($emailAddress && $hash) {
            $this->processWithHash($response, $emailAddress, $hash);

            return;
        }

        if (!$id) {
            throw new BadRequest();
        }

        $queueItemId = $id;

        /** @var ?EmailQueueItem $queueItem */
        $queueItem = $this->entityManager->getEntityById(EmailQueueItem::ENTITY_TYPE, $queueItemId);

        if (!$queueItem) {
            throw new NotFound();
        }

        $campaign = null;
        $target = null;
        $massEmail = null;
        $massEmailId = $queueItem->getMassEmailId();

        if ($massEmailId) {
            /** @var MassEmail $massEmail */
            $massEmail = $this->entityManager->getEntityById(MassEmail::ENTITY_TYPE, $massEmailId);
        }

        if ($massEmail) {
            $campaignId = $massEmail->getCampaignId();

            if ($campaignId) {
                /** @var ?Campaign $campaign */
                $campaign = $this->entityManager->getEntityById(Campaign::ENTITY_TYPE, $campaignId);
            }

            $targetType = $queueItem->getTargetType();
            $targetId = $queueItem->getTargetId();

            $target = $this->entityManager->getEntityById($targetType, $targetId);

            if (!$target) {
                throw new NotFound();
            }

            if ($massEmail->optOutEntirely()) {
                $emailAddress = $target->get('emailAddress');

                if ($emailAddress) {
                    $ea = $this->getEmailAddressRepository()->getByAddress($emailAddress);

                    if ($ea) {
                        $ea->set('optOut', true);
                        $this->entityManager->saveEntity($ea);
                    }
                }
            }

            $link = $this->util->getLinkByEntityType($target->getEntityType());

            /** @var Collection<TargetList> $targetListList */
            $targetListList = $this->entityManager
                ->getRDBRepository(MassEmail::ENTITY_TYPE)
                ->getRelation($massEmail, 'targetLists')
                ->find();

            foreach ($targetListList as $targetList) {
                $relation = $this->entityManager
                    ->getRDBRepository(TargetList::ENTITY_TYPE)
                    ->getRelation($targetList, $link);

                if ($relation->getColumn($target, 'optedOut')) {
                    continue;
                }

                $relation->updateColumnsById($target->getId(), ['optedOut' => true]);

                $hookData = [
                   'link' => $link,
                   'targetId' => $targetId,
                   'targetType' => $targetType,
                ];

                $this->hookManager->process(
                    TargetList::ENTITY_TYPE,
                    'afterOptOut',
                    $targetList,
                    [],
                    $hookData
                );
            }

            $this->hookManager->process($target->getEntityType(), 'afterOptOut', $target, [], []);

            $this->display($response, ['queueItemId' => $queueItemId]);
        }

        if ($campaign && $target) {
            $this->service->logOptedOut($campaign->getId(), $queueItem, $target);
        }
    }

    /**
     * @param array<string, mixed> $actionData
     */
    protected function display(Response $response, array $actionData): void
    {
        $data = [
            'actionData' => $actionData,
            'view' => $this->metadata->get(['clientDefs', 'Campaign', 'unsubscribeView']),
            'template' => $this->metadata->get(['clientDefs', 'Campaign', 'unsubscribeTemplate']),
        ];

        $params = ActionRenderer\Params::create('crm:controllers/unsubscribe', 'unsubscribe', $data);

        $this->actionRenderer->write($response, $params);
    }

    /**
     * @throws NotFound
     */
    protected function processWithHash(Response $response, string $emailAddress, string $hash): void
    {
        $hash2 = $this->hasher->hash($emailAddress);

        if ($hash2 !== $hash) {
            throw new NotFound();
        }

        $repository = $this->getEmailAddressRepository();

        $ea = $repository->getByAddress($emailAddress);

        if (!$ea) {
            throw new NotFound();
        }

        $entityList = $repository->getEntityListByAddressId($ea->getId());

        if (!$ea->isOptedOut()) {
            $ea->set('optOut', true);

            $this->entityManager->saveEntity($ea);

            foreach ($entityList as $entity) {
                $this->hookManager->process($entity->getEntityType(), 'afterOptOut', $entity, [], []);
            }
        }

        $this->display($response,[
            'emailAddress' => $emailAddress,
            'hash' => $hash,
        ]);
    }

    private function getEmailAddressRepository(): EmailAddressRepository
    {
        /** @var EmailAddressRepository */
        return $this->entityManager->getRepository(EmailAddress::ENTITY_TYPE);
    }
}
