<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

use \Espo\Core\Utils\Util;

use \Espo\Core\Exceptions\NotFound;
use \Espo\Core\Exceptions\Forbidden;
use \Espo\Core\Exceptions\BadRequest;
use \Espo\Core\Exceptions\Error;

class Unsubscribe extends \Espo\Core\EntryPoints\Base
{
    public static $authRequired = false;

    private function getHookManager()
    {
        return $this->getContainer()->get('hookManager');
    }

    public function run()
    {
        if (empty($_GET['id'])) {
            throw new BadRequest();
        }
        $queueItemId = $_GET['id'];

        $queueItem = $this->getEntityManager()->getEntity('EmailQueueItem', $queueItemId);

        if (!$queueItem) {
            throw new NotFound();
        }

        $campaign = null;
        $target = null;

        $massEmailId = $queueItem->get('massEmailId');
        if ($massEmailId) {
            $massEmail = $this->getEntityManager()->getEntity('MassEmail', $massEmailId);
            if ($massEmail) {
                $campaignId = $massEmail->get('campaignId');
                if ($campaignId) {
                    $campaign = $this->getEntityManager()->getEntity('Campaign', $campaignId);
                }

                $targetType = $queueItem->get('targetType');
                $targetId = $queueItem->get('targetId');

                if ($targetType && $targetId) {
                    $target = $this->getEntityManager()->getEntity($targetType, $targetId);

                    if ($massEmail->get('optOutEntirely')) {
                        $emailAddress = $target->get('emailAddress');
                        if ($emailAddress) {
                            $ea = $this->getEntityManager()->getRepository('EmailAddress')->getByAddress($emailAddress);
                            if ($ea) {
                                $ea->set('optOut', true);
                                $this->getEntityManager()->saveEntity($ea);
                            }
                        }
                    }

                    $link = null;
                    $m = [
                        'Account' => 'accounts',
                        'Contact' => 'contacts',
                        'Lead' => 'leads',
                        'User' => 'users'
                    ];
                    if (!empty($m[$target->getEntityType()])) {
                        $link = $m[$target->getEntityType()];
                    }
                    if ($link) {
                        $targetListList = $massEmail->get('targetLists');

                        foreach ($targetListList as $targetList) {
                            $optedOutResult = $this->getEntityManager()->getRepository('TargetList')->updateRelation($targetList, $link, $target->id, array(
                                'optedOut' => true
                            ));
                            if ($optedOutResult) {
                                $hookData = [
                                   'link' => $link,
                                   'targetId' => $targetId,
                                   'targetType' => $targetType
                                ];
                                $this->getHookManager()->process('TargetList', 'afterOptOut', $targetList, [], $hookData);
                            }
                        }

                        $data = [
                            'actionData' => [
                                'queueItemId' => $queueItemId,
                            ],
                            'view' => $this->getMetadata()->get(['clientDefs', 'Campaign', 'unsubscribeView']),
                            'template' => $this->getMetadata()->get(['clientDefs', 'Campaign', 'unsubscribeTemplate']),
                        ];

                        $runScript = "
                            Espo.require('crm:controllers/unsubscribe', function (Controller) {
                                var controller = new Controller(app.baseController.params, app.getControllerInjection());
                                controller.masterView = app.masterView;
                                controller.doAction('unsubscribe', ".json_encode($data).");
                            });
                        ";
                        $this->getClientManager()->display($runScript);
                    }
                }
            }
        }

        if ($campaign && $target) {
            $campaignService = $this->getServiceFactory()->create('Campaign');
            $campaignService->logOptedOut($campaignId, $queueItemId, $target, $queueItem->get('emailAddress'), null, $queueItem->get('isTest'));
        }
    }
}
