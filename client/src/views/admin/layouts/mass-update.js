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

class LayoutMassUpdateView extends LayoutRowsView {

    dataAttributeList = ['name']
    editable = false
    ignoreList = []
    ignoreTypeList = ['duration']

    dataAttributesDefs = {
        name: {
            readOnly: true
        },
    }

    setup() {
        super.setup();

        this.wait(true);

        this.loadLayout(() => {
            this.wait(false);
        });
    }

    loadLayout(callback) {
        this.getModelFactory().create(this.scope).then(model => {
            this.getHelper().layoutManager.getOriginal(this.scope, this.type, this.setId, (layout) => {
                const allFields = [];

                for (const field in model.defs.fields) {
                    if (
                        !model.getFieldParam(field, 'massUpdateDisabled') &&
                        !model.getFieldParam(field, 'readOnly') &&
                        !model.getFieldParam(field, 'readOnlyAfterCreate') &&
                        this.isFieldEnabled(model, field) &&
                        model.getFieldType('field') !== 'foreign'
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

                for (const i in layout) {
                    this.enabledFields.push({
                        name: layout[i],
                        labelText: this.getLanguage().translate(layout[i], 'fields', this.scope),
                    });

                    this.enabledFieldsList.push(layout[i]);
                }

                for (const i in allFields) {
                    if (!_.contains(this.enabledFieldsList, allFields[i])) {
                        this.disabledFields.push({
                            name: allFields[i],
                            labelText: this.getLanguage().translate(allFields[i], 'fields', this.scope),
                        });
                    }
                }

                this.rowLayout = this.enabledFields;

                for (const i in this.rowLayout) {
                    this.rowLayout[i].labelText = this.getLanguage()
                        .translate(this.rowLayout[i].name, 'fields', this.scope);

                    this.itemsData[this.rowLayout[i].name] = Espo.Utils.cloneDeep(this.rowLayout[i]);
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

    validate() {
        return true;
    }

    isFieldEnabled(model, name) {
        if (this.ignoreList.indexOf(name) !== -1) {
            return false;
        }

        if (this.ignoreTypeList.indexOf(model.getFieldParam(name, 'type')) !== -1) {
            return false;
        }

        const layoutList = model.getFieldParam(name, 'layoutAvailabilityList');

        if (layoutList && !layoutList.includes(this.type)) {
            return;
        }

        const layoutIgnoreList = model.getFieldParam(name, 'layoutIgnoreList') || [];

        if (layoutIgnoreList.includes(this.type)) {
            return false;
        }

        return !model.getFieldParam(name, 'disabled') &&
            !model.getFieldParam(name, 'utility') &&
            !model.getFieldParam(name, 'layoutMassUpdateDisabled') &&
            !model.getFieldParam(name, 'readOnly');
    }
}

export default LayoutMassUpdateView;
