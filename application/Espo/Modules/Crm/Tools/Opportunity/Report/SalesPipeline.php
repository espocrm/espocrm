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
use Espo\Core\Name\Field;
use Espo\Core\Select\SelectBuilderFactory;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\Metadata;
use Espo\Modules\Crm\Entities\Opportunity;
use Espo\ORM\EntityManager;
use Espo\ORM\Query\Part\Expression;
use Espo\ORM\Query\Part\Order;
use stdClass;

class SalesPipeline
{
    public function __construct(
        private Acl $acl,
        private Config $config,
        private Metadata $metadata,
        private EntityManager $entityManager,
        private SelectBuilderFactory $selectBuilderFactory,
        private Util $util
    ) {}

    /**
     * @throws Forbidden
     */
    public function run(DateRange $range, bool $useLastStage = false, ?string $teamId = null): stdClass
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

        $lostStageList = $this->util->getLostStageList();

        $options = $this->metadata->get('entityDefs.Opportunity.fields.stage.options', []);

        $queryBuilder = $this->selectBuilderFactory
            ->create()
            ->from(Opportunity::ENTITY_TYPE)
            ->withStrictAccessControl()
            ->buildQueryBuilder();

        $stageField = 'stage';

        if ($useLastStage) {
            $stageField = 'lastStage';
        }

        $whereClause = [
            [$stageField . '!=' => $lostStageList],
            [$stageField . '!=' => null],
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

        if ($teamId) {
            $whereClause[] = [
                'teamsFilter.id' => $teamId,
            ];
        }

        $queryBuilder
            ->select([
                $stageField,
                ['SUM:amountConverted', 'amount'],
            ])
            ->order(
                Order::createByPositionInList(
                    Expression::column($stageField),
                    $options
                )
            )
            ->group($stageField)
            ->where($whereClause);

        if ($teamId) {
            $queryBuilder->join(Field::TEAMS, 'teamsFilter');
        }

        $this->util->handleDistinctReportQueryBuilder($queryBuilder, $whereClause);

        $sth = $this->entityManager
            ->getQueryExecutor()
            ->execute($queryBuilder->build());

        $rowList = $sth->fetchAll() ?: [];

        $data = [];

        foreach ($rowList as $row) {
            $stage = $row[$stageField];

            $data[$stage] = floatval($row['amount']);
        }

        $dataList = [];

        $stageList = $this->metadata->get('entityDefs.Opportunity.fields.stage.options', []);

        foreach ($stageList as $stage) {
            if (in_array($stage, $lostStageList)) {
                continue;
            }

            if (!in_array($stage, $lostStageList) && !isset($data[$stage])) {
                $data[$stage] = 0.0;
            }

            $dataList[] = [
                'stage' => $stage,
                'value' => $data[$stage],
            ];
        }

        return (object) [
            'dataList' => $dataList,
        ];
    }
}
