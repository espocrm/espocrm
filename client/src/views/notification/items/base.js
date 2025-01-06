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

/** @module views/notification/items/base */

import View from 'view';

class BaseNotificationItemView extends View {

    /** @type {string} */
    messageName
    /** @type {string} */
    messageTemplate
    messageData = null
    isSystemAvatar = false

    data() {
        return {
            avatar: this.getAvatarHtml(),
        };
    }

    init() {
        this.createField('createdAt', null, null, 'views/fields/datetime-short');

        this.messageData = {};
    }

    createField(name, type, params, view) {
        type = type || this.model.getFieldType(name) || 'base';

        this.createView(name, view || this.getFieldManager().getViewName(type), {
            model: this.model,
            defs: {
                name: name,
                params: params || {}
            },
            selector: '.cell-' + name,
            mode: 'list',
        });
    }

    createMessage() {
        const parentType = this.model.get('relatedParentType') || null;

        if (!this.messageTemplate && this.messageName) {
            this.messageTemplate = this.translate(this.messageName, 'notificationMessages', parentType) || '';
        }

        if (
            this.messageTemplate.indexOf('{entityType}') === 0 &&
            typeof this.messageData.entityType === 'string'
        ) {
            this.messageData.entityTypeUcFirst = Espo.Utils.upperCaseFirst(this.messageData.entityType);

            this.messageTemplate = this.messageTemplate.replace('{entityType}', '{entityTypeUcFirst}');
        }

        this.createView('message', 'views/stream/message', {
            messageTemplate: this.messageTemplate,
            selector: '.message',
            model: this.model,
            messageData: this.messageData,
        });
    }

    getAvatarHtml() {
        let id = this.userId;

        if (this.isSystemAvatar || !id) {
            id = this.getHelper().getAppParam('systemUserId');
        }

        return this.getHelper().getAvatarHtml(id, 'small', 20);
    }

    /**
     * @param {string} entityType
     * @param {boolean} [isPlural]
     * @return {string}
     */
    translateEntityType(entityType, isPlural) {
        let string = isPlural ?
            (this.translate(entityType, 'scopeNamesPlural') || '') :
            (this.translate(entityType, 'scopeNames') || '');

        string = string.toLowerCase();

        if (this.toUpperCaseFirstLetter()) {
            string = Espo.Utils.upperCaseFirst(string);
        }

        return string;
    }

    /**
     * @property
     * @return {boolean}
     */
    toUpperCaseFirstLetter() {
        const language = this.getPreferences().get('language') || this.getConfig().get('language');

        return ['de_DE', 'nl_NL'].includes(language);
    }
}

export default BaseNotificationItemView;
