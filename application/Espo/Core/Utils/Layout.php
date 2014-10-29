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
namespace Espo\Core\Utils;

class Layout
{

    protected $params = array(
        'defaultsPath' => 'application/Espo/Core/defaults',
    );

    private $fileManager;

    private $metadata;

    /**
     * @var string - uses for loading default values
     */
    private $name = 'layout';

    /**
     * @var array - path to layout files
     */
    private $paths = array(
        'corePath' => 'application/Espo/Resources/layouts',
        'modulePath' => 'application/Espo/Modules/{*}/Resources/layouts',
        'customPath' => 'custom/Espo/Custom/Resources/layouts',
    );

    public function __construct(File\Manager $fileManager, Metadata $metadata)
    {
        $this->fileManager = $fileManager;
        $this->metadata = $metadata;
    }

    /**
     * Merge layout data
     * Ex. $controller= Account, $name= detail then will be created a file layoutFolder/Account/detail.json
     *
     * @param        JSON        string $data
     * @param string $controller - ex. Account
     * @param string $name       - detail
     *
     * @return bool
     */
    public function merge($data, $controller, $name)
    {
        $prevData = $this->get($controller, $name);
        $prevDataArray = Json::getArrayData($prevData);
        $dataArray = Json::getArrayData($data);
        $data = Util::merge($prevDataArray, $dataArray);
        $data = Json::encode($data);
        return $this->set($data, $controller, $name);
    }

    /**
     * Get Layout context
     *
     * @param $controller
     * @param $name
     *
     * @return json
     */
    public function get($controller, $name)
    {
        $fileFullPath = Util::concatPath($this->getLayoutPath($controller, true), $name . '.json');
        if (!file_exists($fileFullPath)) {
            $fileFullPath = Util::concatPath($this->getLayoutPath($controller), $name . '.json');
        }
        if (!file_exists($fileFullPath)) {
            //load defaults
            $defaultPath = $this->params['defaultsPath'];
            $fileFullPath = Util::concatPath(Util::concatPath($defaultPath, $this->name), $name . '.json');
            //END: load defaults
            if (!file_exists($fileFullPath)) {
                return false;
            }
        }
        return $this->getFileManager()->getContents($fileFullPath);
    }

    /**
     * Get Layout path, ex. application/Modules/Crm/Layouts/Account
     *
     * @param string $entityName
     * @param bool   $isCustom - if need to check custom folder
     *
     * @return string
     */
    protected function getLayoutPath($entityName, $isCustom = false)
    {
        $path = $this->paths['customPath'];
        if (!$isCustom) {
            $moduleName = $this->getMetadata()->getScopeModuleName($entityName);
            $path = $this->paths['corePath'];
            if ($moduleName !== false) {
                $path = str_replace('{*}', $moduleName, $this->paths['modulePath']);
            }
        }
        $path = Util::concatPath($path, $entityName);
        return $path;
    }

    protected function getMetadata()
    {
        return $this->metadata;
    }

    protected function getFileManager()
    {
        return $this->fileManager;
    }

    /**
     * Set Layout data
     * Ex. $controller= Account, $name= detail then will be created a file layoutFolder/Account/detail.json
     *
     * @param        JSON        string $data
     * @param string $controller - ex. Account
     * @param string $name       - detail
     *
     * @return bool
     */
    public function set($data, $controller, $name)
    {
        if (empty($controller) || empty($name)) {
            return false;
        }
        $layoutPath = $this->getLayoutPath($controller, true);
        if (!Json::isJSON($data)) {
            $data = Json::encode($data);
        }
        return $this->getFileManager()->putContents(array($layoutPath, $name . '.json'), $data);
    }
}

?>