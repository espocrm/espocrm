/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2015 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
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

Espo.define('views/admin/field-manager/fields/dynamic-logic-conditions', 'views/fields/base', function (Dep) {

    return Dep.extend({

        editTemplate: 'admin/field-manager/fields/dynamic-logic-conditions/edit',

        data: function () {
        },

        setup: function () {
            var conditionGroup = (this.model.get(this.name) || {}).conditionGroup || [];

            this.createView('conditionGroup', 'views/admin/dynamic-logic/conditions-string/group-base', {
                itemData: {
                    value: conditionGroup
                },
                operator: 'and',
                scope: this.options.scope
            });
        },

        getConditionsString: function () {
            var data = (this.model.get(this.name) || {}).conditionGroup || [];
            if (!data.length) {
                return this.translate('None');
            }
            return this.stringifyConditionGroup(data);
        },

        stringifyConditionGroup: function (group, type) {
            type = type || 'and';
            if (type === 'and' || type === 'or') {
                var list = [];
                (group || []).forEach(function (item) {
                    list.push(this.stringifyConditionItem(item));
                }, this);
                return list.join(' ' + this.translate('and', 'logicalOperators', 'Admin') + ' ');
            } else if (type === 'not') {
                return this.translate('not', 'logicalOperators', 'Admin') + ' (' + this.stringifyConditionItem(group) + ')';
            }
        },

        stringifyConditionItem: function (item) {
            if (!item) return '';
            item = item || {};
            var type = item.type || 'equals';
            var value = item.value || null;

            if (~['and', 'or', 'not'].indexOf(type)) {
                return '(' + this.stringifyConditionGroup(value, type) + ')';
            }

            var operator;
            switch (type) {
                case 'equals':
                    operator = '=';
                    break;
                case 'notEquals':
                    operator = '&ne;';
                    break;
                case 'greaterThan':
                    operator = '&gt;';
                    break;
                case 'lessThan':
                    operator = '&lt;';
                    break;
                case 'greaterThanOrEquals':
                    operator = '&ge;';
                    break;
                case 'lessThanOrEquals':
                    operator = '&le;';
                    break;
                case 'isEmpty':
                    operator = '= &empty;';
                    break;
                case 'isNotEmpty':
                    operator = '&ne; &empty;';
                    break;
                case 'in':
                    operator = '&isin;';
                case 'notIn':
                    operator = '&notin;';
                    break;
                default:
                    return '';
            }

            if (!item.attribute) return;

            var attribute = item.attribute;

            var part = attribute;

            switch (type) {
                case 'in':
                case 'notIn':
                    part = part + ' ' + operator + ' (' + (value || []).map(function (valueItem) {
                       return this.stringifyValue(valueItem, item);
                    }, this).join(', ') + ')';
                    break;
                case 'isEmpty':
                case 'isNotEmpty':
                    part = part + ' ' + operator;
                    break;
                default:
                    part = part + ' ' + operator + ' ' + this.stringifyValue(value, item);
            }

            return part;
        },

        stringifyValue: function (value, item) {
            var field = (item.data || {}).field || item.attribute;

            var fieldType = this.getMetadata().get(['entityDefs', this.options.scope, 'fields', field]);

            this.getLanguage().translateOption(item, item.attribute, this.options.scope);


            return value;
        },
    });

});
