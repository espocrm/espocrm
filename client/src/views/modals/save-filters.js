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

import ModalView from 'views/modal';
import Model from 'model';

class SaveFiltersModalView extends ModalView {

    template = 'modals/save-filters'

    cssName = 'save-filters'

    data() {
        return {
            dashletList: this.dashletList,
        };
    }

    setup() {
        this.buttonList = [
            {
                name: 'save',
                label: 'Save',
                style: 'primary',
            },
            {
                name: 'cancel',
                label: 'Cancel',
            },
        ];

        this.headerText = this.translate('Save Filter');

        const model = new Model();

        this.createView('name', 'views/fields/varchar', {
            selector: '.field[data-name="name"]',
            defs: {
                name: 'name',
                params: {
                    required: true
                }
            },
            mode: 'edit',
            model: model,
            labelText: this.translate('name', 'fields'),
        });
    }

    /**
     * @param {string} field
     * @return {module:views/fields/base}
     */
    getFieldView(field) {
        return this.getView(field);
    }

    actionSave() {
        const nameView = this.getFieldView('name');

        nameView.fetchToModel();

        if (nameView.validate()) {
            return;
        }

        this.trigger('save', nameView.model.get('name'));

        return true;
    }
}

export default SaveFiltersModalView;
