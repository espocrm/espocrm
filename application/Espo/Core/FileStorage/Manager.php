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

namespace Espo\Core\FileStorage;

use \Espo\Entities\Attachment;

use \Espo\Core\Exceptions\Error;

class Manager
{
    private $implementations = array();

    private $implementationClassNameMap = array();

    private $container;

    public function __construct(array $implementationClassNameMap, $container)
    {
        $this->implementationClassNameMap = $implementationClassNameMap;
        $this->container = $container;
    }

    private function getImplementation($storage = null)
    {
        if (!$storage) {
            $storage = 'EspoUploadDir';
        }

        if (array_key_exists($storage, $this->implementations)) {
            return $this->implementations[$storage];
        }

        if (!array_key_exists($storage, $this->implementationClassNameMap)) {
            throw new Error("FileStorageManager: Unknown storage '{$storage}'");
        }
        $className = $this->implementationClassNameMap[$storage];

        $implementation = new $className();
        foreach ($implementation->getDependencyList() as $dependencyName) {
            $implementation->inject($dependencyName, $this->container->get($dependencyName));
        }
        $this->implementations[$storage] = $implementation;

        return $implementation;
    }

    public function isFile(Attachment $attachment)
    {
        $implementation = $this->getImplementation($attachment->get('storage'));
        return $implementation->isFile($attachment);
    }

    public function getContents(Attachment $attachment)
    {
        $implementation = $this->getImplementation($attachment->get('storage'));
        return $implementation->getContents($attachment);
    }

    public function putContents(Attachment $attachment, $contents)
    {
        $implementation = $this->getImplementation($attachment->get('storage'));
        return $implementation->putContents($attachment, $contents);
    }

    public function unlink(Attachment $attachment)
    {
        $implementation = $this->getImplementation($attachment->get('storage'));
        return $implementation->unlink($attachment);
    }

    public function getLocalFilePath(Attachment $attachment)
    {
        $implementation = $this->getImplementation($attachment->get('storage'));
        return $implementation->getLocalFilePath($attachment);
    }

    public function hasDownloadUrl(Attachment $attachment)
    {
        $implementation = $this->getImplementation($attachment->get('storage'));
        return $implementation->hasDownloadUrl($attachment);
    }

    public function getDownloadUrl(Attachment $attachment)
    {
        $implementation = $this->getImplementation($attachment->get('storage'));
        return $implementation->getDownloadUrl($attachment);
    }
}
