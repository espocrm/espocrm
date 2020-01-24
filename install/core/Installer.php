<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2020 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

    protected $installerConfig;

    protected $isAuth = false;

    protected $permissionError;

    private $passwordHash;

    protected $defaultSettings;

    protected $permittedSettingList = array(
        'dateFormat',
        'timeFormat',
        'timeZone',
        'weekStart',
        'defaultCurrency',
        'language',
        'thousandSeparator',
        'decimalMark',
        'smtpServer',
        'smtpPort',
        'smtpAuth',
        'smtpSecurity',
        'smtpUsername',
        'smtpPassword',
        'outboundEmailFromName',
        'outboundEmailFromAddress',
        'outboundEmailIsShared',
    );

    public function __construct()
    {
        $this->initialize();

        $this->app = new \Espo\Core\Application();

        $user = $this->getEntityManager()->getEntity('User');
        $this->app->getContainer()->setUser($user);

        require_once('install/core/InstallerConfig.php');
        $this->installerConfig = new InstallerConfig();

        require_once('install/core/SystemHelper.php');
        $this->systemHelper = new SystemHelper();

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

    protected function getInstallerConfig()
    {
        return $this->installerConfig;
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
        $installerConfig = $this->getInstallerConfig();

        if ($installerConfig->get('isInstalled')) {
            return true;
        }

        return $this->app->isInstalled();
    }

    protected function getLanguage()
    {
        if (!isset($this->language)) {
            $this->language = $this->app->getContainer()->get('language');
        }

        return $this->language;
    }

    public function getLanguageList($isTranslated = true)
    {
        $languageList = $this->app->getContainer()->get('metadata')->get(['app', 'language', 'list']);

        if ($isTranslated) {
            return $this->getLanguage()->translate('language', 'options', 'Global', $languageList);
        }

        return $languageList;
    }

    protected function getCurrencyList()
    {
        return $this->app->getMetadata()->get('app.currency.list');
    }

    public function getInstallerConfigData()
    {
        return $this->getInstallerConfig()->getAllData();
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
    public function saveData(array $saveData)
    {
        $initData = include('install/core/afterInstall/config.php');
        $databaseDefaults = $this->app->getContainer()->get('config')->get('database');

        $data = [
            'database' => array_merge($databaseDefaults, $saveData['database']),
            'language' => $saveData['language'],
            'siteUrl' => !empty($saveData['siteUrl']) ? $saveData['siteUrl'] : $this->getSystemHelper()->getBaseUrl(),
            'passwordSalt' => $this->getPasswordHash()->generateSalt(),
            'cryptKey' => $this->getContainer()->get('crypt')->generateKey(),
            'hashSecretKey' => \Espo\Core\Utils\Util::generateSecretKey(),
        ];

        if (empty($saveData['defaultPermissions']['user'])) {
            $saveData['defaultPermissions']['user'] = $this->getFileManager()->getPermissionUtils()->getDefaultOwner(true);
        }

        if (empty($saveData['defaultPermissions']['group'])) {
            $saveData['defaultPermissions']['group'] = $this->getFileManager()->getPermissionUtils()->getDefaultGroup(true);
        }

        if (!empty($saveData['defaultPermissions']['user'])) {
            $data['defaultPermissions']['user'] = $saveData['defaultPermissions']['user'];
        }
        if (!empty($saveData['defaultPermissions']['group'])) {
            $data['defaultPermissions']['group'] = $saveData['defaultPermissions']['group'];
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

    public function savePreferences($preferences)
    {
        $preferences = $this->normalizeSettingParams($preferences);

        $currencyList = $this->getConfig()->get('currencyList', []);

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

        $installerConfig = $this->getInstallerConfig();
        $installerConfig->set('isInstalled', true);
        $installerConfig->save();

        $config = $this->app->getContainer()->get('config');
        $config->set('isInstalled', true);
        $result &= $config->save();

        return $result;
    }

    public function getDefaultSettings()
    {
        if (!$this->defaultSettings) {

            $settingDefs = $this->app->getMetadata()->get('entityDefs.Settings.fields');

            $defaults = array();
            foreach ($this->permittedSettingList as $fieldName) {

                if (!isset($settingDefs[$fieldName])) continue;

                switch ($fieldName) {
                    case 'defaultCurrency':
                        $settingDefs['defaultCurrency']['options'] = $this->getCurrencyList();
                        break;

                    case 'language':
                        $settingDefs['language']['options'] = $this->getLanguageList(false);
                        break;
                }

                $defaults[$fieldName] = $this->translateSetting($fieldName, $settingDefs[$fieldName]);
            }

            $this->defaultSettings = $defaults;
        }

        return $this->defaultSettings;
    }

    protected function normalizeSettingParams(array $params)
    {
        $defaultSettings = $this->getDefaultSettings();

        $normalizedParams = [];
        foreach ($params as $name => $value) {
            if (!isset($defaultSettings[$name])) continue;

            $paramDefs = $defaultSettings[$name];
            $paramType = isset($paramDefs['type']) ? $paramDefs['type'] : 'varchar';

            switch ($paramType) {
                case 'enumInt':
                    $value = (int) $value;

                case 'enum':
                    if (isset($paramDefs['options']) && array_key_exists($value, $paramDefs['options'])) {
                        $normalizedParams[$name] = $value;
                    } else if (array_key_exists('default', $paramDefs)) {
                        $normalizedParams[$name] = $paramDefs['default'];
                        $GLOBALS['log']->warning('Incorrect value ['. $value .'] for Settings parameter ['. $name .']. Use default value ['. $paramDefs['default'] .'].');
                    }
                    break;

                case 'bool':
                    $normalizedParams[$name] = (bool) $value;
                    break;

                case 'int':
                    $normalizedParams[$name] = (int) $value;
                    break;

                case 'varchar':
                default:
                    $normalizedParams[$name] = $value;
                    break;
            }
        }

        return $normalizedParams;
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
