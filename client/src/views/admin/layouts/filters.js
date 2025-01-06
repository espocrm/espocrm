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

import LayoutRowsView from 'views/admin/layouts/rows';

class LayoutFiltersView extends LayoutRowsView {

    dataAttributeList = ['name']
    editable = false
    ignoreList = []

    setup() {
        super.setup();

        this.wait(true);

        this.loadLayout(() => this.wait(false));
    }

    loadLayout(callback) {
        this.getModelFactory().create(this.scope, model => {
            this.getHelper().layoutManager.getOriginal(this.scope, this.type, this.setId, layout => {
                const allFields = [];

                for (const field in model.defs.fields) {
                    if (
                        this.checkFieldType(model.getFieldParam(field, 'type')) &&
                        this.isFieldEnabled(model, field)
                    ) {
                        allFields.push(field);
                    }
                }

                allFields.sort((v1, v2) => {
                    return this.translate(v1, 'fields', this.scope)
                        .localeCompare(this.translate(v2, 'fields', this.scope));
                });

                this.enabledFieldsList = [];
                this.enabledFields = [];
                this.disabledFields = [];

                for (const item of layout) {
                    this.enabledFields.push({
                        name: item,
                        labelText: this.getLanguage().translate(item, 'fields', this.scope)
                    });

                    this.enabledFieldsList.push(item);
                }

                for (const item of allFields) {
                    if (!this.enabledFieldsList.includes(item)) {
                        this.disabledFields.push({
                            name: item,
                            labelText: this.getLanguage().translate(item, 'fields', this.scope)
                        });
                    }
                }

                /** @type {Object[]} */
                this.rowLayout = this.enabledFields;

                for (const item of this.rowLayout) {
                    item.labelText = this.getLanguage().translate(item.name, 'fields', this.scope);
                }

                callback();
            });
        });
    }

    fetch() {
        const layout = [];

        $("#layout ul.enabled > li").each((i, el) => {
            layout.push($(el).data('name'));
        });

        return layout;
    }

    checkFieldType(type) {
        return this.getFieldManager().checkFilter(type);
    }

    validate() {
        return true;
    }

    isFieldEnabled(model, name) {
        if (this.ignoreList.indexOf(name) !== -1) {
            return false;
        }

        return !model.getFieldParam(name, 'disabled') &&
            !model.getFieldParam(name, 'utility') &&
            !model.getFieldParam(name, 'layoutFiltersDisabled');
    }
}

export default LayoutFiltersView;
