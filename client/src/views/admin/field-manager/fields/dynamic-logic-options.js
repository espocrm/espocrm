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
import Model from 'model';

export default class extends BaseFieldView {

    editTemplate = 'admin/field-manager/fields/dynamic-logic-options/edit'

    data() {
        return {
            itemDataList: this.itemDataList,
        };
    }

    setup() {
        this.addActionHandler('editConditions', (e, target) => this.edit(parseInt(target.dataset.index)));
        this.addActionHandler('removeOptionList', (e, target) => this.removeItem(parseInt(target.dataset.index)));
        this.addActionHandler('addOptionList', () => this.addOptionList());

        this.optionsDefsList = Espo.Utils.cloneDeep(this.model.get(this.name)) || []
        this.scope = this.options.scope;

        this.setupItems();
        this.setupItemViews();
    }

    setupItems() {
        this.itemDataList = [];

        this.optionsDefsList.forEach((item, i) => {
            this.itemDataList.push({
                conditionGroupViewKey: `conditionGroup${i.toString()}`,
                optionsViewKey: 'options' + i.toString(),
                index: i,
            });
        });
    }

    setupItemViews() {
        this.optionsDefsList.forEach((item, i) => {
            this.createStringView(i);

            this.createOptionsView(i);
        });
    }

    createOptionsView(num) {
        const key = `options${num.toString()}`;

        if (!this.optionsDefsList[num]) {
            return;
        }

        const model = new Model();

        model.set('options', this.optionsDefsList[num].optionList || []);

        this.createView(key, 'views/fields/multi-enum', {
            selector: `.options-container[data-key="${key}"]`,
            model: model,
            name: 'options',
            mode: 'edit',
            params: {
                options: this.model.get('options'),
                translatedOptions: this.model.get('translatedOptions')
            }
        }, view => {
            if (this.isRendered()) {
                view.render();
            }

            this.listenTo(this.model, 'change:options', () => {
                view.setTranslatedOptions(this.getTranslatedOptions());

                view.setOptionList(this.model.get('options'));
            });

            this.listenTo(model, 'change', () => {
                this.optionsDefsList[num].optionList = model.get('options') || [];
            });
        });
    }

    getTranslatedOptions() {
        if (this.model.get('translatedOptions')) {
            return this.model.get('translatedOptions');
        }

        const translatedOptions = {};

        const list = this.model.get('options') || [];

        list.forEach((value) => {
            translatedOptions[value] = this.getLanguage()
                .translateOption(value, this.options.field, this.options.scope);
        });

        return translatedOptions;
    }

    createStringView(num) {
        const key = 'conditionGroup' + num.toString();

        if (!this.optionsDefsList[num]) {
            return;
        }

        this.createView(key, 'views/admin/dynamic-logic/conditions-string/group-base', {
            selector: `.string-container[data-key="${key}"]`,
            itemData: {
                value: this.optionsDefsList[num].conditionGroup
            },
            operator: 'and',
            scope: this.scope,
        }, view => {
            if (this.isRendered()) {
                view.render();
            }
        });
    }

    edit(num) {
        this.createView('modal', 'views/admin/dynamic-logic/modals/edit', {
            conditionGroup: this.optionsDefsList[num].conditionGroup,
            scope: this.options.scope,
        }, (view) => {
            view.render();

            this.listenTo(view, 'apply', (conditionGroup) => {
                this.optionsDefsList[num].conditionGroup = conditionGroup;

                this.trigger('change');

                this.createStringView(num);
            });
        });
    }

    addOptionList() {
        this.optionsDefsList.push({
            optionList: this.model.get('options') || [],
            conditionGroup: null,
        });

        this.setupItems();
        this.reRender();
        this.setupItemViews();

        this.trigger('change');
    }

    removeItem(num) {
        this.optionsDefsList.splice(num, 1);

        this.setupItems();
        this.reRender();
        this.setupItemViews();

        this.trigger('change');
    }

    fetch() {
        const data = {};

        data[this.name] = this.optionsDefsList;

        if (!this.optionsDefsList.length) {
            data[this.name] = null;
        }

        return data;
    }
}
