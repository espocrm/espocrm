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

import View from 'view';
import Select from 'ui/select';
import Model from 'model';

export default class DynamicLogicConditionFieldTypeBaseView extends View {

    template = 'admin/dynamic-logic/conditions/field-types/base'

    /**
     * @protected
     * @type {Record}
     */
    itemData

    /**
     * @protected
     * @type {Record}
     */
    additionalData

    /**
     * @type {string}
     */
    type

    /**
     * @type {string}
     */
    field

    /**
     * @type {string}
     */
    scope

    /**
     * @type {string[]}
     */
    typeList

    /**
     * @type {Model}
     */
    baseModel

    events = {
        'click > div > div > [data-action="remove"]': function (e) {
            e.stopPropagation();

            this.trigger('remove-item');
        },
    }

    data() {
        return {
            type: this.type,
            field: this.field,
            scope: this.scope,
            typeList: this.typeList,
            leftString: this.translateLeftString(),
        };
    }

    translateLeftString() {
        return this.translate(this.field, 'fields', this.scope);
    }

    setup() {
        this.type = this.options.type;
        this.field = this.options.field;
        this.scope = this.options.scope;
        this.fieldType = this.options.fieldType;

        this.itemData = this.options.itemData;
        this.additionalData = (this.itemData.data || {});

        this.typeList = this.getMetadata().get(`clientDefs.DynamicLogic.fieldTypes.${this.fieldType}.typeList`);

        this.baseModel = new Model();

        this.wait(true);

        this.createModel().then(model => {
            this.model = model;

            this.populateValues();
            this.manageValue();

            this.wait(false);
        });
    }

    /**
     * @return {Promise<Model>}
     */
    async createModel() {
        return this.getModelFactory().create(this.scope);
    }

    afterRender() {
        this.$type = this.$el.find('select[data-name="type"]');

        Select.init(this.$type.get(0));

        this.$type.on('change', () => {
            this.type = this.$type.val();

            this.manageValue();
        });
    }

    populateValues() {
        if (this.getValueType() === 'varchar-matches') {
            if (this.itemData.attribute) {
                this.baseModel.set(this.itemData.attribute, this.itemData.value);
            }

            return;
        }

        if (this.itemData.attribute) {
            this.model.set(this.itemData.attribute, this.itemData.value);
        }

        this.model.set(this.additionalData.values || {});
    }

    /**
     * @return {string}
     */
    getValueViewName() {
        const fieldType = this.getMetadata().get(`entityDefs.${this.scope}.fields.${this.field}.type`) || 'base';

        return this.getMetadata().get(`entityDefs.${this.scope}.fields.${this.field}.view`) ||
            this.getFieldManager().getViewName(fieldType);
    }

    /**
     * @return {string}
     */
    getValueFieldName() {
        return this.field;
    }

    /**
     * @return {string}
     */
    getValueType() {
        return this.getMetadata()
                .get(`clientDefs.DynamicLogic.fieldTypes.${this.fieldType}.conditionTypes.${this.type}.valueType`) ||
            this.getMetadata()
                .get(`clientDefs.DynamicLogic.conditionTypes.${this.type}.valueType`);
    }

    manageValue() {
        const valueType = this.getValueType();

        if (valueType === 'field') {
            const viewName = this.getValueViewName();
            const fieldName = this.getValueFieldName();

            this.createView('value', viewName, {
                model: this.model,
                name: fieldName,
                selector: '.value-container',
                mode: 'edit',
                readOnlyDisabled: true,
            }, view => {
                if (this.isRendered()) {
                    view.render();
                }
            });

            return;
        }

        if (valueType === 'custom') {
            this.clearView('value');

            const methodName = 'createValueView' + Espo.Utils.upperCaseFirst(this.type);

            this[methodName]();

            return;
        }

        if (valueType === 'varchar') {
            this.createView('value', 'views/fields/varchar', {
                model: this.model,
                name: this.getValueFieldName(),
                selector: '.value-container',
                mode: 'edit',
                readOnlyDisabled: true,
            }, view => {
                if (this.isRendered()) {
                    view.render();
                }
            });

            return;
        }

        if (valueType === 'varchar-matches') {
            this.createView('value', 'views/fields/varchar', {
                model: this.baseModel,
                name: this.getValueFieldName(),
                selector: '.value-container',
                mode: 'edit',
                readOnlyDisabled: true,
            }, view => {
                if (this.isRendered()) {
                    view.render();
                }
            });

            return;
        }

        this.clearView('value');
    }

    /**
     * @return {import('views/fields/base')}
     */
    getValueView() {
        return this.getView('value');
    }

    fetch() {
        const valueView = this.getValueView();

        const model = this.getValueType() === 'varchar-matches' ? this.baseModel : this.model;

        const item = {
            type: this.type,
            attribute: this.field,
        };

        if (valueView) {
            valueView.fetchToModel();

            item.value = model.get(this.field);
        }

        return item;
    }
}
