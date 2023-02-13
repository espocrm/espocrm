<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2023 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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
use Espo\Core\ORM\DatabaseParamsFactory;
use Espo\Core\Utils\Id\RecordIdGenerator;
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
use Espo\Entities\Job;
use Espo\Entities\ScheduledJob;
use Espo\Entities\User;
use Espo\ORM\Query\SelectBuilder;

class Installer
{
    private SystemHelper $systemHelper;
    private DatabaseHelper $databaseHelper;
    private InstallerConfig $installerConfig;
    private DatabaseParamsFactory $databaseParamsFactory;
    private ?Application $app = null;
    private ?Language $language = null;
    private ?PasswordHash $passwordHash;
    private bool $isAuth = false;
    private ?array $defaultSettings = null;

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
        'theme',
    ];

    public function __construct()
    {
        $this->initialize();

        require_once('install/core/InstallerConfig.php');

        $this->installerConfig = new InstallerConfig();

        require_once('install/core/SystemHelper.php');

        $this->systemHelper = new SystemHelper();

        $this->databaseHelper = $this->getInjectableFactory()->create(DatabaseHelper::class);
        $this->databaseParamsFactory = $this->getInjectableFactory()->create(DatabaseParamsFactory::class);
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

        // Save default data if it does not exist.
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
        /** @var EntityManager */
        return $this->getContainer()->get('entityManager');
    }

    public function getMetadata(): Metadata
    {
        /** @var Metadata */
        return $this->app->getContainer()->get('metadata');
    }

    public function getInjectableFactory(): InjectableFactory
    {
        /** @var InjectableFactory */
        return $this->app->getContainer()->get('injectableFactory');
    }

    public function getConfig(): Config
    {
        /** @var Config */
        return $this->app->getContainer()->get('config');
    }

    public function createConfigWriter(): ConfigWriter
    {
        return $this->getInjectableFactory()->create(ConfigWriter::class);
    }

    private function getSystemHelper(): SystemHelper
    {
        return $this->systemHelper;
    }

    private function getDatabaseHelper(): DatabaseHelper
    {
        return $this->databaseHelper;
    }

    private function getInstallerConfig(): InstallerConfig
    {
        return $this->installerConfig;
    }

    private function getFileManager(): FileManager
    {
        /** @var FileManager */
        return $this->app->getContainer()->get('fileManager');
    }

    private function getPasswordHash(): PasswordHash
    {
        if (!isset($this->passwordHash)) {
            $this->passwordHash = $this->getInjectableFactory()->create(PasswordHash::class);
        }

        return $this->passwordHash;
    }

    public function getVersion(): ?string
    {
        return $this->getConfig()->get('version');
    }

    private function auth(): void
    {
        if (!$this->isAuth) {
            $this->app->setupSystemUser();

            $this->isAuth = true;
        }
    }

    public function isInstalled(): bool
    {
        $installerConfig = $this->getInstallerConfig();

        if ($installerConfig->get('isInstalled')) {
            return true;
        }

        return $this->app->isInstalled();
    }

    public function createLanguage(string $language): Language
    {
        return $this->getInjectableFactory()
            ->createWith(Language::class, ['language' => $language]);
    }

    public function getLanguage(): Language
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

    public function getThemeList(): array
    {
        return [
            'Espo',
            'Dark',
            'Glass',
            'Hazyblue',
            'Sakura',
            'Violet',
        ];
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
        /** @var SystemRequirements $systemRequirementManager */
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
        $databaseParams = $this->databaseParamsFactory->createWithMergedAssoc($params);

        try {
            $this->getDatabaseHelper()->createPDO($databaseParams);
        }
        catch (Exception $e) {
            if ($createDatabase && $e->getCode() == '1049') {
                $pdo = $this->getDatabaseHelper()->createPDO($databaseParams, true);

                $dbname = preg_replace('/[^A-Za-z0-9_\-@$#\(\)]+/', '', $params['dbname']);

                if ($dbname !== $params['dbname']) {
                    throw new Exception("Bad database name.");
                }

                $pdo->query(
                    "CREATE DATABASE IF NOT EXISTS `{$dbname}`"
                );

                return $this->checkDatabaseConnection($params, false);
            }

            throw $e;
        }

        return true;
    }

    /**
     * Save data.
     *
     * @param array<string, mixed> $saveData
     * array(
     *   'driver' => 'pdo_mysql',
     *   'host' => 'localhost',
     *   'dbname' => 'espocrm_test',
     *   'user' => 'root',
     *   'password' => '',
     * )
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
            'cryptKey' => Util::generateSecretKey(),
            'hashSecretKey' => Util::generateSecretKey(),
            'theme' => $saveData['theme'] ?? 'Violet',
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

        return $this->saveConfig(array_merge($data, $initData));
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
        catch (Exception) {
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

        /*$unsetList = [
            'dateFormat',
            'timeFormat',
            'timeZone',
            'weekStart',
            'defaultCurrency',
            'language',
        ];

        foreach ($unsetList as $item) {
            unset($preferences[$item]);
        }

        $this->saveAdminPreferences($preferences);*/

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

    private function createRecord(string $entityType, array $data): bool
    {
        $id = $data['id'] ?? null;

        $entity = null;

        $em = $this->getEntityManager();

        if ($id) {
            $entity = $em->getEntity($entityType, $id);

            if (!$entity) {
                $selectQuery = $em->getQueryBuilder()
                    ->select('id')
                    ->from($entityType)
                    ->withDeleted()
                    ->where(['id' => $id])
                    ->build();

                $entity = $em->getRDBRepository($entityType)
                    ->clone($selectQuery)
                    ->findOne();

                if ($entity) {
                    $updateQuery = $em->getQueryBuilder()
                        ->update()
                        ->in($entityType)
                        ->set(['deleted' => false])
                        ->where(['id' => $id])
                        ->build();

                    $em->getQueryExecutor()->execute($updateQuery);

                    $em->refreshEntity($entity);
                }
            }
        }

        if (!$entity) {
            if (isset($data['name'])) {
                $entity = $this->getEntityManager()
                    ->getRDBRepository($entityType)
                    ->where(['name' => $data['name']])
                    ->findOne();
            }

            if (!$entity) {
                $entity = $this->getEntityManager()->getNewEntity($entityType);
            }
        }

        $entity->set($data);

        $this->getEntityManager()->saveEntity($entity);

        return true;
    }

    public function createUser(string $userName, string $password): bool
    {
        $this->auth();

        $password = $this->getPasswordHash()->hash($password);

        $user = $this->getEntityManager()
            ->getRDBRepositoryByClass(User::class)
            ->clone(
                SelectBuilder::create()
                    ->from(User::ENTITY_TYPE)
                    ->withDeleted()
                    ->build()
            )
            ->where(['userName' => $userName])
            ->findOne();

        if ($user) {
            $user->set([
                'password' => $password,
                'deleted' => false,
            ]);

            $this->getEntityManager()->saveEntity($user);
        }

        if (!$user) {
            $id = $this->getInjectableFactory()
                ->createResolved(RecordIdGenerator::class)
                ->generate();

            $this->createRecord(User::ENTITY_TYPE, [
                'id' => $id,
                'userName' => $userName,
                'password' => $password,
                'lastName' => 'Admin',
                'type' => User::TYPE_ADMIN,
            ]);
        }

        /*$this->saveAdminPreferences([
            'dateFormat' => '',
            'timeFormat' => '',
            'timeZone' => '',
            'weekStart' => -1,
            'defaultCurrency' => '',
            'language' => '',
            'thousandSeparator' => $this->getConfig()->get('thousandSeparator', ','),
            'decimalMark' => $this->getConfig()->get('decimalMark', '.'),
        ]);*/

        return true;
    }

    /*private function saveAdminPreferences($preferences)
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
            'theme',
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
    }*/

    public function checkPermission(): bool
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

    /**
     * @return array<string, mixed>
     */
    public function getDefaultSettings(): array
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

                    case 'theme':
                        $settingDefs['theme']['options'] = $this->getThemeList();

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
            $paramType = $paramDefs['type'] ?? 'varchar';

            switch ($paramType) {
                case 'enumInt':
                    $normalizedParams[$name] = (int) $value;;

                    break;

                case 'enum':
                    if (
                        isset($paramDefs['options']) && array_key_exists($value, $paramDefs['options']) ||
                        !isset($paramDefs['options'])
                    ) {
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
        if (!isset($settingDefs['options'])) {
            return $settingDefs;
        }

        $translation = !empty($settingDefs['translation'])
            ? explode('.', $settingDefs['translation']) : null;

        $label = $translation[2] ?? $name;
        $category = $translation[1] ?? 'options';
        $scope = $translation[0] ?? 'Settings';

        $translatedOptions = $this->getLanguage()
            ->translate($label, $category, $scope, $settingDefs['options']);

        if ($translatedOptions == $name) {
            $translatedOptions = $this->getLanguage()
                ->translate($name, 'options', 'Global', $settingDefs['options']);
        }

        if ($translatedOptions == $name) {
            $translatedOptions = [];

            foreach ($settingDefs['options'] as $value) {
                $translatedOptions[$value] = $value;
            }
        }

        $settingDefs['options'] = $translatedOptions;

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
            catch (Exception) {
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
            ->getRDBRepository(ScheduledJob::ENTITY_TYPE)
            ->where(['job' => 'Dummy'])
            ->findOne();

        if (!$scheduledJob) {
            return;
        }

        $this->getEntityManager()->createEntity(Job::ENTITY_TYPE, [
            'name' => 'Dummy',
            'scheduledJobId' => $scheduledJob->getId(),
        ]);
    }
}
