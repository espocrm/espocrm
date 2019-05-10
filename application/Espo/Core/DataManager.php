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

namespace Espo\Core;

class DataManager
{
    private $container;

    private $cachePath = 'data/cache';


    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    protected function getContainer()
    {
        return $this->container;
    }

    /**
     * Rebuild the system with metadata, database and cache clearing
     *
     * @return bool
     */
    public function rebuild($entityList = null)
    {
        $this->populateConfigParameters();

        $result = $this->clearCache();

        $result &= $this->rebuildMetadata();

        $result &= $this->rebuildDatabase($entityList);

        $this->rebuildScheduledJobs();

        return $result;
    }

    /**
     * Clear a cache
     *
     * @return bool
     */
    public function clearCache()
    {
        $result = $this->getContainer()->get('fileManager')->removeInDir($this->cachePath);

        if ($result != true) {
            throw new Exceptions\Error("Error while clearing cache");
        }

        $this->updateCacheTimestamp();

        return $result;
    }

    /**
     * Rebuild database
     *
     * @return bool
     */
    public function rebuildDatabase($entityList = null)
    {
        try {
            $result = $this->getContainer()->get('schema')->rebuild($entityList);
        } catch (\Exception $e) {
            $result = false;
            $GLOBALS['log']->error('Fault to rebuild database schema'.'. Details: '.$e->getMessage());
        }

        if ($result != true) {
            throw new Exceptions\Error("Error while rebuilding database. See log file for details.");
        }

        $this->updateCacheTimestamp();

        return $result;
    }

    /**
     * Rebuild metadata
     *
     * @return bool
     */
    public function rebuildMetadata()
    {
        $metadata = $this->getContainer()->get('metadata');

        $metadata->init(true);

        $ormData = $this->getContainer()->get('ormMetadata')->getData(true);
        $this->getContainer()->get('entityManager')->setMetadata($ormData);

        $this->updateCacheTimestamp();

        return empty($ormData) ? false : true;
    }

    public function rebuildScheduledJobs()
    {
        $metadata = $this->getContainer()->get('metadata');
        $entityManager = $this->getContainer()->get('entityManager');

        $jobs = $metadata->get(['entityDefs', 'ScheduledJob', 'jobs'], []);

        $systemJobNameList = [];

        foreach ($jobs as $jobName => $defs) {
            if ($jobName && !empty($defs['isSystem']) && !empty($defs['scheduling'])) {
                $systemJobNameList[] = $jobName;
                if (!$entityManager->getRepository('ScheduledJob')->where(array(
                    'job' => $jobName,
                    'status' => 'Active',
                    'scheduling' => $defs['scheduling']
                ))->findOne()) {
                    $job = $entityManager->getRepository('ScheduledJob')->where([
                        'job' => $jobName
                    ])->findOne();
                    if ($job) {
                        $entityManager->removeEntity($job);
                    }
                    $name = $jobName;
                    if (!empty($defs['name'])) {
                        $name = $defs['name'];
                    }
                    $job = $entityManager->getEntity('ScheduledJob');
                    $job->set([
                        'job' => $jobName,
                        'status' => 'Active',
                        'scheduling' => $defs['scheduling'],
                        'isInternal' => true,
                        'name' => $name
                    ]);
                    $entityManager->saveEntity($job);
                }
            }
        }

        $internalScheduledJobList = $entityManager->getRepository('ScheduledJob')->where([
            'isInternal' => true
        ])->find();
        foreach ($internalScheduledJobList as $scheduledJob) {
            $jobName = $scheduledJob->get('job');
            if (!in_array($jobName, $systemJobNameList)) {
                $entityManager->getRepository('ScheduledJob')->deleteFromDb($scheduledJob->id);
            }
        }
    }

    public function updateCacheTimestamp()
    {
        $this->getContainer()->get('config')->updateCacheTimestamp();
        $this->getContainer()->get('config')->save();

        return true;
    }

    protected function populateConfigParameters()
    {
        $config = $this->getContainer()->get('config');

        $pdo = $this->getContainer()->get('entityManager')->getPDO();
        $query = "SHOW VARIABLES LIKE 'ft_min_word_len'";
        $sth = $pdo->prepare($query);
        $sth->execute();

        $fullTextSearchMinLength = null;
        if ($row = $sth->fetch(\PDO::FETCH_ASSOC)) {
            if (isset($row['Value'])) {
                $fullTextSearchMinLength = intval($row['Value']);
            }
        }

        $config->set('fullTextSearchMinLength', $fullTextSearchMinLength);

        $cryptKey = $config->get('cryptKey');
        if (!$cryptKey) {
            $cryptKey = \Espo\Core\Utils\Util::generateKey();
            $config->set('cryptKey', $cryptKey);
        }

        $config->save();
    }
}
