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

use Espo\Core\Exceptions\NotFound;

class ScheduledJob
{
    private $container;

    private $systemUtil;

    protected $data = null;

    protected $cacheFile = 'data/cache/application/jobs.php';

    protected $cronFile = 'cron.php';

    protected $allowedMethod = 'run';

    /**
     * Period to check if crontab is configured properly
     *
     * @var string
     */
    protected $checkingCronPeriod = '25 hours';

    /**
     * @var array - path to cron job files
     */
    private $paths = array(
        'corePath' => 'application/Espo/Jobs',
        'modulePath' => 'application/Espo/Modules/{*}/Jobs',
        'customPath' => 'custom/Espo/Custom/Jobs',
    );

    protected $cronSetup = array(
        'linux' => '* * * * * cd {DOCUMENT_ROOT}; {PHP-BIN-DIR} -f {CRON-FILE} > /dev/null 2>&1',
        'windows' => '{PHP-BINARY} -f {FULL-CRON-PATH}',
        'mac' => '* * * * * cd {DOCUMENT_ROOT}; {PHP-BIN-DIR} -f {CRON-FILE} > /dev/null 2>&1',
        'default' => '* * * * * cd {DOCUMENT_ROOT}; {PHP-BIN-DIR} -f {CRON-FILE} > /dev/null 2>&1',
    );

    public function __construct(\Espo\Core\Container $container)
    {
        $this->container = $container;
        $this->systemUtil = new \Espo\Core\Utils\System();
    }

    protected function getContainer()
    {
        return $this->container;
    }

    protected function getEntityManager()
    {
        return $this->container->get('entityManager');
    }

    protected function getSystemUtil()
    {
        return $this->systemUtil;
    }

    public function getMethodName()
    {
        return $this->allowedMethod;
    }

    /**
     * Get list of all jobs
     *
     * @return array
     */
    public function getAll()
    {
        if (!isset($this->data)) {
            $this->init();
        }

        return $this->data;
    }

    /**
     * Get class name of a job by name
     *
     * @param  string $name
     * @return string
     */
    public function get($name)
    {
        return $this->getClassName($name);
    }

    public function getAvailableList()
    {
        $data = $this->getAll();

        $list = array_keys($data);

        return $list;
    }

    /**
     * Get list of all job names
     *
     * @return array
     */
    public function getAllNamesOnly()
    {
        $data = $this->getAll();

        $namesOnly = array_keys($data);

        return $namesOnly;
    }

    /**
     * Get class name of a job
     *
     * @param  string $name
     * @return string
     */
    protected function getClassName($name)
    {
        $name = Util::normilizeClassName($name);

        $data = $this->getAll();

        $name = ucfirst($name);
        if (isset($data[$name])) {
            return $data[$name];
        }

        return false;
    }

    /**
     * Load scheduler classes. It loads from ...Jobs, ex. \Espo\Jobs
     * @return null
     */
    protected function init()
    {
        $classParser = $this->getContainer()->get('classParser');
        $classParser->setAllowedMethods( array($this->allowedMethod) );
        $this->data = $classParser->getData($this->paths, $this->cacheFile);
    }

    public function getSetupMessage()
    {
        $language = $this->getContainer()->get('language');

        $OS = $this->getSystemUtil()->getOS();
        $desc = $language->translate('cronSetup', 'options', 'ScheduledJob');

        $data = array(
            'PHP-BIN-DIR' => $this->getSystemUtil()->getPhpBin(),
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

        return array(
            'message' => $message,
            'command' => $command,
        );
    }

    /**
     * Check if crontab is configured properly
     *
     * @return boolean
     */
    public function isCronConfigured()
    {
        $r1From = new \DateTime('-' . $this->checkingCronPeriod);
        $r1To = new \DateTime('+' . $this->checkingCronPeriod);

        $r2From = new \DateTime('- 1 hour');
        $r2To = new \DateTime();

        $format = \Espo\Core\Utils\DateTime::$systemDateTimeFormat;

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
                        'scheduledJob.job' => 'Dummy'
                    ]
                ]
            ]
        ];


        return !!$this->getEntityManager()->getRepository('Job')->findOne($selectParams);
    }
}
