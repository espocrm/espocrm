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

namespace Espo\Core\Utils\Metadata;

use Espo\Core\{
    Utils\Util,
    Utils\Metadata,
    Utils\File\Manager as FileManager,
    Utils\Config,
    Utils\Database\Converter,
    Utils\DataCache,
};

class OrmMetadataData
{
    protected $data = null;

    protected $cacheKey = 'ormMetadata';

    protected $useCache;

    protected $metadata;

    protected $fileManager;

    protected $dataCache;

    protected $config;

    private $converter;

    public function __construct(
        Metadata $metadata,
        FileManager $fileManager,
        DataCache $dataCache,
        Config $config
    ) {
        $this->metadata = $metadata;
        $this->fileManager = $fileManager;
        $this->dataCache = $dataCache;
        $this->config = $config;

        $this->useCache = $this->config->get('useCache', false);
    }

    protected function getConverter(): Converter
    {
        if (!isset($this->converter)) {
            $this->converter = new Converter($this->metadata, $this->fileManager, $this->config);
        }

        return $this->converter;
    }

    public function getData(bool $reload = false): array
    {
        if (isset($this->data) && !$reload) {
            return $this->data;
        }

        if ($this->useCache && $this->dataCache->has($this->cacheKey) && !$reload) {
            $this->data = $this->dataCache->get($this->cacheKey);

            return $this->data;
        }

        $this->data = $this->getConverter()->process();

        if ($this->useCache) {
            $this->dataCache->store($this->cacheKey, $this->data);
        }

        return $this->data;
    }

    public function get($key = null, $default = null)
    {
        return Util::getValueByKey($this->getData(), $key, $default);
    }
}
