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

Espo.define('views/admin/dynamic-logic/conditions-string/group-base', 'view', function (Dep) {

    return Dep.extend({

        template: 'admin/dynamic-logic/conditions-string/group-base',

        data: function () {
            var isEmpty = false;
            if (!this.conditionList.length) {
                return {
                    isEmpty: true
                };
            }
            return {
                viewDataList: this.viewDataList,
                operator: this.operator,
                level: this.level
            };
        },

        setup: function () {
            this.level = this.options.level || 0;
            this.number = this.options.number || 0;
            this.scope = this.options.scope;


            this.operator = this.options.operator || this.operator;

            this.itemData = this.options.itemData || {};
            this.viewList = [];

            var conditionList = this.conditionList = this.itemData.value || [];

            this.viewDataList = [];
            conditionList.forEach(function (item, i) {
                var key = 'view-' + this.level.toString() + '-' + this.number.toString() + '-' + i.toString();

                this.createItemView(i, key, item);
                this.viewDataList.push({
                    key: key,
                    isEnd: i === conditionList.length - 1
                });
            }, this);
        },

        createItemView: function (number, key, item) {
            this.viewList.push(key);

            item = item || {};

            var additionalData = item.data || {};

            var type = additionalData.type || item.type || 'equals';

            var viewName = this.getMetadata().get(['clientDefs', 'DynamicLogic', 'itemTypes', type, 'view']);
            if (!viewName) return;

            var operator = this.getMetadata().get(['clientDefs', 'DynamicLogic', 'itemTypes', type, 'operator']);
            var operatorString = this.getMetadata().get(['clientDefs', 'DynamicLogic', 'itemTypes', type, 'operatorString']);

            this.createView(key, viewName, {
                itemData: item,
                scope: this.scope,
                level: this.level + 1,
                el: this.getSelector() + ' [data-view-key="'+key+'"]',
                number: number,
                operator: operator,
                operatorString: operatorString
            });
        },

    });

});

