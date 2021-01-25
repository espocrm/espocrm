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

define('views/layout-set/layouts', 'views/admin/layouts/index', function (Dep) {

    return Dep.extend({

        setup: function () {
            var setId = this.setId = this.options.layoutSetId;
            this.baseUrl = '#LayoutSet/editLayouts/id=' + setId;

            Dep.prototype.setup.call(this);


            this.wait(
                this.getModelFactory().create('LayoutSet')
                .then(
                    function (m) {
                        this.sModel = m;
                        m.id = setId;
                        return m.fetch();
                    }.bind(this)
                )
            );
        },

        getLayoutScopeDataList: function () {
            var dataList = [];
            var list = this.sModel.get('layoutList') || [];

            var scopeList = [];

            list.forEach(function (item) {
                var arr = item.split('.');
                var scope = arr[0];
                if (~scopeList.indexOf(scope)) return;
                scopeList.push(scope);
            });

            scopeList.forEach(function (scope) {
                var o = {};
                o.scope = scope;
                o.url = this.baseUrl + '&scope=' + scope;
                o.typeDataList = [];

                var typeList = [];

                list.forEach(function (item) {
                    var arr = item.split('.');
                    var scope = arr[0];
                    var type = arr[1];
                    if (scope !== o.scope) return;
                    typeList.push(type);
                });

                typeList.forEach(function (type) {
                    o.typeDataList.push({
                        type: type,
                        url: this.baseUrl + '&scope=' + scope + '&type=' + type,
                    });
                }, this);

                o.typeList = typeList;

                dataList.push(o);
            }, this);

            return dataList;
        },

        getHeaderHtml: function () {
            var m = this.sModel;
            var separatorHtml = '<span class="breadcrumb-separator"><span class="chevron-right"></span></span>';

            var html = "<a href=\"#LayoutSet\">"+this.translate('LayoutSet', 'scopeNamesPlural')+"</a> " + separatorHtml + ' ' +
                "<a href=\"#LayoutSet/view/"+m.id+"\">"+Handlebars.Utils.escapeExpression(m.get('name'))+"</a> " +
                separatorHtml + ' ' + this.translate('Edit Layouts', 'labels', 'LayoutSet');

            return html;
        },

        navigate: function (scope, type) {
            this.getRouter().navigate('#LayoutSet/editLayouts/id='+this.setId+'&scope='+scope + '&type='+type, {trigger: false});
        },
    });
});
