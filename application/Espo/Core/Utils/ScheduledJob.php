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

namespace Espo\Core\Utils;

use Espo\Core\{
    Utils\ClassFinder,
    Utils\Language,
    Utils\System,
    Utils\DateTime as DateTimeUtil,
    ORM\EntityManager,
};

use DateTime;

class ScheduledJob
{
    private $systemUtil;

    protected $cronFile = 'cron.php';

    /**
     * Period to check if crontab is configured properly
     *
     * @var string
     */
    protected $checkingCronPeriod = '25 hours';

    protected $cronSetup = [
        'linux' => '* * * * * cd {DOCUMENT_ROOT}; {PHP-BINARY} -f {CRON-FILE} > /dev/null 2>&1',
        'windows' => '{PHP-BINARY} -f {FULL-CRON-PATH}',
        'mac' => '* * * * * cd {DOCUMENT_ROOT}; {PHP-BINARY} -f {CRON-FILE} > /dev/null 2>&1',
        'default' => '* * * * * cd {DOCUMENT_ROOT}; {PHP-BINARY} -f {CRON-FILE} > /dev/null 2>&1',
    ];

    protected $classFinder;

    protected $language;

    protected $entityManager;

    public function __construct(ClassFinder $classFinder, EntityManager $entityManager, Language $language)
    {
        $this->classFinder = $classFinder;
        $this->entityManager = $entityManager;
        $this->language = $language;

        $this->systemUtil = new System();
    }

    protected function getContainer()
    {
        return $this->container;
    }

    protected function getSystemUtil()
    {
        return $this->systemUtil;
    }

    public function getAvailableList()
    {
        $map = $this->classFinder->getMap('Jobs');
        $list = array_keys($map);
        return $list;
    }

    public function getJobClassName(string $name) : ?string
    {
        $name = ucfirst($name);

        $className = $this->classFinder->find('Jobs', $name);

        return $className;
    }

    public function getSetupMessage()
    {
        $language = $this->language;

        $OS = $this->getSystemUtil()->getOS();
        $desc = $language->translate('cronSetup', 'options', 'ScheduledJob');

        $data = array(
            'PHP-BINARY' => $this->getSystemUtil()->getPhpBinary(),
            'CRON-FILE' => $this->cronFile,
            'DOCUMENT_ROOT' => $this->getSystemUtil()->getRootDir(),
            'FULL-CRON-PATH' => Util::concatPath($this->getSystemUtil()->getRootDir(), $this->cronFile),
        );

        $message = isset($desc[$OS]) ? $desc[$OS] : $desc['default'];

        $command = isset($this->cronSetup[$OS]) ? $this->cronSetup[$OS] : $this->cronSetup['default'];

        foreach ($data as $name => $value) {
            $command = str_replace('{'.$name.'}', $value, $command);
        }

        return [
            'message' => $message,
            'command' => $command,
        ];
    }

    /**
     * Check if crontab is configured properly.
     *
     * @return boolean
     */
    public function isCronConfigured()
    {
        $r1From = new DateTime('-' . $this->checkingCronPeriod);
        $r1To = new DateTime('+' . $this->checkingCronPeriod);

        $r2From = new DateTime('- 1 hour');
        $r2To = new DateTime();

        $format = DateTimeUtil::$systemDateTimeFormat;

        $selectParams = [
            'select' => ['id'],
            'leftJoins' => ['scheduledJob'],
            'whereClause' => [
                'OR' => [
                    [
                        ['executedAt>=' => $r2From->format($format)] ,
                        ['executedAt<=' => $r2To->format($format)],
                    ],
                    [
                        ['executeTime>=' => $r1From->format($format)],
                        ['executeTime<='=> $r1To->format($format)],
                        'scheduledJob.job' => 'Dummy',
                    ]
                ]
            ]
        ];

        return (bool) $this->entityManager->getRepository('Job')->findOne($selectParams);
    }
}
