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

namespace Espo\Classes\Cleanup;

use Espo\Core\Cleanup\Cleanup;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\DateTime as DateTimeUtil;
use Espo\Entities\WebhookEventQueueItem;
use Espo\Entities\WebhookQueueItem;
use Espo\ORM\EntityManager;
use DateTime;
use Espo\ORM\Name\Attribute;

/**
 * @noinspection PhpUnused
 */
class WebhookQueue implements Cleanup
{
    private string $cleanupWebhookQueuePeriod = '10 days';

    public function __construct(private Config $config, private EntityManager $entityManager)
    {}

    public function process(): void
    {
        $period = '-' . $this->config->get('cleanupWebhookQueuePeriod', $this->cleanupWebhookQueuePeriod);

        $datetime = new DateTime();
        $datetime->modify($period);
        $from = $datetime->format(DateTimeUtil::SYSTEM_DATE_TIME_FORMAT);

        $query1 = $this->entityManager
            ->getQueryBuilder()
            ->delete()
            ->from(WebhookQueueItem::ENTITY_TYPE)
            ->where([
                'DATE:(createdAt)<' => $from,
                'OR' => [
                    'status!=' => WebhookQueueItem::STATUS_PENDING,
                    Attribute::DELETED => true,
                ],
            ])
            ->build();

        $this->entityManager->getQueryExecutor()->execute($query1);

        $query2 = $this->entityManager
            ->getQueryBuilder()
            ->delete()
            ->from(WebhookEventQueueItem::ENTITY_TYPE)
            ->where([
                'DATE:(createdAt)<' => $from,
                'OR' => [
                    'isProcessed' => true,
                    Attribute::DELETED => true,
                ],
            ])
            ->build();

        $this->entityManager->getQueryExecutor()->execute($query2);
    }
}
