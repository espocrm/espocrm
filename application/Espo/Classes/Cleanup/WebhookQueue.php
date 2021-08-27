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

namespace Espo\Classes\Cleanup;

use Espo\Core\Cleanup\Cleanup;
use Espo\Core\Utils\Config;
use Espo\ORM\EntityManager;

use DateTime;

class WebhookQueue implements Cleanup
{
    private $cleanupWebhookQueuePeriod = '10 days';

    private $config;

    private $entityManager;

    public function __construct(Config $config, EntityManager $entityManager)
    {
        $this->config = $config;
        $this->entityManager = $entityManager;
    }

    public function process(): void
    {
        $period = '-' . $this->config->get('cleanupWebhookQueuePeriod', $this->cleanupWebhookQueuePeriod);

        $datetime = new DateTime();

        $datetime->modify($period);
        $from = $datetime->format('Y-m-d H:i:s');

        $query1 = $this->entityManager
            ->getQueryBuilder()
            ->delete()
            ->from('WebhookQueueItem')
            ->where([
                'DATE:(createdAt)<' => $from,
                'OR' => [
                    'status!=' => 'Pending',
                    'deleted' => true,
                ],
            ])
            ->build();

        $this->entityManager->getQueryExecutor()->execute($query1);

        $query2 = $this->entityManager
            ->getQueryBuilder()
            ->delete()
            ->from('WebhookEventQueueItem')
            ->where([
                'DATE:(createdAt)<' => $from,
                'OR' => [
                    'isProcessed' => true,
                    'deleted' => true,
                ],
            ])
            ->build();

        $this->entityManager->getQueryExecutor()->execute($query2);
    }
}
