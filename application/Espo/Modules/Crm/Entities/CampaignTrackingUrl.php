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

namespace Espo\Modules\Crm\Entities;

use Espo\Core\ORM\Entity;
use LogicException;

class CampaignTrackingUrl extends Entity
{
    public const ENTITY_TYPE = 'CampaignTrackingUrl';

    public const ACTION_SHOW_MESSAGE = 'Show Message';

    public function get(string $attribute): mixed
    {
        if ($attribute === 'urlToUse') {
            return $this->getUrlToUseInternal();
        }

        return parent::get($attribute);
    }

    public function has(string $attribute): bool
    {
        if ($attribute === 'urlToUse') {
            return $this->hasUrlToUseInternal();
        }

        return parent::has($attribute);
    }

    public function getCampaignId(): ?string
    {
        return $this->get('campaignId');
    }

    public function getAction(): ?string
    {
        return $this->get('action');
    }

    public function getMessage(): ?string
    {
        return $this->get('message');
    }

    public function getUrl(): ?string
    {
        return $this->get('url');
    }

    public function getUrlToUse(): string
    {
        if (!$this->id) {
            throw new LogicException();
        }

        return $this->get('urlToUse');
    }

    private function getUrlToUseInternal(): string
    {
        return "{trackingUrl:$this->id}";
    }

    private function hasUrlToUseInternal(): bool
    {
        return !$this->isNew();
    }

    public function getCampaign(): ?Campaign
    {
        /** @var ?Campaign */
        return $this->relations->getOne('campaign');
    }
}
