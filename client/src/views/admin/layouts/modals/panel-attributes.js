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

import ModalView from 'views/modal';
import Model from 'model';

export default class LayoutPanelAttributesView extends ModalView {

    templateContent = `
        <div class="panel panel-default no-side-margin">
            <div class="panel-body">
                <div class="edit-container">{{{edit}}}</div>
            </div>
        </div>
    `

    className = 'dialog dialog-record'

    shortcutKeys = {
        /** @this LayoutPanelAttributesView */
        'Control+Enter': function (e) {
            if (document.activeElement instanceof HTMLInputElement) {
                document.activeElement.dispatchEvent(new Event('change', {bubbles: true}));
            }

            this.actionSave();

            e.preventDefault();
            e.stopPropagation();
        },
    }

    setup() {
        this.buttonList = [
            {
                name: 'save',
                text: this.translate('Apply'),
                style: 'primary',
            },
            {
                name: 'cancel',
                label: 'Cancel',
            },
        ];

        const model = new Model();

        model.name = 'LayoutManager';
        model.set(this.options.attributes || {});

        const attributeList = this.options.attributeList;
        const attributeDefs = this.options.attributeDefs;

        this.createView('edit', 'views/admin/layouts/record/edit-attributes', {
            selector: '.edit-container',
            attributeList: attributeList,
            attributeDefs: attributeDefs,
            model: model,
            dynamicLogicDefs: this.options.dynamicLogicDefs,
        });
    }

    actionSave() {
        const editView = /** @type {import('views/record/edit').default} */
            this.getView('edit');

        const attrs = editView.fetch();

        editView.model.set(attrs, {silent: true});

        if (editView.validate()) {
            return;
        }

        const attributes = editView.model.attributes;

        this.trigger('after:save', attributes);

        return true;
    }
}
