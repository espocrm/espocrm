<?php
/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
 * Website: https://www.espocrm.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU Affero General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU Affero General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

use Doctrine\DBAL\Exception as DBALException;
use Espo\Core\Application;
use Espo\Core\Container;
use Espo\Core\DataManager;
use Espo\Core\Exceptions\Error;
use Espo\Core\InjectableFactory;
use Espo\Core\ORM\DatabaseParamsFactory;
use Espo\Core\Utils\Database\ConfigDataProvider;
use Espo\Core\Utils\Database\Dbal\ConnectionFactoryFactory;
use Espo\Core\Utils\Id\RecordIdGenerator;
use Espo\Core\Utils\ScheduledJob as ScheduledJobUtil;
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
use Espo\Tools\Installer\DatabaseConfigDataProvider;

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
        'outboundEmailFromName',
        'outboundEmailFromAddress',
        'outboundEmailIsShared',
        'theme',
    ];

    public function __construct(
        private Application\ApplicationParams $applicationParams = new Application\ApplicationParams(),
    ) {
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

        $injectableFactory = (new Application($this->applicationParams))
            ->getContainer()
            ->getByClass(InjectableFactory::class);

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

        $this->app = new Application($this->applicationParams);
    }

    private function getContainer(): Container
    {
        return $this->app->getContainer();
    }

    private function getEntityManager(): EntityManager
    {
        return $this->getContainer()->getByClass(EntityManager::class);
    }

    public function getMetadata(): Metadata
    {
        return $this->app->getContainer()->getByClass(Metadata::class);
    }

    public function getInjectableFactory(): InjectableFactory
    {
        return $this->app->getContainer()->getByClass(InjectableFactory::class);
    }

    public function getConfig(): Config
    {
        return $this->app->getContainer()->getByClass(Config::class);
    }

    public function createConfigWriter(): ConfigWriter
    {
        return $this->getInjectableFactory()->create(ConfigWriter::class);
    }

    private function getSystemHelper(): SystemHelper
    {
        return $this->systemHelper;
    }

    private function getInstallerConfig(): InstallerConfig
    {
        return $this->installerConfig;
    }

    private function getFileManager(): FileManager
    {
        return $this->app->getContainer()->getByClass(FileManager::class);
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
                $language = $this->app->getContainer()->get('defaultLanguage');

                if (!$language instanceof Language) {
                    throw new RuntimeException("Can't get default language.");
                }

                $this->language = $language;
            } catch (Throwable $e) {
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
            'Light',
            'Glass',
            'Violet',
            'Sakura',
            'Hazyblue',
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
        $platform = $additionalData['databaseParams']['platform'] ?? 'Mysql';

        $dbConfigDataProvider = new DatabaseConfigDataProvider($platform);

        /** @var SystemRequirements $systemRequirementManager */
        $systemRequirementManager = $this->app
            ->getContainer()
            ->getByClass(InjectableFactory::class)
            ->createWithBinding(
                SystemRequirements::class,
                BindingContainerBuilder::create()
                    ->bindInstance(ConfigDataProvider::class, $dbConfigDataProvider)
                    ->build()
            );

        return $systemRequirementManager->getRequiredListByType($type, $requiredOnly, $additionalData);
    }

    /**
     * @throws DBALException
     * @throws Exception
     */
    public function checkDatabaseConnection(array $rawParams, bool $createDatabase = false): void
    {
        $params = $this->databaseParamsFactory->createWithMergedAssoc($rawParams);

        $dbname = $params->getName();

        try {
            $this->databaseHelper->createPDO($params);
        } catch (Exception $e) {
            if (!$createDatabase) {
                throw $e;
            }

            if ((int) $e->getCode() !== 1049) {
                throw $e;
            }

            /** @noinspection RegExpRedundantEscape */
            if ($dbname !== preg_replace('/[^A-Za-z0-9_\-@$#\(\)]+/', '', $dbname)) {
                throw new Exception("Bad database name.");
            }

            $params = $params->withName(null);

            $pdo = $this->databaseHelper->createPDO($params);

            $connectionFactoryFactory = $this->getInjectableFactory()->create(ConnectionFactoryFactory::class);

            $connection = $connectionFactoryFactory
                ->create($params->getPlatform(), $pdo)
                ->create($params);

            $schemaManager = $connection->createSchemaManager();
            $platform = $connection->getDatabasePlatform();

            $schemaManager->createDatabase($platform->quoteIdentifier($dbname));

             $this->checkDatabaseConnection($rawParams);
        }
    }

    /**
     * Save data.
     *
     * @param array<string, mixed> $saveData
     *     [
     *       'driver' => 'pdo_mysql',
     *       'host' => 'localhost',
     *       'dbname' => 'espocrm_test',
     *       'user' => 'root',
     *       'password' => '',
     *     ]
     * @return bool
     */
    public function saveData(array $saveData)
    {
        $initData = include('install/core/afterInstall/config.php');

        $databaseDefaults = $this->app
            ->getContainer()
            ->getByClass(Config::class)
            ->get('database');

        $siteUrl = !empty($saveData['siteUrl']) ? $saveData['siteUrl'] : $this->getSystemHelper()->getBaseUrl();

        $data = [
            'database' => array_merge($databaseDefaults, $saveData['database']),
            'language' => $saveData['language'] ?? 'en_US',
            'siteUrl' => $siteUrl,
            'cryptKey' => Util::generateSecretKey(),
            'hashSecretKey' => Util::generateSecretKey(),
            'theme' => $saveData['theme'] ?? 'Espo',
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

    /**
     * @throws Error
     */
    public function rebuild(): void
    {
        try {
            $this->app->getContainer()->getByClass(DataManager::class)->rebuild();
        } catch (Exception) {
            $this->auth();

            $this->app->getContainer()->getByClass(DataManager::class)->rebuild();
        }
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

        return $this->saveConfig($preferences);
    }

    private function createRecords(): void
    {
        $records = include('install/core/afterInstall/records.php');

        foreach ($records as $entityName => $recordList) {
            foreach ($recordList as $data) {
                $this->createRecord($entityName, $data);
            }
        }
    }

    private function createRecord(string $entityType, array $data): void
    {
        $id = $data['id'] ?? null;

        $entity = null;

        $em = $this->getEntityManager();

        if ($id) {
            $entity = $em->getEntityById($entityType, $id);

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

        return true;
    }

    public function checkPermission(): bool
    {
        return $this->getFileManager()->getPermissionUtils()->setMapPermission();
    }

    public function getLastPermissionError()
    {
        return $this->getFileManager()->getPermissionUtils()->getLastErrorRules();
    }

    public function setSuccess(): void
    {
        $this->auth();
        $this->createRecords();
        $this->executeFinalScript();

        $installerConfig = $this->getInstallerConfig();
        $installerConfig->set('isInstalled', true);
        $installerConfig->save();

        $configWriter = $this->createConfigWriter();
        $configWriter->set('isInstalled', true);
        $configWriter->save();
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
                case 'enum':
                    if (
                        isset($paramDefs['options']) && array_key_exists($value, $paramDefs['options']) ||
                        !isset($paramDefs['options'])
                    ) {
                        $normalizedParams[$name] = $value;
                    } else if (array_key_exists('default', $paramDefs)) {
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
                case 'enumInt':
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

    public function getCronMessage(): array
    {
        return $this->getInjectableFactory()
            ->create(ScheduledJobUtil::class)
            ->getSetupMessage();
    }

    private function executeFinalScript(): void
    {
        $this->prepareDummyJob();
    }

    private function prepareDummyJob(): void
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

    public function getLogoSrc(string $theme): string
    {
        return $this->getMetadata()->get(['themes', $theme, 'logo']) ?? 'client/img/logo.svg';
    }
}
