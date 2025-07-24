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

namespace Espo;

use Espo\Core\Binding\Binder;
use Espo\Core\Binding\BindingProcessor;
use Espo\Core\Binding\Key\NamedClassKey;

/**
 * Default binding for the dependency injection framework. Custom binding should be set up in
 * `Espo\Modules\{ModuleName}\Binding` or `Espo\Custom\Binding`.
 *
 * @link https://docs.espocrm.com/development/di/#binding.
 */
class Binding implements BindingProcessor
{
    public function process(Binder $binder): void
    {
        $this->bindServices($binder);
        $this->bindCore($binder);
        $this->bindMisc($binder);
        $this->bindAcl($binder);
        $this->bindWebSocket($binder);
        $this->bindEmailAccount($binder);
    }

    private function bindServices(Binder $binder): void
    {
        $binder->bindService(
            'Espo\\Core\\Application\\ApplicationParams',
            'applicationParams'
        );

        $binder->bindService(
            'Espo\\Core\\InjectableFactory',
            'injectableFactory'
        );

        $binder->bindService(
            'Espo\\Core\\Container',
            'container'
        );

        $binder->bindService(
            'Psr\\Container\\ContainerInterface',
            'container'
        );

        $binder->bindService(
            'Espo\\Core\\Utils\\Module',
            'module'
        );

        $binder->bindService(
            'Espo\\Core\\Utils\\Config',
            'config'
        );

        $binder->bindService(
            'Espo\\Core\\Utils\\File\\Manager',
            'fileManager'
        );

        $binder->bindService(
            'Espo\\ORM\\EntityManager',
            'entityManager'
        );

        $binder->bindService(
            'Espo\\Core\\ORM\\EntityManager',
            'entityManager'
        );

        $binder->bindService(
            'Espo\\ORM\\Defs',
            'ormDefs'
        );

        $binder->bindService(
            'Espo\\Core\\DataManager',
            'dataManager'
        );

        $binder->bindService(
            'Espo\\Core\\Utils\\Metadata',
            'metadata'
        );

        $binder->bindService(
            'Espo\\Core\\Utils\\Log',
            'log'
        );

        $binder->bindService(
            'Espo\\Core\\ApplicationState',
            'applicationState'
        );

        $binder->bindService(
            'Espo\\Core\\ApplicationUser',
            'applicationUser'
        );

        $binder->bindService(
            'Espo\\Core\\Authentication\\AuthToken\\Manager',
            'authTokenManager'
        );

        $binder->bindService(
            'Espo\\Core\\Select\\SelectBuilderFactory',
            'selectBuilderFactory'
        );

        $binder->bindService(
            'Espo\\Core\\ServiceFactory',
            'serviceFactory'
        );

        $binder->bindService(
            'Espo\\Core\\Record\\ServiceContainer',
            'recordServiceContainer'
        );

        $binder->bindService(
            'Espo\\Core\\HookManager',
            'hookManager'
        );

        $binder->bindService(
            'Espo\\Core\\Utils\\NumberUtil',
            'number'
        );

        $binder->bindService(
            'Espo\\Core\\Utils\\DateTime',
            'dateTime'
        );

        $binder->bindService(
            'Espo\\Core\\Utils\\FieldUtil',
            'fieldUtil'
        );

        $binder->bindService(
            'Espo\\Core\\Mail\\EmailSender',
            'emailSender'
        );

        $binder->bindService(
            NamedClassKey::create('Espo\\Core\\Utils\\Language', 'baseLanguage'),
            'baseLanguage'
        );

        $binder->bindService(
            NamedClassKey::create('Espo\\Core\\Utils\\Language', 'defaultLanguage'),
            'defaultLanguage'
        );

        $binder->bindService(
            'Espo\\Core\\Utils\\Language',
            'language'
        );

        $binder->bindService(
            'Espo\\Core\\Formula\\Manager',
            'formulaManager'
        );

        $binder->bindService(
            NamedClassKey::create('Espo\\Core\\AclManager', 'internalAclManager'),
            'internalAclManager'
        );

        $binder->bindService(
            'Espo\\Core\\AclManager',
            'aclManager'
        );

        $binder->bindService(
            'Espo\\Core\\Acl',
            'acl'
        );

        $binder->bindService(
            'Espo\\Entities\\Preferences',
            'preferences'
        );

        $binder->bindService(
            'Espo\\Entities\\User',
            'user'
        );

        $binder->bindService(
            'Espo\\Core\\Utils\\ClientManager',
            'clientManager'
        );

        $binder->bindService(
            'Espo\\Core\\ExternalAccount\\ClientManager',
            'externalAccountClientManager'
        );

        $binder->bindService(
            'Espo\\Core\\WebSocket\\Submission',
            'webSocketSubmission'
        );

        $binder->bindService(
            'Espo\\Tools\\Stream\\Service',
            'streamService'
        );

        $binder->bindService(
            'Espo\\Core\\Utils\\Config\\SystemConfig',
            'systemConfig'
        );

        $binder->bindService(
            'Espo\\Core\\Utils\\Config\\ApplicationConfig',
            'applicationConfig'
        );
    }

    private function bindCore(Binder $binder): void
    {
        $binder->bindImplementation(
            'Espo\\ORM\\PDO\\PDOProvider',
            'Espo\\ORM\\PDO\\DefaultPDOProvider'
        );

        $binder->bindImplementation(
            'Espo\\Core\\Utils\\Database\\ConfigDataProvider',
            'Espo\\Core\\Utils\\Database\\DefaultConfigDataProvider'
        );

        $binder->bindImplementation(
            'Espo\\Core\\Job\\JobScheduler\\Creator',
            'Espo\\Core\\Job\\JobScheduler\\Creators\\EntityCreator',
        );
    }

    private function bindMisc(Binder $binder): void
    {
        $binder->bindImplementation(
            'Espo\\Core\\Utils\\Id\\RecordIdGenerator',
            'Espo\\Core\\Utils\\Id\\DefaultRecordIdGenerator'
        );

        $binder->bindFactory(
            'Espo\\Core\\Sms\\Sender',
            'Espo\\Core\\Sms\\SenderFactory'
        );

        $binder->bindImplementation(
            'Espo\\Core\\Authentication\\Jwt\\KeyFactory',
            'Espo\\Core\\Authentication\\Jwt\\DefaultKeyFactory'
        );

        $binder
            ->for('Espo\\Core\\Authentication\\Oidc\\TokenValidator')
            ->bindImplementation(
                'Espo\\Core\\Authentication\\Jwt\\SignatureVerifierFactory',
                'Espo\\Core\\Authentication\\Oidc\\DefaultSignatureVerifierFactory'
            );

        $binder
            ->for('Espo\\Core\\Authentication\\Oidc\\Login')
            ->bindImplementation(
                'Espo\\Core\\Authentication\\Oidc\\UserProvider',
                'Espo\\Core\\Authentication\\Oidc\\UserProvider\\DefaultUserProvider'
            );

        $binder->bindImplementation(
            'Espo\\Core\\Mail\\Importer\\ParentFinder',
            'Espo\\Core\\Mail\\Importer\\DefaultParentFinder'
        );

        $binder->bindImplementation(
            'Espo\\Core\\Mail\\Importer\\DuplicateFinder',
            'Espo\\Core\\Mail\\Importer\\DefaultDuplicateFinder'
        );

        $binder->bindImplementation(
            'Espo\\Tools\\Api\\Cors\\Helper',
            'Espo\\Tools\\Api\\Cors\\DefaultHelper'
        );

        $binder->bindImplementation(
            'Espo\\Core\\Record\\ActionHistory\\ActionLogger',
            'Espo\\Core\\Record\\ActionHistory\\DefaultActionLogger'
        );

        $binder->bindImplementation(
            'Espo\\Core\\Mail\\Importer',
            'Espo\\Core\\Mail\\Importer\\DefaultImporter'
        );
    }

    private function bindAcl(Binder $binder): void
    {
        $binder->bindImplementation(
            'Espo\\Core\\Acl\\Table\\TableFactory',
            'Espo\\Core\\Acl\\Table\\DefaultTableFactory'
        );
    }

    private function bindWebSocket(Binder $binder): void
    {
        $binder->bindFactory(
            'Espo\\Core\\WebSocket\\Subscriber',
            'Espo\\Core\\WebSocket\\SubscriberFactory'
        );

        $binder->bindFactory(
            'Espo\\Core\\WebSocket\\Sender',
            'Espo\\Core\\WebSocket\\SenderFactory'
        );
    }

    private function bindEmailAccount(Binder $binder): void
    {
        $binder
            ->for('Espo\\Core\\Mail\\Account\\PersonalAccount\\Service')
            ->bindFactory(
                'Espo\\Core\\Mail\\Account\\Fetcher',
                'Espo\\Core\\Mail\\Account\\PersonalAccount\\FetcherFactory'
            )
            ->bindImplementation(
                'Espo\\Core\\Mail\\Account\\StorageFactory',
                'Espo\\Core\\Mail\\Account\\PersonalAccount\\StorageFactory'
            );

        $binder
            ->for('Espo\\Core\\Mail\\Account\\GroupAccount\\Service')
            ->bindFactory(
                'Espo\\Core\\Mail\\Account\\Fetcher',
                'Espo\\Core\\Mail\\Account\\GroupAccount\\FetcherFactory'
            )
            ->bindImplementation(
                'Espo\\Core\\Mail\\Account\\StorageFactory',
                'Espo\\Core\\Mail\\Account\\GroupAccount\\StorageFactory'
            );
    }
}
