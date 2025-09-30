<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

namespace Espo\Modules\Crm\Tools\MassEmail;

use Espo\Core\Utils\Config;
use Espo\Modules\Crm\Entities\Campaign;
use Espo\Modules\Crm\Tools\MassEmail\MessagePreparator\Data;
use Espo\Modules\Crm\Tools\MassEmail\MessagePreparator\Headers;

class DefaultMessageHeadersPreparator implements MessageHeadersPreparator
{
    public function __construct(
        private Config $config,
        private Config\ApplicationConfig $applicationConfig,
    ) {}

    public function prepare(Headers $headers, Data $data): void
    {
        $headers->addTextHeader('X-Queue-Item-Id', $data->getId());
        $headers->addTextHeader('Precedence', 'bulk');

        $campaignType = $this->getCampaignType($data);

        if (
            $campaignType === Campaign::TYPE_INFORMATIONAL_EMAIL ||
            $campaignType === Campaign::TYPE_NEWSLETTER
        ) {
            $headers->addTextHeader('Auto-Submitted', 'auto-generated');
            $headers->addTextHeader('X-Auto-Response-Suppress', 'AutoReply');
        }

        $this->addMandatoryOptOut($headers, $data);
    }

    private function getSiteUrl(): string
    {
        $url = $this->config->get('massEmailSiteUrl') ?? $this->applicationConfig->getSiteUrl();

        return rtrim($url, '/');
    }

    private function addMandatoryOptOut(Headers $headers, Data $data): void
    {
        if ($this->getCampaignType($data) === Campaign::TYPE_INFORMATIONAL_EMAIL) {
            return;
        }

        if ($this->config->get('massEmailDisableMandatoryOptOutLink')) {
            return;
        }

        $id = $data->getId();

        $url = "{$this->getSiteUrl()}/api/v1/Campaign/unsubscribe/$id";

        $headers->addTextHeader('List-Unsubscribe-Post', 'List-Unsubscribe=One-Click');
        $headers->addTextHeader('List-Unsubscribe', "<$url>");
    }

    private function getCampaignType(Data $data): ?string
    {
        return $data->getQueueItem()->getMassEmail()?->getCampaign()?->getType();
    }
}
