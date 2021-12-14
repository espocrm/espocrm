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

use Espo\Core\Application;
use Espo\Core\Container;
use Espo\Core\InjectableFactory;

use Espo\Core\Utils\Util;
use Espo\Core\Utils\Config\ConfigFileManager;
use Espo\Core\Utils\Config;
use Espo\Core\Utils\Config\ConfigWriter;
use Espo\Core\Utils\Config\ConfigWriterFileManager;
use Espo\Core\Utils\Database\Helper as DatabaseHelper;
use Espo\Core\Utils\PasswordHash;
use Espo\Core\Utils\SystemRequirements;
use Espo\Core\Utils\Metadata;
use Espo\Core\Utils\File\Manager as FileManager;
use Espo\Core\Utils\Language;

use Espo\Core\Binding\BindingContainerBuilder;

use Espo\Core\ORM\EntityManager;

class Installer
{
    private $app = null;

    private $language = null;

    private $systemHelper = null;

    private $databaseHelper = null;

    private $installerConfig;

    private $isAuth = false;

    private $passwordHash;

    private $defaultSettings;

    private $permittedSettingList = [
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
    ];

    public function __construct()
    {
        $this->initialize();

        require_once('install/core/InstallerConfig.php');

        $this->installerConfig = new InstallerConfig();

        require_once('install/core/SystemHelper.php');

        $this->systemHelper = new SystemHelper();

        $this->databaseHelper = new DatabaseHelper($this->getConfig());
    }

    private function initialize(): void
    {
        $fileManager = new ConfigFileManager();

        $config = new Config($fileManager);

        $configPath = $config->getConfigPath();

        if (!file_exists($configPath)) {
            $fileManager->putPhpContents($configPath, []);

            $config->update();
        }

        $defaultData = include('application/Espo/Resources/defaults/config.php');

        $configData = [];

        foreach (array_keys($defaultData) as $key) {
            if (!$config->has($key)) {
                continue;
            }

            $configData[$key] = $config->get($key);
        }

        $configWriterFileManager = new ConfigWriterFileManager(
            null,
            $config->get('defaultPermissions') ?? null
        );

        /** @var InjectableFactory $injectableFactory */
        $injectableFactory = (new Application())->getContainer()->get('injectableFactory');

        $configWriter = $injectableFactory->createWithBinding(
            ConfigWriter::class,
            BindingContainerBuilder::create()
                ->bindInstance(Config::class, $config)
                ->bindInstance(ConfigWriterFileManager::class, $configWriterFileManager)
                ->build()
        );

        // Save default data if does not exist.
        if (!Util::arrayKeysExists(array_keys($defaultData), $configData)) {
            $defaultData = array_replace_recursive($defaultData, $configData);

            $configWriter->setMultiple($defaultData);
            $configWriter->save();
        }

        $this->app = new Application();
    }

    private function getContainer(): Container
    {
        return $this->app->getContainer();
    }

    private function getEntityManager(): EntityManager
    {
        return $this->getContainer()->get('entityManager');
    }

    public function getMetadata(): Metadata
    {
        return $this->app->getContainer()->get('metadata');
    }

    public function getConfig(): Config
    {
        return $this->app->getContainer()->get('config');
    }

    public function createConfigWriter(): ConfigWriter
    {
        return $this->app->getContainer()->get('injectableFactory')->create(ConfigWriter::class);
    }

    private function getSystemHelper()
    {
        return $this->systemHelper;
    }

    private function getDatabaseHelper()
    {
        return $this->databaseHelper;
    }

    private function getInstallerConfig()
    {
        return $this->installerConfig;
    }

    private function getFileManager(): FileManager
    {
        return $this->app->getContainer()->get('fileManager');
    }

    private function getPasswordHash(): PasswordHash
    {
        if (!isset($this->passwordHash)) {
            $config = $this->getConfig();
            $configWriter = $this->createConfigWriter();

            $this->passwordHash = new PasswordHash($config, $configWriter);
        }

        return $this->passwordHash;
    }

    public function getVersion(): ?string
    {
        return $this->getConfig()->get('version');
    }

    private function auth()
    {
        if (!$this->isAuth) {
            $this->app->setupSystemUser();

            $this->isAuth = true;
        }

        return $this->isAuth;
    }

    public function isInstalled(): bool
    {
        $installerConfig = $this->getInstallerConfig();

        if ($installerConfig->get('isInstalled')) {
            return true;
        }

        return $this->app->isInstalled();
    }

    private function getLanguage(): Language
    {
        if (!isset($this->language)) {
            try {
                $this->language = $this->app->getContainer()->get('defaultLanguage');
            }
            catch (Throwable $e) {
                echo "Error: " . $e->getMessage();

                $GLOBALS['log']->error($e->getMessage());

                die;
            }
        }

        return $this->language;
    }

    public function getLanguageList($isTranslated = true): array
    {
        $languageList = $this->getMetadata()->get(['app', 'language', 'list']);

        if ($isTranslated) {
            return $this->getLanguage()->translate('language', 'options', 'Global', $languageList);
        }

        return $languageList;
    }

    private function getCurrencyList(): array
    {
        return $this->getMetadata()->get('app.currency.list');
    }

    public function getInstallerConfigData()
    {
        return $this->getInstallerConfig()->getAllData();
    }

    public function getSystemRequirementList($type, $requiredOnly = false, array $additionalData = null)
    {
         $systemRequirementManager = $this->app
            ->getContainer()
            ->get('injectableFactory')
            ->create(SystemRequirements::class);

         return $systemRequirementManager->getRequiredListByType($type, $requiredOnly, $additionalData);
    }

    public function checkDatabaseConnection(
        array $params,
        bool $createDatabase = false
    ) {
        try {
            $pdo = $this->getDatabaseHelper()->createPdoConnection($params);
        }
        catch (Exception $e) {

            if ($createDatabase && $e->getCode() == '1049') {
                $pdo = $this->getDatabaseHelper()
                    ->createPdoConnection($params, true);

                $pdo->query(
                    "CREATE DATABASE IF NOT EXISTS `" . $params['dbname'] . "`"
                );

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

        $databaseDefaults = $this->app
            ->getContainer()
            ->get('config')
            ->get('database');

        $siteUrl = !empty($saveData['siteUrl']) ? $saveData['siteUrl'] : $this->getSystemHelper()->getBaseUrl();

        $data = [
            'database' => array_merge($databaseDefaults, $saveData['database']),
            'language' => $saveData['language'] ?? 'en_US',
            'siteUrl' => $siteUrl,
            'passwordSalt' => $this->getPasswordHash()->generateSalt(),
            'cryptKey' => $this->getContainer()->get('crypt')->generateKey(),
            'hashSecretKey' => Util::generateSecretKey(),
        ];

        if (empty($saveData['defaultPermissions']['user'])) {
            $saveData['defaultPermissions']['user'] = $this->getFileManager()
                ->getPermissionUtils()
                ->getDefaultOwner(true);
        }

        if (empty($saveData['defaultPermissions']['group'])) {
            $saveData['defaultPermissions']['group'] = $this->getFileManager()
                ->getPermissionUtils()
                ->getDefaultGroup(true);
        }

        if (!empty($saveData['defaultPermissions']['user'])) {
            $data['defaultPermissions']['user'] = $saveData['defaultPermissions']['user'];
        }

        if (!empty($saveData['defaultPermissions']['group'])) {
            $data['defaultPermissions']['group'] = $saveData['defaultPermissions']['group'];
        }

        $result = $this->saveConfig(
            array_merge($data, $initData)
        );

        return $result;
    }

    public function saveConfig($data)
    {
        $configWriter = $this->createConfigWriter();

        $configWriter->setMultiple($data);

        $configWriter->save();

        return true;
    }

    public function buildDatabase()
    {
        try {
            $this->app->getContainer()->get('dataManager')->rebuild();

            return true;
        }
        catch (Exception $e) {
            $this->auth();

            $this->app->getContainer()->get('dataManager')->rebuild();
        }

        return true;
    }

    public function savePreferences(array $rawPreferences)
    {
        $preferences = $this->normalizeSettingParams($rawPreferences);

        $currencyList = $this->getConfig()->get('currencyList', []);

        if (
            isset($preferences['defaultCurrency']) &&
            !in_array($preferences['defaultCurrency'], $currencyList)
        ) {
            $preferences['currencyList'] = [$preferences['defaultCurrency']];
            $preferences['baseCurrency'] = $preferences['defaultCurrency'];
        }

        $result = $this->saveConfig($preferences);

        $this->saveAdminPreferences($preferences);

        return $result;
    }

    private function createRecords()
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

    private function createRecord($entityName, $data)
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
                    $sql =
                        "UPDATE `". Util::toUnderScore($entityName)."` SET deleted = '0' " .
                        "WHERE `id` = '".$data['id']."'";

                    $pdo->prepare($sql)->execute();

                    $entity = $this->getEntityManager()->getEntity($entityName, $data['id']);
                }
            }
        }

        if (!isset($entity)) {
            if (isset($data['name'])) {
                $entity = $this->getEntityManager()
                    ->getRepository($entityName)
                    ->where([
                        'name' => $data['name'],
                    ])
                    ->findOne();
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

        $result = $this->createRecord('User', [
            'id' => '1',
            'userName' => $userName,
            'password' => $this->getPasswordHash()->hash($password),
            'lastName' => 'Admin',
            'type' => 'admin',
        ]);

        $this->saveAdminPreferences([
            'dateFormat' => '',
            'timeFormat' => '',
            'timeZone' => '',
            'weekStart' => -1,
            'defaultCurrency' => '',
            'language' => '',
            'thousandSeparator' => $this->getConfig()->get('thousandSeparator', ','),
            'decimalMark' => $this->getConfig()->get('decimalMark', '.'),
        ]);

        return $result;
    }

    private function saveAdminPreferences($preferences)
    {
        $permittedSettingList = [
            'dateFormat',
            'timeFormat',
            'timeZone',
            'weekStart',
            'defaultCurrency',
            'thousandSeparator',
            'decimalMark',
            'language',
        ];

        $data = array_intersect_key($preferences, array_flip($permittedSettingList));

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

        $this->executeFinalScript();
        /** END: afterInstall scripts */

        $installerConfig = $this->getInstallerConfig();

        $installerConfig->set('isInstalled', true);

        $installerConfig->save();

        $configWriter = $this->createConfigWriter();

        $configWriter->set('isInstalled', true);

        $configWriter->save();

        return $result;
    }

    public function getDefaultSettings()
    {
        if (!$this->defaultSettings) {

            $settingDefs = $this->getMetadata()->get('entityDefs.Settings.fields');

            $defaults = [];

            foreach ($this->permittedSettingList as $fieldName) {
                if (!isset($settingDefs[$fieldName])) {
                    continue;
                }

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

    private function normalizeSettingParams(array $params)
    {
        $defaultSettings = $this->getDefaultSettings();

        $normalizedParams = [];

        foreach ($params as $name => $value) {
            if (!isset($defaultSettings[$name])) {
                continue;
            }

            $paramDefs = $defaultSettings[$name];
            $paramType = isset($paramDefs['type']) ? $paramDefs['type'] : 'varchar';

            switch ($paramType) {
                case 'enumInt':
                    $value = (int) $value;

                case 'enum':
                    if (isset($paramDefs['options']) && array_key_exists($value, $paramDefs['options'])) {
                        $normalizedParams[$name] = $value;
                    }
                    else if (array_key_exists('default', $paramDefs)) {
                        $normalizedParams[$name] = $paramDefs['default'];

                        $GLOBALS['log']->warning(
                            'Incorrect value ['. $value .'] for Settings parameter ['. $name .']. ' .
                            'Use default value ['. $paramDefs['default'] .'].'
                        );
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

    private function translateSetting($name, array $settingDefs)
    {
        if (isset($settingDefs['options'])) {
            $optionLabel = $this->getLanguage()
                ->translate($name, 'options', 'Settings', $settingDefs['options']);

            if ($optionLabel == $name) {
                $optionLabel = $this->getLanguage()
                    ->translate($name, 'options', 'Global', $settingDefs['options']);
            }

            if ($optionLabel == $name) {
                $optionLabel = [];

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

    private function executeQueries()
    {
        $queries = include('install/core/afterInstall/queries.php');

        $pdo = $this->getEntityManager()->getPDO();

        $result = true;

        foreach ($queries as $query) {
            $sth = $pdo->prepare($query);

            try {
                $result &= $sth->execute();
            }
            catch (Exception $e) {
                $GLOBALS['log']->warning('Error executing the query: ' . $query);
            }

        }

        return $result;
    }

    private function executeFinalScript()
    {
        $this->prepareDummyJob();
    }

    private function prepareDummyJob()
    {
        $scheduledJob = $this->getEntityManager()
            ->getRepository('ScheduledJob')
            ->where([
                'job' => 'Dummy',
            ])
            ->findOne();

        if (!$scheduledJob) {
            return;
        }

        $this->getEntityManager()->createEntity('Job', [
            'name' => 'Dummy',
            'scheduledJobId' => $scheduledJob->id,
        ]);
    }
}
