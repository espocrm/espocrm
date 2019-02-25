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

use Espo\Core\Utils\Util;
use Espo\Core\Utils\File\Manager as FileManager;
use Espo\Core\Utils\Config;

class Installer
{
    protected $app = null;

    protected $language = null;

    protected $systemHelper = null;

    protected $databaseHelper = null;

    protected $isAuth = false;

    protected $permissionMap;

    protected $permissionError;

    private $passwordHash;

    protected $settingList = array(
        'dateFormat',
        'timeFormat',
        'timeZone',
        'weekStart',
        'defaultCurrency' => array(
            'currencyList', 'defaultCurrency',
        ),
        'smtpSecurity',
        'language',
    );


    public function __construct()
    {
        $this->initialize();

        $this->app = new \Espo\Core\Application();

        $user = $this->getEntityManager()->getEntity('User');
        $this->app->getContainer()->setUser($user);

        require_once('install/core/SystemHelper.php');
        $this->systemHelper = new SystemHelper();

        $configPath = $this->getConfig()->getConfigPath();
        $this->permissionMap = $this->getConfig()->get('permissionMap');
        $this->permissionMap['writable'][] = $configPath;

        $this->databaseHelper = new \Espo\Core\Utils\Database\Helper($this->getConfig());
    }

    protected function initialize()
    {
        $fileManager = new FileManager();
        $config = new Config($fileManager);
        $configPath = $config->getConfigPath();

        if (!file_exists($configPath)) {
            $fileManager->putPhpContents($configPath, array());
        }

        $data = include('data/config.php');
        $defaultData = $config->getDefaults();

        //save default data if not exists, check by keys
        if (!Util::arrayKeysExists(array_keys($defaultData), $data)) {
            $defaultData = array_replace_recursive($defaultData, $data);
            $config->set($defaultData);
            $config->save();
        }
    }

    protected function getContainer()
    {
        return $this->app->getContainer();
    }

    protected function getEntityManager()
    {
        return $this->getContainer()->get('entityManager');
    }

    public function getConfig()
    {
        return $this->app->getContainer()->get('config');
    }

    protected function getSystemHelper()
    {
        return $this->systemHelper;
    }

    protected function getDatabaseHelper()
    {
        return $this->databaseHelper;
    }

    protected function getFileManager()
    {
        return $this->app->getContainer()->get('fileManager');
    }

    protected function getPasswordHash()
    {
        if (!isset($this->passwordHash)) {
            $config = $this->getConfig();
            $this->passwordHash = new \Espo\Core\Utils\PasswordHash($config);
        }

        return $this->passwordHash;
    }

    public function getVersion()
    {
        return $this->getConfig()->get('version');
    }

    protected function auth()
    {
        if (!$this->isAuth) {
            $auth = new \Espo\Core\Utils\Auth($this->app->getContainer());
            $auth->useNoAuth();

            $this->isAuth = true;
        }

        return $this->isAuth;
    }

    public function isInstalled()
    {
        return $this->app->isInstalled();
    }

    protected function getLanguage()
    {
        if (!isset($this->language)) {
            $this->language = $this->app->getContainer()->get('language');
        }

        return $this->language;
    }

    public function getLanguageList()
    {
        $config = $this->app->getContainer()->get('config');

        $languageList = $config->get('languageList');

        $translated = $this->getLanguage()->translate('language', 'options', 'Global', $languageList);

        return $translated;
    }

    public function getSystemRequirementList($type, $requiredOnly = false, array $additionalData = null)
    {
         $systemRequirementManager = new \Espo\Core\Utils\SystemRequirements($this->app->getContainer());
         return $systemRequirementManager->getRequiredListByType($type, $requiredOnly, $additionalData);
    }

    public function checkDatabaseConnection(array $params, $isCreateDatabase = false)
    {
        $databaseHelper = $this->getDatabaseHelper();

        try {
            $pdo = $this->getDatabaseHelper()->createPdoConnection($params);
        } catch (\Exception $e) {
            if ($isCreateDatabase && $e->getCode() == '1049') {
                $modParams = $params;
                unset($modParams['dbname']);

                $pdo = $this->getDatabaseHelper()->createPdoConnection($modParams);
                $pdo->query("CREATE DATABASE IF NOT EXISTS `". $params['dbname'] ."`");

                return $this->checkDatabaseConnection($params, false);
            }

            throw $e;
        }

        return true;
    }

    /**
     * Save data
     *
     * @param  array $database
     * array (
     *   'driver' => 'pdo_mysql',
     *   'host' => 'localhost',
     *   'dbname' => 'espocrm_test',
     *   'user' => 'root',
     *   'password' => '',
     * ),
     * @param  string $language
     * @return bool
     */
    public function saveData($database, $language)
    {
        $initData = include('install/core/afterInstall/config.php');
        $siteUrl = $this->getSystemHelper()->getBaseUrl();
        $databaseDefaults = $this->app->getContainer()->get('config')->get('database');

        $data = array(
            'database' => array_merge($databaseDefaults, $database),
            'language' => $language,
            'siteUrl' => $siteUrl,
            'passwordSalt' => $this->getPasswordHash()->generateSalt(),
            'cryptKey' => $this->getContainer()->get('crypt')->generateKey()
        );

        $owner = $this->getFileManager()->getPermissionUtils()->getDefaultOwner(true);
        $group = $this->getFileManager()->getPermissionUtils()->getDefaultGroup(true);

        if (!empty($owner)) {
            $data['defaultPermissions']['user'] = $owner;
        }
        if (!empty($group)) {
            $data['defaultPermissions']['group'] = $group;
        }

        $data = array_merge($data, $initData);
        $result = $this->saveConfig($data);

        return $result;
    }

    public function saveConfig($data)
    {
        $config = $this->app->getContainer()->get('config');

        $config->set($data);
        $result = $config->save();

        return $result;
    }

    public function buildDatabase()
    {
        $result = false;

        try {
            $result = $this->app->getContainer()->get('dataManager')->rebuild();
        } catch (\Exception $e) {
            $this->auth();
            $result = $this->app->getContainer()->get('dataManager')->rebuild();
        }

        return $result;
    }

    public function setPreferences($preferences)
    {
        $currencyList = $this->getConfig()->get('currencyList', array());
        if (isset($preferences['defaultCurrency']) && !in_array($preferences['defaultCurrency'], $currencyList)) {

            $preferences['currencyList'] = array($preferences['defaultCurrency']);
            $preferences['baseCurrency'] = $preferences['defaultCurrency'];
        }

        $res = $this->saveConfig($preferences);

        /*save these settings for admin*/
        $this->setAdminPreferences($preferences);

        return $res;
    }


    protected function createRecords()
    {
        $records = include('install/core/afterInstall/records.php');

        $result = true;
        foreach ($records as $entityName => $recordList) {
            foreach ($recordList as $data) {
                $result &= $this->createRecord($entityName, $data);
            }
        }

        return $result;
    }

    protected function createRecord($entityName, $data)
    {
        if (isset($data['id'])) {

            $entity = $this->getEntityManager()->getEntity($entityName, $data['id']);

            if (!isset($entity)) {
                $pdo = $this->getEntityManager()->getPDO();

                $sql = "SELECT id FROM `".Util::toUnderScore($entityName)."` WHERE `id` = '".$data['id']."'";
                $sth = $pdo->prepare($sql);
                $sth->execute();

                $deletedEntity = $sth->fetch(\PDO::FETCH_ASSOC);

                if ($deletedEntity) {
                    $sql = "UPDATE `".Util::toUnderScore($entityName)."` SET deleted = '0' WHERE `id` = '".$data['id']."'";
                    $pdo->prepare($sql)->execute();

                    $entity = $this->getEntityManager()->getEntity($entityName, $data['id']);
                }
            }
        }

        if (!isset($entity)) {
            if (isset($data['name'])) {
                $entity = $this->getEntityManager()->getRepository($entityName)->where(array(
                    'name' => $data['name'],
                ))->findOne();
            }

            if (!isset($entity)) {
                $entity = $this->getEntityManager()->getEntity($entityName);
            }
        }

        $entity->set($data);

        $id = $this->getEntityManager()->saveEntity($entity);

        return is_string($id);
    }

    public function createUser($userName, $password)
    {
        $this->auth();

        $user = array(
            'id' => '1',
            'userName' => $userName,
            'password' => $this->getPasswordHash()->hash($password),
            'lastName' => 'Admin',
            'type' => 'admin',
        );

        $result = $this->createRecord('User', $user);

        return $result;
    }

    protected function setAdminPreferences($preferences)
    {
        $allowedPreferences = array(
            'dateFormat',
            'timeFormat',
            'timeZone',
            'weekStart',
            'defaultCurrency',
            'thousandSeparator',
            'decimalMark',
            'language',
        );

        $data = array_intersect_key($preferences, array_flip($allowedPreferences));
        if (empty($data)) {
            return true;
        }

        $entity = $this->getEntityManager()->getEntity('Preferences', '1');
        if ($entity) {
            $entity->set($data);
            return $this->getEntityManager()->saveEntity($entity);
        }

        return false;
    }

    public function checkPermission()
    {
        return $this->getFileManager()->getPermissionUtils()->setMapPermission();
    }

    public function getLastPermissionError()
    {
        return $this->getFileManager()->getPermissionUtils()->getLastErrorRules();
    }

    public function setSuccess()
    {
        $this->auth();

        /** afterInstall scripts */
        $result = $this->createRecords();
        $result &= $this->executeQueries();
        /** END: afterInstall scripts */

        $config = $this->app->getContainer()->get('config');
        $config->set('isInstalled', true);
        $result &= $config->save();

        return $result;
    }

    public function getSettingDefaults()
    {
        $defaults = array();

        $settingDefs = $this->app->getMetadata()->get('entityDefs.Settings.fields');

        foreach ($this->settingList as $fieldName => $field) {

            if (is_array($field)) {
                $fieldDefaults = array();
                foreach ($field as $subField) {
                    if (isset($settingDefs[$subField])) {
                        $fieldDefaults = array_merge($fieldDefaults, $this->translateSetting($subField, $settingDefs[$subField]));
                    }
                }
                $defaults[$fieldName] = $fieldDefaults;

            } else if (isset($settingDefs[$field])) {

                $defaults[$field] = $this->translateSetting($field, $settingDefs[$field]);
            }
        }

        if (isset($defaults['language'])) {
            $defaults['language']['options'] = $this->getLanguageList();
        }

        return $defaults;
    }

    protected function translateSetting($name, array $settingDefs)
    {
        if (isset($settingDefs['options'])) {
            $optionLabel = $this->getLanguage()->translate($name, 'options', 'Settings', $settingDefs['options']);

            if ($optionLabel == $name) {
                $optionLabel = $this->getLanguage()->translate($name, 'options', 'Global', $settingDefs['options']);
            }

            if ($optionLabel == $name) {
                $optionLabel = array();
                foreach ($settingDefs['options'] as $key => $value) {
                    $optionLabel[$value] = $value;
                }
            }

            $settingDefs['options'] = $optionLabel;
        }

        return $settingDefs;
    }

    public function getCronMessage()
    {
        return $this->getContainer()->get('scheduledJob')->getSetupMessage();
    }

    protected function executeQueries()
    {
        $queries = include('install/core/afterInstall/queries.php');

        $pdo = $this->getEntityManager()->getPDO();

        $result = true;

        foreach ($queries as $query) {
            $sth = $pdo->prepare($query);

            try {
                $result &= $sth->execute();
            } catch (\Exception $e) {
                $GLOBALS['log']->warning('Error executing the query: ' . $query);
            }

        }

        return $result;
    }
}
