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
namespace Espo\Core\Utils\Database\Schema;

use Doctrine\DBAL\Schema\Schema;
use Espo\Core\ORM\EntityManager;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\Metadata;

abstract class BaseRebuildActions
{

    protected $currentSchema = null;

    protected $metadataSchema = null;

    private $metadata;

    private $config;

    private $entityManager;

    public function __construct(
        Metadata $metadata,
        Config $config,
        EntityManager $entityManager
    ){
        $this->metadata = $metadata;
        $this->config = $config;
        $this->entityManager = $entityManager;
    }

    protected function getEntityManager()
    {
        return $this->entityManager;
    }

    protected function getConfig()
    {
        return $this->config;
    }

    protected function getMetadata()
    {
        return $this->metadata;
    }

    protected function getCurrentSchema()
    {
        return $this->currentSchema;
    }

    public function setCurrentSchema(Schema $currentSchema)
    {
        $this->currentSchema = $currentSchema;
    }

    protected function getMetadataSchema()
    {
        return $this->metadataSchema;
    }

    public function setMetadataSchema(Schema $metadataSchema)
    {
        $this->metadataSchema = $metadataSchema;
    }
    /*
    public function beforeRebuild()
    {         
    }

    public function afterRebuild()
    {         
    }
    */
}

