<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

use Espo\Entities\User;
use Espo\Modules\Crm\Entities\Campaign;
use Espo\Modules\Crm\Entities\EmailQueueItem;
use Espo\Modules\Crm\Entities\MassEmail;
use Espo\Modules\Crm\Tools\Campaign\LogService;
use Espo\Core\Api\Request;
use Espo\Core\Api\Response;
use Espo\Core\EntryPoint\EntryPoint;
use Espo\Core\EntryPoint\Traits\NoAuth;
use Espo\Core\Exceptions\BadRequest;
use Espo\Core\Exceptions\NotFound;
use Espo\Core\ORM\EntityManager;

class CampaignTrackOpened implements EntryPoint
{
    use NoAuth;

    public function __construct(
        private EntityManager $entityManager,
        private LogService $service,
        private User $user,
    ) {}

    /**
     * @throws BadRequest
     * @throws NotFound
     */
    public function run(Request $request, Response $response): void
    {
        $id = $request->getQueryParam('id');

        if (!$id) {
            throw new BadRequest("No id.");
        }

        $queueItemId = $id;

        /** @var ?EmailQueueItem $queueItem */
        $queueItem = $this->entityManager->getEntityById(EmailQueueItem::ENTITY_TYPE, $queueItemId);

        if (!$queueItem) {
            throw new NotFound("Item not found.");
        }

        $targetType = $queueItem->getTargetType();
        $targetId = $queueItem->getTargetId();

        $target = $this->entityManager->getEntityById($targetType, $targetId);

        if (!$target) {
            return;
        }

        $massEmail = $queueItem->getMassEmail();

        if (!$massEmail) {
            return;
        }

        $campaign = $massEmail->getCampaign();

        if (!$campaign) {
            return;
        }

        if ($this->user->isSystem()) {
            $this->service->logOpened($campaign->getId(), $queueItem);
        }

        header('Content-Type: image/png');

        $img  = imagecreatetruecolor(1, 1);

        if (!$img) {
            return;
        }

        imagesavealpha($img, true);

        $color = imagecolorallocatealpha($img, 127, 127, 127, 127);

        if ($color === false) {
            return;
        }

        imagefill($img, 0, 0, $color);

        imagepng($img);
        imagecolordeallocate($img, $color);
        imagedestroy($img);
    }
}
