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

import ModalView from 'views/modal';
import Model from 'model';
import VarcharFieldView from 'views/fields/varchar';

class SaveFiltersModalView extends ModalView {


    // language=Handlebars
    templateContent = `
        <div class="panel panel-default no-side-margin">
            <div class="panel-body">
                <div class="cell form-group" data-name="name">
                    <label
                        class="control-label"
                        data-name="name"
                    >{{translate 'name' category='fields'}}</label>
                    <div class="field" data-name="name">{{{nameField}}}</div>
                </div>
            </div>
        </div>
    `

    cssName = 'save-filters'

    /**
     * @private
     * @type {VarcharFieldView}
     */
    nameFieldView

    data() {
        return {
            dashletList: this.dashletList,
        };
    }

    setup() {
        this.shortcutKeys = {
            'Control+Enter': (e) => {
                e.preventDefault();
                e.stopPropagation();

                this.actionSave();
            },
        };

        this.buttonList = [
            {
                name: 'save',
                label: 'Save',
                style: 'primary',
                onClick: () => this.actionSave(),
            },
            {
                name: 'cancel',
                label: 'Cancel',
                onClick: () => this.actionCancel(),
            },
        ];

        this.headerText = this.translate('Save Filter');

        this.formModel = new Model();

        this.nameFieldView = new VarcharFieldView({
            name: 'name',
            params: {
                required: true
            },
            mode: 'edit',
            model: this.formModel,
            labelText: this.translate('name', 'fields'),
        })

        this.assignView('nameField', this.nameFieldView);
    }

    afterRender() {
        setTimeout(() => {
            this.nameFieldView.element.querySelector('input')?.focus();
        }, 1);
    }

    actionSave() {
        this.fetchToModel();

        if (this.nameFieldView.validate()) {
            return;
        }

        this.trigger('save', this.formModel.attributes.name);

        return true;
    }

    /**
     * @private
     */
    fetchToModel() {
        this.formModel.setMultiple({
            ...this.nameFieldView.fetch(),
        });
    }
}

export default SaveFiltersModalView;
