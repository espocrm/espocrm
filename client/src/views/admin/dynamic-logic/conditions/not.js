/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: https://www.espocrm.com
 *
 * EspoCRM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * EspoCRM is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with EspoCRM. If not, see http://www.gnu.org/licenses/.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

Espo.define('views/admin/dynamic-logic/conditions/not', 'views/admin/dynamic-logic/conditions/group-base', function (Dep) {

    return Dep.extend({

        template: 'admin/dynamic-logic/conditions/not',

        operator: 'not',

        data: function () {
            return {
                viewKey: this.viewKey,
                operator: this.operator,
                hasItem: this.hasView(this.viewKey),
                level: this.level,
                groupOperator: this.getGroupOperator()
            };
        },

        setup: function () {
            this.level = this.options.level || 0;
            this.number = this.options.number || 0;
            this.scope = this.options.scope;

            this.itemData = this.options.itemData || {};
            this.viewList = [];

            var i = 0;
            var key = this.getKey();

            this.createItemView(i, key, this.itemData.value);
            this.viewKey = key;
        },

        removeItem: function () {
            var key = this.getKey();
            this.clearView(key);

            this.controlAddItemVisibility();
        },

        getKey: function () {
            var i = 0;
            return 'view-' + this.level.toString() + '-' + this.number.toString() + '-' + i.toString();
        },

        getIndexForNewItem: function () {
            return 0;
        },

        addItemContainer: function () {
        },

        addViewDataListItem: function () {
        },

        fetch: function () {
            var view = this.getView(this.viewKey);
            if (!view) return {
                type: 'and',
                value: []
            };

            var value = view.fetch();

            console.log(value);

            return {
                type: this.operator,
                value: value
            };
        },

        controlAddItemVisibility: function () {
            if (this.getView(this.getKey())) {
                this.$el.find(' > .group-bottom').addClass('hidden');
            } else {
                this.$el.find(' > .group-bottom').removeClass('hidden');
            }
        }

    });

});

