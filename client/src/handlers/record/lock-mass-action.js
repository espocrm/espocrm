/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2026 EspoCRM, Inc.
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

import ActionHandler from 'action-handler';
import {inject} from 'di';
import AclManager from 'acl-manager';
import Language from 'language';
import MassActionHelper from 'helpers/mass-action';
import Metadata from 'metadata';

// noinspection JSUnusedGlobalSymbols
export default class LockMassActionHandler extends ActionHandler {

    /**
     * @type {AclManager}
     */
    @inject(AclManager)
    acl

    /**
     * @type {Language}
     */
    @inject(Language)
    language

    /**
     * @type {Metadata}
     */
    @inject(Metadata)
    metadata

    // noinspection JSUnusedGlobalSymbols
    async actionLock() {
        const msg = this.language.translate('confirmMassLock', 'messages');

        await this.view.confirm(msg);

        await this.process('lock');
    }

    // noinspection JSUnusedGlobalSymbols
    async actionUnlock() {
        const msg = this.language.translate('confirmMassUnlock', 'messages');

        await this.view.confirm(msg);

        await this.process('unlock');
    }

    /**
     * @private
     * @param {string} action
     */
    async process(action) {
        const helper = new MassActionHelper(this.view);
        const params = this.view.getMassActionSelectionPostData();
        const idle = !!params.searchParams && helper.checkIsIdle(this.view.collection.total);

        const onDone = count => {
            const labelKey = action === 'lock' ? 'massLockDone': 'massUnlockDone';

            const msg = this.view.translate(labelKey, 'messages')
                .replace('{count}', count.toString());

            Espo.Ui.success(msg);
        };

        Espo.Ui.notifyWait();

        const result = await Espo.Ajax.postRequest('MassAction', {
            entityType: this.view.entityType,
            action: action,
            params: params,
            idle: idle,
        });

        if (result.id) {
            const view = await helper.process(result.id, action)

            this.view.listenToOnce(view, 'close:success', result => onDone(result.count));

            return;
        }

        onDone(result.count);
    }

    // noinspection JSUnusedGlobalSymbols
    initLock() {
        if (
            !this.view.collection ||
            !this.isEnabled(this.view.collection.entityType) ||
            this.acl.getPermissionLevel('massUpdate') !== 'yes' ||
            this.acl.getPermissionLevel('lock') !== 'yes'
        ) {
            this.view.removeMassAction('lock');
        }
    }

    // noinspection JSUnusedGlobalSymbols
    initUnlock() {
        if (
            !this.view.collection ||
            !this.isEnabled(this.view.collection.entityType) ||
            this.acl.getPermissionLevel('massUpdate') !== 'yes' ||
            this.acl.getPermissionLevel('lock') !== 'yes'
        ) {
            this.view.removeMassAction('unlock');
        }
    }

    /**
     * @private
     * @param {string} entityType
     * @return {boolean}
     */
    isEnabled(entityType) {
        return this.metadata.get(`scopes.${entityType}.lockable`) === true;
    }
}
