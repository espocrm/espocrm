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

namespace Espo\Core;

use Espo\Core\{
    Exceptions\Error,
    ORM\EntityManager,
    Utils\Metadata,
    Utils\Util,
    Utils\Config,
    Utils\Config\ConfigWriter,
    Utils\File\Manager as FileManager,
    Utils\Metadata\OrmMetadataData,
    HookManager,
    Utils\Database\Schema\SchemaProxy,
};

use PDO;
use Throwable;

/**
 * Clears cache, rebuilds the application.
 */
class DataManager
{
    protected $config;
    protected $configWriter;
    protected $entityManager;
    protected $fileManager;
    protected $metadata;
    protected $ormMetadataData;
    protected $hookManager;
    protected $schemaProxy;

    private $cachePath = 'data/cache';

    public function __construct(
        EntityManager $entityManager,
        Config $config,
        ConfigWriter $configWriter,
        FileManager $fileManager,
        Metadata $metadata,
        OrmMetadataData $ormMetadataData,
        HookManager $hookManager,
        SchemaProxy $schemaProxy
    ) {
        $this->entityManager = $entityManager;
        $this->config = $config;
        $this->configWriter = $configWriter;
        $this->fileManager = $fileManager;
        $this->metadata = $metadata;
        $this->ormMetadataData = $ormMetadataData;
        $this->hookManager = $hookManager;
        $this->schemaProxy = $schemaProxy;
    }

    /**
     * Rebuild the system with metadata, database and cache clearing.
     */
    public function rebuild(?array $entityList = null)
    {
        $this->clearCache();

        $this->disableHooks();

        $this->populateConfigParameters();

        $this->rebuildMetadata();

        $this->rebuildDatabase($entityList);

        $this->rebuildScheduledJobs();

        $this->enableHooks();
    }

    /**
     * Clear a cache.
     */
    public function clearCache()
    {
        $result = $this->fileManager->removeInDir($this->cachePath);

        if ($result != true) {
            throw new Error("Error while clearing cache");
        }

        $this->updateCacheTimestamp();
    }

    /**
     * Rebuild database.
     */
    public function rebuildDatabase(?array $entityList = null)
    {
        $schema = $this->schemaProxy;

        try {
            $result = $schema->rebuild($entityList);
        }
        catch (Throwable $e) {
            $result = false;

            $GLOBALS['log']->error(
                "Fault to rebuild database schema. Details: ". $e->getMessage() .
                " at " . $e->getFile() . ":" . $e->getLine()
            );
        }

        if ($result != true) {
            throw new Error("Error while rebuilding database. See log file for details.");
        }

        $databaseType = strtolower($schema->getDatabaseHelper()->getDatabaseType());

        if (
            !$this->config->get('actualDatabaseType') ||
            $this->config->get('actualDatabaseType') !== $databaseType
        ) {
            $this->configWriter->set('actualDatabaseType', $databaseType);
        }

        $databaseVersion = $schema->getDatabaseHelper()->getDatabaseVersion();

        if (
            !$this->config->get('actualDatabaseVersion') ||
            $this->config->get('actualDatabaseVersion') !== $databaseVersion
        ) {
            $this->configWriter->set('actualDatabaseVersion', $databaseVersion);
        }

        $this->configWriter->updateCacheTimestamp();

        $this->configWriter->save();
    }

    /**
     * Rebuild metadata.
     */
    public function rebuildMetadata()
    {
        $metadata = $this->metadata;

        $metadata->init(true);

        $ormData = $this->ormMetadataData->getData(true);

        $this->entityManager->getMetadata()->updateData();

        $this->updateCacheTimestamp();

        if (empty($ormData)) {
            throw new Error("Error while rebuilding metadata. See log file for details.");
        }
    }

    /**
     * Rebuild scheduled jobs. Create system jobs.
     */
    public function rebuildScheduledJobs()
    {
        $metadata = $this->metadata;

        $entityManager = $this->entityManager;

        $jobs = $metadata->get(['entityDefs', 'ScheduledJob', 'jobs'], []);

        $systemJobNameList = [];

        foreach ($jobs as $jobName => $defs) {
            if (!$jobName) {
                continue;
            }

            if (empty($defs['isSystem']) || empty($defs['scheduling'])) {
                continue;
            }

            $systemJobNameList[] = $jobName;

            $sj = $entityManager
                ->getRepository('ScheduledJob')
                ->where([
                    'job' => $jobName,
                    'status' => 'Active',
                    'scheduling' => $defs['scheduling'],
                ])
                ->findOne();

            if ($sj) {
                continue;
            }

            $job = $entityManager
                ->getRepository('ScheduledJob')
                ->where([
                    'job' => $jobName,
                ])
                ->findOne();

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
                'name' => $name,
            ]);

            $entityManager->saveEntity($job);
        }

        $internalScheduledJobList = $entityManager
            ->getRepository('ScheduledJob')
            ->where([
                'isInternal' => true,
            ])
            ->find();

        foreach ($internalScheduledJobList as $scheduledJob) {
            $jobName = $scheduledJob->get('job');

            if (!in_array($jobName, $systemJobNameList)) {
                $entityManager->getRepository('ScheduledJob')->deleteFromDb($scheduledJob->id);
            }
        }
    }

    /**
     * Update cache timestamp.
     */
    public function updateCacheTimestamp()
    {
        $this->configWriter->updateCacheTimestamp();

        $this->configWriter->save();
    }

    protected function populateConfigParameters()
    {
        $this->setFullTextConfigParameters();
        $this->setCryptKeyConfigParameter();

        $this->configWriter->save();
    }

    protected function setFullTextConfigParameters()
    {
        $platform = $this->config->get('database.platform') ?? null;
        $driver = $this->config->get('database.driver') ?? '';

        if ($platform !== 'Mysql' && strpos($driver, 'mysql') === false) {
            return;
        }

        $pdo = $this->entityManager->getPDO();

        $sql = "SHOW VARIABLES LIKE 'ft_min_word_len'";

        $sth = $pdo->prepare($sql);

        $sth->execute();

        $fullTextSearchMinLength = null;

        $row = $sth->fetch(PDO::FETCH_ASSOC);

        if ($row && isset($row['Value'])) {
            $fullTextSearchMinLength = intval($row['Value']);
        }

        $this->configWriter->set('fullTextSearchMinLength', $fullTextSearchMinLength);
    }

    protected function setCryptKeyConfigParameter()
    {
        if ($this->config->get('cryptKey')) {
            return;
        }

        $cryptKey = Util::generateSecretKey();

        $this->configWriter->set('cryptKey', $cryptKey);
    }

    protected function disableHooks()
    {
        $this->hookManager->disable();
    }

    protected function enableHooks()
    {
        $this->hookManager->enable();
    }
}
