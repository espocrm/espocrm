/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2021 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

define('views/layout-set/fields/layout-list', [
    'views/fields/multi-enum', 'views/admin/layouts/index'], function (Dep, LayoutsIndex) {

    return Dep.extend({

        typeList: [
            'list',
            'detail',
            'listSmall',
            'detailSmall',
            'bottomPanelsDetail',
            'filters',
            'massUpdate',
            'sidePanelsDetail',
            'sidePanelsEdit',
            'sidePanelsDetailSmall',
            'sidePanelsEditSmall',
        ],

        setupOptions: function () {
            this.params.options = [];
            this.translatedOptions = {};

            this.scopeList = Object.keys(this.getMetadata().get('scopes')).filter(function (item) {
                return this.getMetadata().get(['scopes', item, 'layouts']);
            }, this).sort(function (v1, v2) {
                return this.translate(v1, 'scopeNames').localeCompare(this.translate(v2, 'scopeNames'));
            }.bind(this));

            var dataList = LayoutsIndex.prototype.getLayoutScopeDataList.call(this);

            dataList.forEach(function (item1) {
                item1.typeList.forEach(function (type) {
                    var item = item1.scope + '.' + type;
                    if (type.substr(-6) === 'Portal') return;
                    this.params.options.push(item);

                    this.translatedOptions[item] = this.translate(item1.scope, 'scopeNames') + '.' +
                        this.translate(type, 'layouts', 'Admin');
                }, this);
            }, this);
        },

    });
});
