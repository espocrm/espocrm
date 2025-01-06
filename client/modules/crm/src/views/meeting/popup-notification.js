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

import PopupNotificationView from 'views/popup-notification';

class MeetingPopupNotificationView extends PopupNotificationView {

    template = 'crm:meeting/popup-notification'

    type = 'event'
    style = 'primary'
    closeButton = true

    setup() {
        if (!this.notificationData.entityType) {
            return;
        }

        const promise = this.getModelFactory().create(this.notificationData.entityType, model => {
            const field = this.notificationData.dateField;
            const fieldType = model.getFieldParam(field, 'type') || 'base';
            const viewName = this.getFieldManager().getViewName(fieldType);

            model.set(this.notificationData.attributes);

            this.createView('date', viewName, {
                model: model,
                mode: 'detail',
                selector: `.field[data-name="${field}"]`,
                name: field,
                readOnly: true,
            });
        });

        this.wait(promise);
    }

    data() {
        return {
            header: this.translate(this.notificationData.entityType, 'scopeNames'),
            dateField: this.notificationData.dateField,
            ...super.data(),
        };
    }

    onCancel() {
        Espo.Ajax.postRequest('Activities/action/removePopupNotification', {id: this.notificationId});
    }
}

export default MeetingPopupNotificationView;
