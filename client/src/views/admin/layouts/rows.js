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

Espo.define('views/admin/layouts/rows', 'views/admin/layouts/base', function (Dep) {

    return Dep.extend({

        template: 'admin/layouts/rows',


        events: _.extend({
            'click #layout a[data-action="editField"]': function (e) {
                var data = {};
                this.dataAttributes.forEach(function (attr) {
                    data[attr] =  $(e.target).closest('li').data(Espo.Utils.toDom(attr))
                });
                this.openEditDialog(data);
            },
        }, Dep.prototype.events),

        dataAttributes: null,

        dataAttributesDefs: {},

        editable: false,

        data: function () {
            return {
                scope: this.scope,
                type: this.type,
                buttonList: this.buttonList,
                enabledFields: this.enabledFields,
                disabledFields: this.disabledFields,
                layout: this.rowLayout,
                dataAttributes: this.dataAttributes,
                dataAttributesDefs: this.dataAttributesDefs,
                editable: this.editable,
            };
        },

        afterRender: function () {
            $('#layout ul.enabled, #layout ul.disabled').sortable({
                connectWith: '#layout ul.connected'
            });
        },

        fetch: function () {
            var layout = [];
            $("#layout ul.enabled > li").each(function (i, el) {
                var o = {};
                this.dataAttributes.forEach(function (attr) {
                    var value = $(el).data(Espo.Utils.toDom(attr)) || null;
                    if (value) {
                        o[attr] = value;
                    }
                });
                layout.push(o);
            }.bind(this));
            return layout;
        },

        validate: function (layout) {
            if (layout.length == 0) {
                this.notify('Layout cannot be empty', 'error');
                return false;
            }
            return true;
        }
    });
});

