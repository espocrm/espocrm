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
namespace Espo\Core\Utils\Database;

use Espo\Core\Utils\File\Manager;
use Espo\Core\Utils\Log;
use Espo\Core\Utils\Metadata;

class Converter
{

    private $metadata;

    private $fileManager;

    private $schemaConverter;

    private $schemaFromMetadata = null;

    /**
     * @param Metadata $metadata
     * @param Manager  $fileManager
     *
     * @internal param array $meta - metadata array
     */
    //private $meta;
    public function __construct(Metadata $metadata, Manager $fileManager)
    {
        $this->metadata = $metadata;
        $this->fileManager = $fileManager;
        $this->ormConverter = new Orm\Converter($this->metadata, $this->fileManager);
        $this->schemaConverter = new Schema\Converter($this->fileManager);
    }

    public function getSchemaFromMetadata($entityList = null)
    {
        $ormMeta = $this->getMetadata()->getOrmMetadata();
        $entityDefs = $this->getMetadata()->get('entityDefs');
        $this->schemaFromMetadata = $this->getSchemaConverter()->process($ormMeta, $entityDefs, $entityList);
        return $this->schemaFromMetadata;
    }

    protected function getMetadata()
    {
        return $this->metadata;
    }

    protected function getSchemaConverter()
    {
        return $this->schemaConverter;
    }

    /**
     * Main method of convertation from metadata to orm metadata and database schema
     *
     * @return bool
     */
    public function process()
    {
        /**
         * @var Log $log
         */
        $log = $GLOBALS['log'];
        $log->debug('Orm\Converter - Start: orm convertation');
        $ormMeta = $this->getOrmConverter()->process();
        //save database meta to a file espoMetadata.php
        $result = $this->getMetadata()->setOrmMetadata($ormMeta);
        $log->debug('Orm\Converter - End: orm convertation, result=[' . $result . ']');
        return $result;
    }

    protected function getOrmConverter()
    {
        return $this->ormConverter;
    }
}

?>