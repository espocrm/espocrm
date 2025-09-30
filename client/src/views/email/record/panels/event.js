/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

import SidePanelView from 'views/record/panels/side';

export default class extends SidePanelView {

    setupFields() {
        super.setupFields();

        this.fieldList.push({
            name: 'icsEventDateStart',
            readOnly: true,
            labelText: this.translate('dateStart', 'fields', 'Meeting'),
        });

        this.fieldList.push({
            name: 'createdEvent',
            readOnly: true,
        });

        this.fieldList.push({
            name: 'createEvent',
            readOnly: true,
            noLabel: true,
        });

        this.controlEventField();

        this.listenTo(this.model, 'change:icsEventData', this.controlEventField, this);
        this.listenTo(this.model, 'change:createdEventId', this.controlEventField, this);
    }

    /**
     * @private
     */
    controlEventField() {
        if (!this.model.get('icsEventData')) {
            this.recordViewObject.hideField('createEvent');
            this.recordViewObject.showField('createdEvent');

            return;
        }

        const eventData = this.model.get('icsEventData');

        if (eventData.createdEvent) {
            this.recordViewObject.hideField('createEvent');
            this.recordViewObject.showField('createdEvent');

            return;
        }

        if (!this.model.get('createdEventId')) {
            this.recordViewObject.hideField('createdEvent');
            this.recordViewObject.showField('createEvent');

            return;
        }

        this.recordViewObject.hideField('createEvent');
        this.recordViewObject.showField('createdEvent');
    }
}
