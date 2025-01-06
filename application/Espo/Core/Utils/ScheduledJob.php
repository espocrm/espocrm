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

namespace Espo\Core\Utils;

use Espo\Core\Job\MetadataProvider;

use Espo\Entities\Job;
use Espo\Core\ORM\EntityManager;
use Espo\Core\Utils\DateTime as DateTimeUtil;

use Espo\ORM\Name\Attribute;
use Exception;
use RuntimeException;
use DateTime;

class ScheduledJob
{
    protected string $cronFile = 'cron.php';
    /**
     * Period to check if crontab is configured properly.
     */
    protected string $checkingCronPeriod = '25 hours';
    /** @var array<string, string> */
    protected $cronSetup = [
        'linux' => '* * * * * cd {DOCUMENT_ROOT}; {PHP-BINARY} -f {CRON-FILE} > /dev/null 2>&1',
        'windows' => '{PHP-BINARY} -f {FULL-CRON-PATH}',
        'mac' => '* * * * * cd {DOCUMENT_ROOT}; {PHP-BINARY} -f {CRON-FILE} > /dev/null 2>&1',
        'default' => '* * * * * cd {DOCUMENT_ROOT}; {PHP-BINARY} -f {CRON-FILE} > /dev/null 2>&1',
    ];

    private System $systemUtil;

    public function __construct(
        private ClassFinder $classFinder,
        private EntityManager $entityManager,
        private Language $language,
        private MetadataProvider $metadataProvider
    ) {
        $this->systemUtil = new System();
    }

    /**
     * @return string[]
     */
    public function getAvailableList(): array
    {
        $list = array_filter(
            array_merge(
                $this->metadataProvider->getNonSystemScheduledJobNameList(),
                array_keys(
                    $this->classFinder->getMap('Jobs')
                )
            ),
            function (string $item) {
                return !$this->metadataProvider->isJobSystem($item);
            }
        );

        asort($list);

        return array_values($list);
    }

    /**
     * @return array{
     *   message: string,
     *   command: string,
     * }
     */
    public function getSetupMessage(): array
    {
        $language = $this->language;

        $OS = $this->systemUtil->getOS();

        $desc = $language->translate('cronSetup', 'options', 'ScheduledJob');

        if (!is_array($desc)) {
            $desc = [];
        }

        $data = [
            'PHP-BINARY' => $this->systemUtil->getPhpBinary(),
            'CRON-FILE' => $this->cronFile,
            'DOCUMENT_ROOT' => $this->systemUtil->getRootDir(),
            'FULL-CRON-PATH' => Util::concatPath($this->systemUtil->getRootDir(), $this->cronFile),
        ];

        $message = $desc[$OS] ?? $desc['default'];

        $command = $this->cronSetup[$OS] ?? $this->cronSetup['default'];

        foreach ($data as $name => $value) {
            /** @var string $command */
            $command = str_replace('{' . $name . '}', $value ?? '', $command);
        }

        return [
            'message' => $message,
            'command' => $command,
        ];
    }

    /**
     * Check if crontab is configured properly.
     */
    public function isCronConfigured(): bool
    {
        try {
            $r1From = new DateTime('-' . $this->checkingCronPeriod);
            $r1To = new DateTime('+' . $this->checkingCronPeriod);
        } catch (Exception) {
            throw new RuntimeException();
        }

        $r2From = new DateTime('-1 hour');
        $r2To = new DateTime();

        $format = DateTimeUtil::SYSTEM_DATE_TIME_FORMAT;

        return (bool) $this->entityManager
            ->getRDBRepository(Job::ENTITY_TYPE)
            ->select([Attribute::ID])
            ->leftJoin('scheduledJob')
            ->where([
                'OR' => [
                    [
                        ['executedAt>=' => $r2From->format($format)],
                        ['executedAt<=' => $r2To->format($format)],
                    ],
                    [
                        ['executeTime>=' => $r1From->format($format)],
                        ['executeTime<='=> $r1To->format($format)],
                        'scheduledJob.job' => 'Dummy',
                    ]
                ]
            ])
            ->findOne();
    }
}
