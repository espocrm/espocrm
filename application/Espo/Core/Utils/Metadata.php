<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2015 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

use Espo\Core\Exceptions\Error;

class Metadata
{
    protected $meta = null;

    protected $scopes = array();

    private $config;
    private $unifier;
    private $fileManager;
    private $converter;
    private $moduleConfig;

    /**
     * @var string - uses for loading default values
     */
    private $name = 'metadata';

    private $cacheFile = 'data/cache/application/metadata.php';

    private $paths = array(
        'corePath' => 'application/Espo/Resources/metadata',
        'modulePath' => 'application/Espo/Modules/{*}/Resources/metadata',
        'customPath' => 'custom/Espo/Custom/Resources/metadata',
    );

    protected $ormMeta = null;

    private $ormCacheFile = 'data/cache/application/ormMetadata.php';

    private $moduleList = null;

    /**
     * Default module order
     * @var integer
     */
    protected $defaultModuleOrder = 10;

    private $deletedData = array();

    private $changedData = array();

    public function __construct(\Espo\Core\Utils\Config $config, \Espo\Core\Utils\File\Manager $fileManager)
    {
        $this->config = $config;
        $this->fileManager = $fileManager;

        $this->unifier = new \Espo\Core\Utils\File\Unifier($this->fileManager);

        $this->converter = new \Espo\Core\Utils\Database\Converter($this, $this->fileManager);

        $this->moduleConfig = new \Espo\Core\Utils\Module($this->config, $this->fileManager);
    }

    protected function getConfig()
    {
        return $this->config;
    }

    protected function getUnifier()
    {
        return $this->unifier;
    }

    protected function getFileManager()
    {
        return $this->fileManager;
    }

    protected function getConverter()
    {
        return $this->converter;
    }

    protected function getModuleConfig()
    {
        return $this->moduleConfig;
    }

    public function isCached()
    {
        if (!$this->getConfig()->get('useCache')) {
            return false;
        }

        if (file_exists($this->cacheFile)) {
            return true;
        }

        return false;
    }

    /**
     * Init metadata
     *
     * @param  boolean $reload
     * @return void
     */
    public function init($reload = false)
    {
        if (!$this->getConfig()->get('useCache')) {
            $reload = true;
        }

        if (file_exists($this->cacheFile) && !$reload) {
            $this->meta = $this->getFileManager()->getPhpContents($this->cacheFile);
        } else {
            $this->meta = $this->getUnifier()->unify($this->name, $this->paths, true);
            $this->meta = $this->setLanguageFromConfig($this->meta);

            if ($this->getConfig()->get('useCache')) {
                $isSaved = $this->getFileManager()->putPhpContents($this->cacheFile, $this->meta);
                if ($isSaved === false) {
                    $GLOBALS['log']->emergency('Metadata:init() - metadata has not been saved to a cache file');
                }
            }
        }
    }

    /**
     * Get metadata array
     *
     * @return array
     */
    protected function getData()
    {
        if (empty($this->meta) || !is_array($this->meta)) {
            $this->init();
        }

        return $this->meta;
    }

    /**
    * Get Metadata
    *
    * @param string $key
    * @param mixed $default
    *
    * @return array
    */
    public function get($key = null, $default = null)
    {
        return Util::getValueByKey($this->getData(), $key, $default);
    }

    /**
    * Get All Metadata context
    *
    * @param $isJSON
    * @param bool $reload
    *
    * @return json | array
    */
    public function getAll($isJSON = false, $reload = false)
    {
        if ($reload) {
            $this->init(true);
        }

        if ($isJSON) {
            return Json::encode($this->meta);
        }
        return $this->meta;
    }

    /**
     * Set language list and default for Settings, Preferences metadata
     *
     * @param array $data Meta
     * @return array $data
     */
    protected function setLanguageFromConfig($data)
    {
        $entityList = array(
            'Settings',
            'Preferences',
        );

        $languageList = $this->getConfig()->get('languageList');
        $language = $this->getConfig()->get('language');

        foreach ($entityList as $entityName) {
            if (isset($data['entityDefs'][$entityName]['fields']['language'])) {
                $data['entityDefs'][$entityName]['fields']['language']['options'] = $languageList;
                $data['entityDefs'][$entityName]['fields']['language']['default'] = $language;
            }
        }

        return $data;
    }

    /**
    * Set Metadata data
    * Ex. $key1 = menu, $key2 = Account then will be created a file metadataFolder/menu/Account.json
    *
    * @param  string $key1
    * @param  string $key2
    * @param JSON string $data
    *
    * @return bool
    */
    public function set($key1, $key2, $data)
    {
        $newData = array(
            $key1 => array(
                $key2 => $data,
            ),
        );

        $this->changedData = Util::merge($this->changedData, $newData);
        $this->meta = Util::merge($this->getData(), $newData);

        $this->undelete($key1, $key2, $data);
    }

    /**
     * Unset some fields and other stuff in metadat
     *
     * @param  string $key1
     * @param  string $key2
     * @param  array | string $unsets Ex. 'fields.name'
     *
     * @return bool
     */
    public function delete($key1, $key2, $unsets)
    {
        if (!is_array($unsets)) {
            $unsets = (array) $unsets;
        }

        $normalizedData = array(
            '__APPEND__',
        );
        $metaUnsetData = array();
        foreach ($unsets as $unsetItem) {
            $normalizedData[] = $unsetItem;
            $metaUnsetData[] = implode('.', array($key1, $key2, $unsetItem));
        }

        $unsetData = array(
            $key1 => array(
                $key2 => $normalizedData,
            ),
        );

        $this->deletedData = Util::merge($this->deletedData, $unsetData);
        $this->deletedData = Util::unsetInArrayByValue('__APPEND__', $this->deletedData);

        $this->meta = Util::unsetInArray($this->getData(), $metaUnsetData);
    }

    /**
     * Undelete the deleted items
     *
     * @param  string $key1
     * @param  string $key2
     * @param  array $data
     * @return void
     */
    protected function undelete($key1, $key2, $data)
    {
        if (isset($this->deletedData[$key1][$key2])) {
            foreach ($this->deletedData[$key1][$key2] as $unsetIndex => $unsetItem) {
                $value = Util::getValueByKey($data, $unsetItem);
                if (isset($value)) {
                    unset($this->deletedData[$key1][$key2][$unsetIndex]);
                }
            }
        }
    }

    /**
     * Clear unsaved changes
     *
     * @return void
     */
    public function clearChanges()
    {
        $this->changedData = array();
        $this->deletedData = array();
        $this->init(true);
    }

    /**
     * Save changes
     *
     * @return bool
     */
    public function save()
    {
        $path = $this->paths['customPath'];

        $result = true;
        if (!empty($this->changedData)) {
            foreach ($this->changedData as $key1 => $keyData) {
                foreach ($keyData as $key2 => $data) {
                    if (!empty($data)) {
                        $result &= $this->getFileManager()->mergeContents(array($path, $key1, $key2.'.json'), $data, true);
                    }
                }
            }
        }

        if (!empty($this->deletedData)) {
            foreach ($this->deletedData as $key1 => $keyData) {
                foreach ($keyData as $key2 => $unsetData) {
                    if (!empty($unsetData)) {
                        $rowResult = $this->getFileManager()->unsetContents(array($path, $key1, $key2.'.json'), $unsetData, true);
                        if ($rowResult == false) {
                            $GLOBALS['log']->warning('Metadata items ['.$key1.'.'.$key2.'] can be deleted for custom code only.');
                        }
                        $result &= $rowResult;
                    }
                }
            }
        }

        if ($result == false) {
            throw new Error("Error saving metadata. See log file for details.");
        }

        $this->clearChanges();

        return (bool) $result;
    }

    public function getOrmMetadata($reload = false)
    {
        if (!empty($this->ormMeta) && is_array($this->ormMeta) && !$reload) {
            return $this->ormMeta;
        }

        if (!file_exists($this->ormCacheFile) || !$this->getConfig()->get('useCache') || $reload) {
            $this->getConverter()->process();
        }

        if (empty($this->ormMeta)) {
            $this->ormMeta = $this->getFileManager()->getPhpContents($this->ormCacheFile);
        }

        return $this->ormMeta;
    }

    public function setOrmMetadata(array $ormMeta)
    {
        $result = true;

        if ($this->getConfig()->get('useCache')) {
            $result = $this->getFileManager()->putPhpContents($this->ormCacheFile, $ormMeta);
            if ($result == false) {
                throw new \Espo\Core\Exceptions\Error('Metadata::setOrmMetadata() - Cannot save ormMetadata to a file');
            }
        }

        $this->ormMeta = $ormMeta;

        return $result;
    }

    /**
     * Get Entity path, ex. Espo.Entities.Account or Modules\Crm\Entities\MyModule
     *
     * @param string $entityName
     * @param bool $delim - delimiter
     *
     * @return string
     */
    public function getEntityPath($entityName, $delim = '\\')
    {
        $path = $this->getScopePath($entityName, $delim);

        return implode($delim, array($path, 'Entities', Util::normilizeClassName(ucfirst($entityName))));
    }

    public function getRepositoryPath($entityName, $delim = '\\')
    {
        $path = $this->getScopePath($entityName, $delim);

        return implode($delim, array($path, 'Repositories', Util::normilizeClassName(ucfirst($entityName))));
    }

    /**
     * Get Scopes
     *
     * @return array
     */
    public function getScopes()
    {
        if (!empty($this->scopes)) {
            return $this->scopes;
        }

        $scopeList = $this->get('scopes');
        if (!is_array($scopeList)) {
            $this->init(true);
            $scopeList = $this->get('scopes');
        }

        $scopes = array();
        if (is_array($scopeList)) {
            foreach ($scopeList as $name => $details) {
                $scopes[$name] = isset($details['module']) ? $details['module'] : false;
            }
        }

        return $this->scopes = $scopes;
    }

    /**
     * Get Module List
     *
     * @return array
     */
    public function getModuleList()
    {
        if (!empty($this->moduleList)) {
            return $this->moduleList;
        }

        $scopes = $this->getScopes();

        $modulesToSort = array();
        foreach ($scopes as $moduleName) {
            if (!empty($moduleName) && !isset($modulesToSort[$moduleName])) {
                $modulesToSort[$moduleName] = $this->getModuleConfig()->get($moduleName . '.order', $this->defaultModuleOrder);
            }
        }

        krsort($modulesToSort);
        asort($modulesToSort);

        $this->moduleList = array_keys($modulesToSort);

        return $this->moduleList;
    }

    /**
     * Get module name if it's a custom module or empty string for core entity
     *
     * @param string $scopeName
     *
     * @return string
     */
    public function getScopeModuleName($scopeName)
    {
        return $this->get('scopes.' . $scopeName . '.module', false);
    }

    /**
     * Get Scope path, ex. "Modules/Crm" for Account
     *
     * @param string $scopeName
     * @param string $delim - delimiter
     *
     * @return string
     */
    public function getScopePath($scopeName, $delim = '/')
    {
        $moduleName = $this->getScopeModuleName($scopeName);

        $path = ($moduleName !== false) ? 'Espo/Modules/'.$moduleName : 'Espo';

        if ($delim != '/') {
           $path = str_replace('/', $delim, $path);
        }

        return $path;
    }

    /**
      * Check if scope exists
      *
      * @param string $scopeName
      *
      * @return bool
      */
    public function isScopeExists($scopeName)
    {
        $scopeModuleMap = $this->getScopes();

        $lowerEntityName = strtolower($scopeName);
        foreach($scopeModuleMap as $rowEntityName => $rowModuleName) {
            if ($lowerEntityName == strtolower($rowEntityName)) {
                return true;
            }
        }

        return false;
    }

}
