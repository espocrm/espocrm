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

import BaseFieldView from 'views/fields/base';
import RecordModal from 'helpers/record-modal';

export default class extends BaseFieldView {

    detailTemplate = 'email/fields/create-event/detail'

    eventEntityType = 'Meeting'

    getAttributeList() {
        return [
            'icsEventData',
            'createdEventId',
        ];
    }

    setup() {
        super.setup();

        this.addActionHandler('createEvent', () => this.createEvent());
    }

    createEvent() {
        const eventData = this.model.get('icsEventData') || {};

        const attributes = Espo.Utils.cloneDeep(eventData.valueMap || {});

        attributes.parentId = this.model.get('parentId');
        attributes.parentType = this.model.get('parentType');
        attributes.parentName = this.model.get('parentName');

        this.addFromAddressToAttributes(attributes);

        const helper = new RecordModal();

        helper.showCreate(this, {
            entityType: this.eventEntityType,
            attributes: attributes,
            afterSave: async () => {
                await this.model.fetch();

                Espo.Ui.success(this.translate('Done'))
            },
        });
    }

    /**
     * @private
     * @param {Record} attributes
     */
    addFromAddressToAttributes(attributes) {
        const fromAddress = this.model.get('from');
        const idHash = this.model.get('idHash') || {};
        const typeHash = this.model.get('typeHash') || {};
        const nameHash = this.model.get('nameHash') || {};

        let fromId = null;
        let fromType = null;
        let fromName = null;

        if (!fromAddress) {
            return;
        }

        fromId = idHash[fromAddress] || null;
        fromType = typeHash[fromAddress] || null;
        fromName = nameHash[fromAddress] || null;

        const attendeeLink = this.getAttendeeLink(fromType);

        if (!attendeeLink) {
            return;
        }

        attributes[attendeeLink + 'Ids'] = attributes[attendeeLink + 'Ids'] || [];
        attributes[attendeeLink + 'Names'] = attributes[attendeeLink + 'Names'] || {};

        if (~attributes[attendeeLink + 'Ids'].indexOf(fromId)) {
            return;
        }

        attributes[attendeeLink + 'Ids'].push(fromId);
        attributes[attendeeLink + 'Names'][fromId] = fromName;
    }

    /**
     * @private
     * @param {string} entityType
     * @return {null|string}
     */
    getAttendeeLink(entityType) {
        if (entityType === 'User') {
            return 'users';
        }

        if (entityType === 'Contact') {
            return 'contacts';
        }

        if (entityType === 'Lead') {
            return 'leads';
        }

        return null;
    }
}
