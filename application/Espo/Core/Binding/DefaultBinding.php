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

namespace Espo\Core\Binding;

class DefaultBinding implements BindingProcessor
{
    public function process(Binder $binder): void
    {
        $binder->bindService(
            'Espo\\Core\\InjectableFactory',
            'injectableFactory'
        );

        $binder->bindService(
            'Espo\\Core\\Container',
            'container'
        );

        $binder->bindService(
            'Espo\\Core\\Container\\Container',
            'container'
        );

        $binder->bindService(
            'Espo\\Core\\Utils\\Module',
            'module'
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
            'Espo\\Core\\SelectBuilderFactory',
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
            'Espo\\Core\\Record\\HookManager',
            'recordHookManager'
        );

        $binder->bindService(
            'Espo\\Core\\Utils\\HookManager',
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
            'Espo\\Core\\Utils\\Language $baseLanguage',
            'baseLanguage'
        );

        $binder->bindService(
            'Espo\\Core\\Utils\\Language $defaultLanguage',
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
            'Espo\\Core\\AclManager $internalAclManager',
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
            'Espo\\Core\\Acl',
            'acl'
        );

        $binder->bindService(
            'Espo\\Core\\ExternalAccount\\ClientManager',
            'externalAccountClientManager'
        );

        $binder->bindImplementation(
            'Espo\\Core\\Acl\\Table\\TableFactory',
            'Espo\\Core\\Acl\\Table\\DefaultTableFactory'
        );

        $binder->bindImplementation(
            'Espo\\Core\\Utils\\Id\\RecordIdGenerator',
            'Espo\\Core\\Utils\\Id\\DefaultRecordIdGenerator'
        );

        $binder->bindFactory(
            'Espo\\Core\\WebSocket\\Subscriber',
            'Espo\\Core\\WebSocket\\SubscriberFactory'
        );

        $binder->bindFactory(
            'Espo\\Core\\WebSocket\\Sender',
            'Espo\\Core\\WebSocket\\SenderFactory'
        );

        $binder->bindFactory(
            'Espo\\Core\\Sms\\Sender',
            'Espo\\Core\\Sms\\SenderFactory'
        );
    }
}
