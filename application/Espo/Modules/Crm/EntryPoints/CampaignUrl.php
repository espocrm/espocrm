<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2020 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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
 * along with EspoCRM. If not, see http://www.gnu.org/licenses/.phpppph
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

namespace Espo\Modules\Crm\EntryPoints;

use Espo\Core\Exceptions;

use Espo\Modules\Crm\Entities\EmailQueueItem;
use Espo\Modules\Crm\Entities\CampaignTrackingUrl;

class CampaignUrl extends \Espo\Core\EntryPoints\Base
{
    public static $authRequired = false;

    protected function getHookManager()
    {
        return $this->getContainer()->get('hookManager');
    }

    public function run()
    {
        $queueItemId = $_GET['queueItemId'] ?? null;
        $trackingUrlId = $_GET['id'] ?? null;
        $emailAddress = $_GET['emailAddress'] ?? null;
        $hash = $_GET['hash'] ?? null;

        if (!$trackingUrlId) throw new Exceptions\BadRequest();
        $trackingUrl = $this->getEntityManager()->getEntity('CampaignTrackingUrl', $trackingUrlId);
        if (!$trackingUrl) throw new Exceptions\NotFound();

        if ($emailAddress && $hash) {
            $this->processWithHash($trackingUrl, $emailAddress, $hash);
        } else {
            if (!$queueItemId) throw new Exceptions\BadRequest();
            $queueItem = $this->getEntityManager()->getEntity('EmailQueueItem', $queueItemId);
            if (!$queueItem) throw new Exceptions\NotFound();

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

    protected function processWithQueueItem(CampaignTrackingUrl $trackingUrl, EmailQueueItem $queueItem)
    {
        $target = null;
        $campaign = null;

        $targetType = $queueItem->get('targetType');
        $targetId = $queueItem->get('targetId');

        if ($targetType && $targetId) {
            $target = $this->getEntityManager()->getEntity($targetType, $targetId);
        }

        $campaignId = $trackingUrl->get('campaignId');
        if ($campaignId) {
            $campaign = $this->getEntityManager()->getEntity('Campaign', $campaignId);
        }

        if ($target) {
            $this->getHookManager()->process('CampaignTrackingUrl', 'afterClick', $trackingUrl, [], [
                'targetId' => $targetId,
                'targetType' => $targetType,
            ]);
        }

        if ($campaign && $target) {
            $campaignService = $this->getServiceFactory()->create('Campaign');
            $campaignService->logClicked($campaignId, $queueItem->id, $target, $trackingUrl, null, $queueItem->get('isTest'));
        }
    }

    protected function processWithHash(CampaignTrackingUrl $trackingUrl, string $emailAddress, string $hash)
    {
        $hash2 = $this->getContainer()->get('hasher')->hash($emailAddress);

        if ($hash2 !== $hash) {
            throw new Exceptions\NotFound();
        }

        $eaRepository = $this->getEntityManager()->getRepository('EmailAddress');

        $ea = $eaRepository->getByAddress($emailAddress);
        if (!$ea) {
            throw new Exceptions\NotFound();
        }

        $entityList = $eaRepository->getEntityListByAddressId($ea->id);

        foreach ($entityList as $target) {
            $this->getHookManager()->process('CampaignTrackingUrl', 'afterClick', $trackingUrl, [], [
                'targetId' => $target->id,
                'targetType' => $target->getEntityType(),
            ]);
        }
    }

    protected function displayMessage(?string $message)
    {
        $message = $message ?? '';

        $data = [
            'message' => $message,
            'view' => $this->getMetadata()->get(['clientDefs', 'Campaign', 'trackinkUrlMessageView']),
            'template' => $this->getMetadata()->get(['clientDefs', 'Campaign', 'trackinkUrlMessageTemplate']),
        ];

        $runScript = "
            Espo.require('crm:controllers/tracking-url', function (Controller) {
                var controller = new Controller(app.baseController.params, app.getControllerInjection());
                controller.masterView = app.masterView;
                controller.doAction('displayMessage', ".json_encode($data).");
            });
        ";
        $this->getClientManager()->display($runScript);
    }
}
