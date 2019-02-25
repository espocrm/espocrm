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

class CampaignTrackOpened extends \Espo\Core\EntryPoints\Base
{
    public static $authRequired = false;

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

        $target = null;
        $campaign = null;

        $targetType = $queueItem->get('targetType');
        $targetId = $queueItem->get('targetId');

        if ($targetType && $targetId) {
            $target = $this->getEntityManager()->getEntity($targetType, $targetId);
        }

        $massEmailId = $queueItem->get('massEmailId');
        if (!$massEmailId) return;
        $massEmail = $this->getEntityManager()->getEntity('MassEmail', $massEmailId);
        if (!$massEmail) return;

        $campaignId = $massEmail->get('campaignId');
        if (!$campaignId) return;

        $campaign = $this->getEntityManager()->getEntity('Campaign', $campaignId);
        if (!$campaign) return;


        if (!$target) {
            return;
        }
        $campaignService = $this->getServiceFactory()->create('Campaign');
        $campaignService->logOpened($campaignId, $queueItemId, $target, null, $queueItem->get('isTest'));

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

