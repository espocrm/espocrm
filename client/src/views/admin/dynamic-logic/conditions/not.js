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

import DynamicLogicConditionGroupBaseView from 'views/admin/dynamic-logic/conditions/group-base';

export default class extends DynamicLogicConditionGroupBaseView {

    template = 'admin/dynamic-logic/conditions/not'

    operator = 'not'

    data() {
        return {
            viewKey: this.viewKey,
            operator: this.operator,
            hasItem: this.hasView(this.viewKey),
            level: this.level,
            groupOperator: this.getGroupOperator(),
        };
    }

    setup() {
        this.level = this.options.level || 0;
        this.number = this.options.number || 0;
        this.scope = this.options.scope;

        this.itemData = this.options.itemData || {};
        this.viewList = [];

        const i = 0;
        const key = this.getKey();

        if (this.itemData.value) {
            this.createItemView(i, key, this.itemData.value);
        }

        this.viewKey = key;
    }

    removeItem() {
        const key = this.getKey();

        this.clearView(key);

        this.controlAddItemVisibility();
    }

    getKey() {
        const i = 0;

        return `view-${this.level.toString()}-${this.number.toString()}-${i.toString()}`;
    }

    getIndexForNewItem() {
        return 0;
    }

    addItemContainer() {}

    addViewDataListItem() {}

    fetch() {
        /** @type {import('./field-types/base').default} */
        const view = this.getView(this.viewKey);

        if (!view) {
            return {
                type: 'and',
                value: [],
            };
        }

        const value = view.fetch();

        return {
            type: this.operator,
            value: value,
        };
    }

    controlAddItemVisibility() {
        if (this.getView(this.getKey())) {
            this.$el.find(' > .group-bottom').addClass('hidden');
        } else {
            this.$el.find(' > .group-bottom').removeClass('hidden');
        }
    }
}
