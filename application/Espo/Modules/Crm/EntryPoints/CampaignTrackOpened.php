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

use Espo\Modules\Crm\Services\Campaign as Service;

use Espo\Core\{
    Exceptions\NotFound,
    Exceptions\BadRequest,
    EntryPoint\EntryPoint,
    EntryPoint\Traits\NoAuth,
    Api\Request,
    Api\Response,
    ORM\EntityManager,
};

class CampaignTrackOpened implements EntryPoint
{
    use NoAuth;

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var Service
     */
    protected $service;

    public function __construct(EntityManager $entityManager, Service $service)
    {
        $this->entityManager = $entityManager;
        $this->service = $service;
    }

    public function run(Request $request, Response $response): void
    {
        $id = $request->getQueryParam('id');

        if (!$id) {
            throw new BadRequest();
        }

        $queueItemId = $id;

        $queueItem = $this->entityManager->getEntity('EmailQueueItem', $queueItemId);

        if (!$queueItem) {
            throw new NotFound();
        }

        $target = null;

        $targetType = $queueItem->get('targetType');
        $targetId = $queueItem->get('targetId');

        if ($targetType && $targetId) {
            $target = $this->entityManager->getEntity($targetType, $targetId);
        }

        $massEmailId = $queueItem->get('massEmailId');

        if (!$massEmailId) {
            return;
        }

        $massEmail = $this->entityManager->getEntity('MassEmail', $massEmailId);

        if (!$massEmail) {
            return;
        }

        $campaignId = $massEmail->get('campaignId');

        if (!$campaignId) {
            return;
        }

        $campaign = $this->entityManager->getEntity('Campaign', $campaignId);

        if (!$campaign) {
            return;
        }

        if (!$target) {
            return;
        }

        $this->service->logOpened($campaignId, $queueItemId, $target, null, $queueItem->get('isTest'));

        header('Content-Type: image/png');

        $img  = imagecreatetruecolor(1, 1);

        imagesavealpha($img, true);

        $color = imagecolorallocatealpha($img, 127, 127, 127, 127);

        imagefill($img, 0, 0, $color);

        imagepng($img);
        imagecolordeallocate($img, $color);
        imagedestroy($img);
    }
}
