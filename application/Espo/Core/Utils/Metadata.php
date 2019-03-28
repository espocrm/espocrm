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

use Espo\Core\Exceptions\Error;

class Metadata
{
    protected $data = null;

    protected $objData = null;

    protected $useCache;

    private $unifier;

    private $objUnifier;

    private $fileManager;

    private $moduleConfig;

    private $metadataHelper;

    protected $pathToModules = 'application/Espo/Modules';

    protected $cacheFile = 'data/cache/application/metadata.php';

    protected $objCacheFile = 'data/cache/application/objMetadata.php';

    protected $paths = array(
        'corePath' => 'application/Espo/Resources/metadata',
        'modulePath' => 'application/Espo/Modules/{*}/Resources/metadata',
        'customPath' => 'custom/Espo/Custom/Resources/metadata',
    );

    private $moduleList = null;

    protected $frontendHiddenPathList = [
        ['app', 'formula', 'functionClassNameMap'],
        ['app', 'fileStorage', 'implementationClassNameMap'],
        ['app', 'emailNotifications', 'handlerClassNameMap'],
        ['app', 'client'],
    ];

    /**
     * Default module order
     * @var integer
     */
    protected $defaultModuleOrder = 10;

    private $deletedData = array();

    private $changedData = array();

    public function __construct(\Espo\Core\Utils\File\Manager $fileManager, $useCache = false)
    {
        $this->useCache = $useCache;
        $this->fileManager = $fileManager;
    }

    protected function getFileManager()
    {
        return $this->fileManager;
    }

    protected function getUnifier()
    {
        if (!isset($this->unifier)) {
            $this->unifier = new \Espo\Core\Utils\File\Unifier($this->fileManager, $this, false);
        }

        return $this->unifier;
    }

    protected function getObjUnifier()
    {
        if (!isset($this->objUnifier)) {
            $this->objUnifier = new \Espo\Core\Utils\File\Unifier($this->fileManager, $this, true);
        }

        return $this->objUnifier;
    }

    protected function getModuleConfig()
    {
        if (!isset($this->moduleConfig)) {
            $this->moduleConfig = new \Espo\Core\Utils\Module($this->fileManager, $this->useCache);
        }

        return $this->moduleConfig;
    }

    protected function getMetadataHelper()
    {
        if (!isset($this->metadataHelper)) {
            $this->metadataHelper = new Metadata\Helper($this);
        }

        return $this->metadataHelper;
    }

    public function isCached()
    {
        if (!$this->useCache) {
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
        if (!$this->useCache) {
            $reload = true;
        }

        if (file_exists($this->cacheFile) && !$reload) {
            $this->data = $this->getFileManager()->getPhpContents($this->cacheFile);
        } else {
            $this->clearVars();
            $objData = $this->getAllObjects(false, $reload);
            $this->data = Util::objectToArray($objData);

            if ($this->useCache) {
                $isSaved = $this->getFileManager()->putPhpContents($this->cacheFile, $this->data);
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
        if (empty($this->data) || !is_array($this->data)) {
            $this->init();
        }

        return $this->data;
    }

    /**
    * Get Metadata
    *
    * @param mixed string|array $key
    * @param mixed $default
    *
    * @return array
    */
    public function get($key = null, $default = null)
    {
        $result = Util::getValueByKey($this->getData(), $key, $default);
        return $result;
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
            $this->init($reload);
        }

        if ($isJSON) {
            return Json::encode($this->data);
        }
        return $this->data;
    }

    protected function objInit($reload = false)
    {
        if (!$this->useCache) {
            $reload = true;
        }

        if (file_exists($this->objCacheFile) && !$reload) {
            $this->objData = $this->getFileManager()->getPhpContents($this->objCacheFile);
        } else {
            $this->objData = $this->getObjUnifier()->unify('metadata', $this->paths, true);
            $this->objData = $this->addAdditionalFieldsObj($this->objData);

            if ($this->useCache) {
                $isSaved = $this->getFileManager()->putPhpContents($this->objCacheFile, $this->objData, true);
                if ($isSaved === false) {
                    $GLOBALS['log']->emergency('Metadata:objInit() - metadata has not been saved to a cache file');
                }
            }
        }
    }

    protected function getObjData($reload = false)
    {
        if (!isset($this->objData) || $reload) {
            $this->objInit($reload);
        }

        return $this->objData;
    }

    /**
    * Get Object Metadata
    *
    * @param mixed string|array $key
    * @param mixed $default
    *
    * @return object
    */
    public function getObjects($key = null, $default = null)
    {
        $objData = $this->getObjData();

        return Util::getValueByKey($objData, $key, $default);
    }

    public function getAllObjects($isJSON = false, $reload = false)
    {
        $objData = $this->getObjData($reload);

        if ($isJSON) {
            return Json::encode($objData);
        }

        return $objData;
    }

    public function getAllForFrontend()
    {
        $data = $this->getAllObjects();

        foreach ($this->frontendHiddenPathList as $row) {
            $p =& $data;
            $path = [&$p];
            foreach ($row as $i => $item) {
                if (is_array($item)) break;
                if (!property_exists($p, $item)) break;
                if ($i == count($row) - 1) {
                    unset($p->$item);
                    $o =& $p;
                    for ($j = $i - 1; $j > 0; $j--) {
                        if (is_object($o) && !count(get_object_vars($o))) {
                            $o =& $path[$j];
                            $k = $row[$j];
                            unset($o->$k);
                        } else {
                            break;
                        }
                    }
                } else {
                    $p =& $p->$item;
                    $path[] = &$p;
                }
            }
        }
        return $data;
    }

    protected function addAdditionalFieldsObj($data)
    {
        if (!isset($data->entityDefs)) return $data;

        $fieldDefinitionList = Util::objectToArray($data->fields);

        foreach (get_object_vars($data->entityDefs) as $entityType => $entityDefsItem) {

            if (isset($data->entityDefs->$entityType->collection)) {

                $collectionItem = $data->entityDefs->$entityType->collection;

                if (isset($collectionItem->orderBy)) {
                    $collectionItem->sortBy = $collectionItem->orderBy;
                } else if (isset($collectionItem->sortBy)) {
                    $collectionItem->orderBy = $collectionItem->sortBy;
                }

                if (isset($collectionItem->order)) {
                     $collectionItem->asc = $collectionItem->order === 'asc' ? true : false;
                } else if (isset($collectionItem->asc)) {
                    $collectionItem->order = $collectionItem->asc === true ? 'asc' : 'desc';
                }
            }

            if (!isset($entityDefsItem->fields)) continue;
            foreach (get_object_vars($entityDefsItem->fields) as $field => $fieldDefsItem) {
                $additionalFields = $this->getMetadataHelper()->getAdditionalFieldList($field, Util::objectToArray($fieldDefsItem), $fieldDefinitionList);

                if (!$additionalFields) continue;
                foreach ($additionalFields as $subFieldName => $subFieldParams) {
                    if (isset($entityDefsItem->fields->$subFieldName)) {
                        $data->entityDefs->$entityType->fields->$subFieldName = DataUtil::merge(Util::arrayToObject($subFieldParams), $entityDefsItem->fields->$subFieldName);
                    } else {
                        $data->entityDefs->$entityType->fields->$subFieldName = Util::arrayToObject($subFieldParams);
                    }
                }
            }
        }

        return $data;
    }

    /**
     * Get metadata definition in custom directory
     *
     * @param  string|array $key
     * @param  mixed $default
     *
     * @return object|mixed
     */
    public function getCustom($key1, $key2, $default = null)
    {
        $filePath = array($this->paths['customPath'], $key1, $key2.'.json');
        $fileContent = $this->getFileManager()->getContents($filePath);

        if ($fileContent) {
            return Json::decode($fileContent);
        }

        return $default;
    }

    /**
     * Set and save metadata in custom directory. The data is not merging with existing data. Use getCustom() to get existing data.
     *
     * @param  string $key1
     * @param  string $key2
     * @param  array $data
     *
     * @return boolean
     */
    public function saveCustom($key1, $key2, $data)
    {
        if (is_object($data)) {
            foreach ($data as $key => $item) {
                if ($item == new \stdClass()) {
                    unset($data->$key);
                }
            }
        }

        $filePath = array($this->paths['customPath'], $key1, $key2.'.json');
        $changedData = Json::encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $result = $this->getFileManager()->putContents($filePath, $changedData);

        $this->init(true);

        return true;
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
        if (is_array($data)) {
            foreach ($data as $key => $item) {
                if (is_array($item) && empty($item)) {
                    unset($data[$key]);
                }
            }
        }

        $newData = array(
            $key1 => array(
                $key2 => $data,
            ),
        );

        $this->changedData = Util::merge($this->changedData, $newData);
        $this->data = Util::merge($this->getData(), $newData);

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
    public function delete($key1, $key2, $unsets = null)
    {
        if (!is_array($unsets)) {
            $unsets = (array) $unsets;
        }

        switch ($key1) {
            case 'entityDefs':
                //unset related additional fields, e.g. a field with "address" type
                $fieldDefinitionList = $this->get('fields');

                $unsetList = $unsets;
                foreach ($unsetList as $unsetItem) {
                    if (preg_match('/fields\.([^\.]+)/', $unsetItem, $matches) && isset($matches[1])) {
                        $fieldName = $matches[1];
                        $fieldPath = [$key1, $key2, 'fields', $fieldName];

                        $additionalFields = $this->getMetadataHelper()->getAdditionalFieldList($fieldName, $this->get($fieldPath, []), $fieldDefinitionList);
                        if (is_array($additionalFields)) {
                            foreach ($additionalFields as $additionalFieldName => $additionalFieldParams) {
                                $unsets[] = 'fields.' . $additionalFieldName;
                            }
                        }
                    }
                }
                break;
        }

        $normalizedData = array(
            '__APPEND__',
        );
        $metadataUnsetData = array();
        foreach ($unsets as $unsetItem) {
            $normalizedData[] = $unsetItem;
            $metadataUnsetData[] = implode('.', array($key1, $key2, $unsetItem));
        }

        $unsetData = array(
            $key1 => array(
                $key2 => $normalizedData
            )
        );

        $this->deletedData = Util::merge($this->deletedData, $unsetData);
        $this->deletedData = Util::unsetInArrayByValue('__APPEND__', $this->deletedData, true);

        $this->data = Util::unsetInArray($this->getData(), $metadataUnsetData, true);
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
     * Load modules
     *
     * @return void
     */
    protected function loadModuleList()
    {
        $modules = $this->getFileManager()->getFileList($this->pathToModules, false, '', false);

        $modulesToSort = array();
        if (is_array($modules)) {
            foreach ($modules as $moduleName) {
                if (!empty($moduleName) && !isset($modulesToSort[$moduleName])) {
                    $modulesToSort[$moduleName] = $this->getModuleConfig()->get($moduleName . '.order', $this->defaultModuleOrder);
                }
            }
        }

        array_multisort(array_values($modulesToSort), SORT_ASC, array_keys($modulesToSort), SORT_ASC, $modulesToSort);

        $this->moduleList = array_keys($modulesToSort);
    }

    /**
     * Get Module List
     *
     * @return array
     */
    public function getModuleList()
    {
        if (!isset($this->moduleList)) {
            $this->loadModuleList();
        }

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
     * Clear metadata variables when reload meta
     *
     * @return void
     */
    protected function clearVars()
    {
        $this->data = null;
        $this->moduleList = null;
    }
}
