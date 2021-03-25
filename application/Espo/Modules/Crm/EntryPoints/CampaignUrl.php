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

namespace Espo\Modules\Crm\EntryPoints;

use Espo\{
    Modules\Crm\Entities\EmailQueueItem,
    Modules\Crm\Entities\CampaignTrackingUrl,
};

use Espo\Core\{
    Exceptions\NotFoundSilent,
    Exceptions\BadRequest,
    EntryPoint\EntryPoint,
    EntryPoint\Traits\NoAuth,
    Api\Request,
    Api\Response,
    ORM\EntityManager,
    ServiceFactory,
    Utils\Hasher,
    HookManager,
    Utils\ClientManager,
    Utils\Metadata,
};

class CampaignUrl implements EntryPoint
{
    use NoAuth;

    protected $entityManager;

    protected $serviceFactory;

    protected $hasher;

    protected $hookManager;

    protected $clientManager;

    protected $metadata;

    public function __construct(
        EntityManager $entityManager,
        ServiceFactory $serviceFactory,
        Hasher $hasher,
        HookManager $hookManager,
        ClientManager $clientManager,
        Metadata $metadata
    ) {
        $this->entityManager = $entityManager;
        $this->serviceFactory = $serviceFactory;
        $this->hasher = $hasher;
        $this->hookManager = $hookManager;
        $this->clientManager = $clientManager;
        $this->metadata = $metadata;
    }

    public function run(Request $request, Response $response) : void
    {
        $queueItemId = $request->get('queueItemId') ?? null;
        $trackingUrlId = $request->get('id') ?? null;
        $emailAddress = $request->get('emailAddress') ?? null;
        $hash = $request->get('hash') ?? null;
        $uid = $request->get('uid') ?? null;

        if (!$trackingUrlId) {
            throw new BadRequest();
        }

        $trackingUrl = $this->entityManager->getEntity('CampaignTrackingUrl', $trackingUrlId);

        if (!$trackingUrl) {
            throw new NotFoundSilent("Tracking URL '{$trackingUrlId}' not found.");
        }

        if ($emailAddress && $hash) {
            $this->processWithHash($trackingUrl, $emailAddress, $hash);
        }
        else if ($uid && $hash) {
            $this->processWithUniqueId($trackingUrl, $uid, $hash);
        }
        else {
            if (!$queueItemId) {
                throw new BadRequest();
            }

            $queueItem = $this->entityManager->getEntity('EmailQueueItem', $queueItemId);

            if (!$queueItem) {
                throw new NotFoundSilent();
            }

            $this->processWithQueueItem($trackingUrl, $queueItem);
        }

        if ($trackingUrl->get('action') === 'Show Message') {
            $this->displayMessage($trackingUrl->get('message'));

            return;
        }

        if ($trackingUrl->get('url')) {
            ob_clean();

            header('Location: ' . $trackingUrl->get('url') . '');

            die;
        }
    }

    protected function processWithQueueItem(CampaignTrackingUrl $trackingUrl, EmailQueueItem $queueItem) : void
    {
        $target = null;
        $campaign = null;

        $targetType = $queueItem->get('targetType');
        $targetId = $queueItem->get('targetId');

        if ($targetType && $targetId) {
            $target = $this->entityManager->getEntity($targetType, $targetId);
        }

        $campaignId = $trackingUrl->get('campaignId');

        if ($campaignId) {
            $campaign = $this->entityManager->getEntity('Campaign', $campaignId);
        }

        if ($target) {
            $this->hookManager->process('CampaignTrackingUrl', 'afterClick', $trackingUrl, [], [
                'targetId' => $targetId,
                'targetType' => $targetType,
            ]);
        }

        if ($campaign && $target) {
            $campaignService = $this->serviceFactory->create('Campaign');

            $campaignService->logClicked(
                $campaignId, $queueItem->id, $target, $trackingUrl, null, $queueItem->get('isTest')
            );
        }
    }

    protected function processWithHash(CampaignTrackingUrl $trackingUrl, string $emailAddress, string $hash) : void
    {
        $hashActual = $this->hasher->hash($emailAddress);

        if ($hashActual !== $hash) {
            throw new NotFoundSilent();
        }

        $eaRepository = $this->entityManager->getRepository('EmailAddress');

        $ea = $eaRepository->getByAddress($emailAddress);

        if (!$ea) {
            throw new NotFoundSilent();
        }

        $entityList = $eaRepository->getEntityListByAddressId($ea->id);

        foreach ($entityList as $target) {
            $this->hookManager->process('CampaignTrackingUrl', 'afterClick', $trackingUrl, [], [
                'targetId' => $target->id,
                'targetType' => $target->getEntityType(),
            ]);
        }
    }

    protected function processWithUniqueId(CampaignTrackingUrl $trackingUrl, string $uid, string $hash) : void
    {
        $hashActual = $this->hasher->hash($uid);

        if ($hashActual !== $hash) {
            throw new NotFoundSilent();
        }

        $this->hookManager->process('CampaignTrackingUrl', 'afterClick', $trackingUrl, [], [
            'uid' => $uid,
        ]);
    }

    protected function displayMessage(?string $message) : void
    {
        $data = [
            'message' => $message ?? '',
            'view' => $this->metadata->get(['clientDefs', 'Campaign', 'trackinkUrlMessageView']),
            'template' => $this->metadata->get(['clientDefs', 'Campaign', 'trackinkUrlMessageTemplate']),
        ];

        $runScript = "
            Espo.require('crm:controllers/tracking-url', function (Controller) {
                var controller = new Controller(app.baseController.params, app.getControllerInjection());
                controller.masterView = app.masterView;
                controller.doAction('displayMessage', " . json_encode($data) . ");
            });
        ";

        $this->clientManager->display($runScript);
    }
}
