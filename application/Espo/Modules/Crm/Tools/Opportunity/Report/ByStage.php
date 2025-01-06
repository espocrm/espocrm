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

namespace Espo\Modules\Crm\Tools\Opportunity\Report;

use Espo\Core\Acl;
use Espo\Core\Exceptions\Forbidden;
use Espo\Core\Select\SelectBuilderFactory;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\Metadata;
use Espo\Modules\Crm\Entities\Opportunity;
use Espo\ORM\EntityManager;
use Espo\ORM\Query\Part\Expression;
use Espo\ORM\Query\Part\Order;
use stdClass;

class ByStage
{
    private Acl $acl;
    private Config $config;
    private Metadata $metadata;
    private EntityManager $entityManager;
    private SelectBuilderFactory $selectBuilderFactory;
    private Util $util;

    public function __construct(
        Acl $acl,
        Config $config,
        Metadata $metadata,
        EntityManager $entityManager,
        SelectBuilderFactory $selectBuilderFactory,
        Util $util
    ) {
        $this->acl = $acl;
        $this->config = $config;
        $this->metadata = $metadata;
        $this->entityManager = $entityManager;
        $this->selectBuilderFactory = $selectBuilderFactory;
        $this->util = $util;
    }

    /**
     * @throws Forbidden
     */
    public function run(DateRange $range): stdClass
    {
        $range = $range->withFiscalYearShift(
            $this->config->get('fiscalYearShift') ?? 0
        );

        if (!$this->acl->checkScope(Opportunity::ENTITY_TYPE, Acl\Table::ACTION_READ)) {
            throw new Forbidden();
        }

        if (!$this->acl->checkField(Opportunity::ENTITY_TYPE, 'amount')) {
            throw new Forbidden("No access to 'amount' field.");
        }

        [$from, $to] = $range->getRange();

        $options = $this->metadata->get('entityDefs.Opportunity.fields.stage.options') ?? [];

        $queryBuilder = $this->selectBuilderFactory
            ->create()
            ->from(Opportunity::ENTITY_TYPE)
            ->withStrictAccessControl()
            ->buildQueryBuilder();

        $whereClause = [
            ['stage!=' => $this->util->getLostStageList()],
            ['stage!=' => $this->util->getWonStageList()],
        ];

        if ($from && $to) {
            $whereClause[] = [
                'closeDate>=' => $from->toString(),
                'closeDate<' => $to->toString(),
            ];
        }

        if ($from && !$to) {
            $whereClause[] = [
                'closeDate>=' => $from->toString(),
            ];
        }

        if (!$from && $to) {
            $whereClause[] = [
                'closeDate<' => $to->toString(),
            ];
        }
        $queryBuilder
            ->select([
                'stage',
                ['SUM:amountConverted', 'amount'],
            ])
            ->order(
                Order::createByPositionInList(
                    Expression::column('stage'),
                    $options
                )
            )
            ->group('stage')
            ->where($whereClause);

        $stageIgnoreList = array_merge(
            $this->util->getLostStageList(),
            $this->util->getWonStageList()
        );

        $this->util->handleDistinctReportQueryBuilder($queryBuilder, $whereClause);

        $sth = $this->entityManager
            ->getQueryExecutor()
            ->execute($queryBuilder->build());

        $rowList = $sth->fetchAll() ?: [];

        $result = [];

        foreach ($rowList as $row) {
            $stage = $row['stage'];

            if (in_array($stage, $stageIgnoreList)) {
                continue;
            }

            $result[$stage] = floatval($row['amount']);
        }

        foreach ($options as $stage) {
            if (in_array($stage, $stageIgnoreList)) {
                continue;
            }

            if (array_key_exists($stage, $result)) {
                continue;
            }

            $result[$stage] = 0.0;
        }

        return (object) $result;
    }
}
