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
import Metadata from 'metadata';

// noinspection JSUnusedGlobalSymbols
export default class LockActionHandler extends ActionHandler {

    /**
     * @type {AclManager}
     */
    @inject(AclManager)
    acl

    /**
     * @type {Metadata}
     */
    @inject(Metadata)
    metadata

    async actionLock() {
        const model = this.view.model;

        Espo.Ui.notifyWait();

        let attributes;

        try {
            attributes = await Espo.Ajax.postRequest('Action', {
                action: 'lock',
                entityType: model.entityType,
                id: model.id,
            });
        } catch (e) {
            return;
        }

        model.setMultiple(attributes, {sync: true});
        model.trigger('sync', this.model, null, {});

        Espo.Ui.success(this.view.translate('locked', 'messages'));
    }

    async actionUnlock() {
        const model = this.view.model;

        Espo.Ui.notifyWait();

        let attributes;

        try {
            attributes = await Espo.Ajax.postRequest('Action', {
                action: 'unlock',
                entityType: model.entityType,
                id: model.id,
            });
        } catch (e) {
            return;
        }

        model.setMultiple(attributes, {sync: true});
        model.trigger('sync', this.model, null, {});

        Espo.Ui.success(this.view.translate('unlocked', 'messages'));
    }

    // noinspection JSUnusedGlobalSymbols
    /**
     * @return {boolean}
     */
    canBeLocked() {
        const model = this.view.model;

        if (!this.isEnabled(model.entityType)) {
            return false;
        }

        if (this.acl.getPermissionLevel('lock') !== 'yes') {
            return false;
        }

        if (model.attributes.isLocked) {
            return false;
        }

        return true;
    }

    // noinspection JSUnusedGlobalSymbols
    /**
     * @return {boolean}
     */
    canBeUnlocked() {
        const model = this.view.model;

        if (!this.isEnabled(model.entityType)) {
            return false;
        }

        if (this.acl.getPermissionLevel('lock') !== 'yes') {
            return false;
        }

        if (!model.attributes.isLocked) {
            return false;
        }

        return true;
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
