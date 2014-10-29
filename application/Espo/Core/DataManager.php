<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014  Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
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
 ************************************************************************/
namespace Espo\Core;

use Espo\Core\Utils\Config;
use Espo\Core\Utils\Database\Schema\Schema;
use Espo\Core\Utils\File\Manager;
use Espo\Core\Utils\Log;
use Espo\Core\Utils\Metadata;

class DataManager
{

    private $container;

    private $cachePath = 'data/cache';

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Rebuild the system with metadata, database and cache clearing
     *
     * @param null $entityList
     *
     * @throws Exceptions\Error
     * @return bool
     */
    public function rebuild($entityList = null)
    {
        $result = $this->clearCache();
        $result &= $this->rebuildMetadata();
        $result &= $this->rebuildDatabase($entityList);
        return $result;
    }

    /**
     * Clear a cache
     *
     * @throws Exceptions\Error
     * @return bool
     */
    public function clearCache()
    {
        /**
         * @var Manager $fileManager
         */
        $fileManager = $this->getContainer()->get('fileManager');
        $result = $fileManager->removeInDir($this->cachePath);
        if ($result != true) {
            throw new Exceptions\Error("Error while clearing cache");
        }
        $this->updateCacheTimestamp();
        return $result;
    }

    protected function getContainer()
    {
        return $this->container;
    }

    /**
     * Update cache timestamp
     *
     * @return bool
     */
    public function updateCacheTimestamp()
    {
        /**
         * @var Config $config
         */
        $config = $this->getContainer()->get('config');
        $config->updateCacheTimestamp();
        $config->save();
        return true;
    }

    /**
     * Rebuild metadata
     *
     * @return bool
     */
    public function rebuildMetadata()
    {
        /**
         * @var Metadata $metadata
         */
        $metadata = $this->getContainer()->get('metadata');
        $metadata->init(true);
        $ormMeta = $metadata->getOrmMetadata(true);
        $this->updateCacheTimestamp();
        return empty($ormMeta) ? false : true;
    }

    /**
     * Rebuild database
     *
     * @param null $entityList
     *
     * @throws Exceptions\Error
     * @return bool
     */
    public function rebuildDatabase($entityList = null)
    {
        /**
         * @var Schema $schema
         * @var Log    $log
         */
        $schema = $this->getContainer()->get('schema');
        $log = $GLOBALS['log'];
        try{
            $result = $schema->rebuild($entityList);
        } catch(\Exception $e){
            $result = false;
            $log->error('Fault to rebuild database schema' . '. Details: ' . $e->getMessage());
        }
        if ($result != true) {
            throw new Exceptions\Error("Error while rebuilding database. See log file for details.");
        }
        $this->updateCacheTimestamp();
        return $result;
    }
}

