/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2024 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

define('views/admin/layouts/rows', ['views/admin/layouts/base'], function (Dep) {

    return Dep.extend({

        template: 'admin/layouts/rows',

        dataAttributeList: null,
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
                dataAttributeList: this.dataAttributeList,
                dataAttributesDefs: this.dataAttributesDefs,
                editable: this.editable,
            };
        },

        setup: function () {
            this.itemsData = {};

            Dep.prototype.setup.call(this);

            this.events['click a[data-action="editItem"]'] = e => {
                const name = $(e.target).closest('li').data('name');

                this.editRow(name);
            };

            this.on('update-item', (name, attributes) => {
                this.itemsData[name] = Espo.Utils.cloneDeep(attributes);
            });

            Espo.loader.require('res!client/css/misc/layout-manager-rows.css', styleCss => {
                this.$style = $('<style>').html(styleCss).appendTo($('body'));
            });
        },

        onRemove: function () {
            if (this.$style) {
                this.$style.remove();
            }
        },

        editRow: function (name) {
            const attributes = Espo.Utils.cloneDeep(this.itemsData[name] || {});
            attributes.name = name;

            this.openEditDialog(attributes)
        },

        afterRender: function () {
            $('#layout ul.enabled, #layout ul.disabled').sortable({
                connectWith: '#layout ul.connected',
                update: e => {
                    if (!$(e.target).hasClass('disabled')) {
                        this.onDrop(e);
                        this.setIsChanged();
                    }
                },
            });

            this.$el.find('.enabled-well').focus();
        },

        onDrop: function (e) {},

        fetch: function () {
            const layout = [];

            $("#layout ul.enabled > li").each((i, el) => {
                const o = {};

                const name = $(el).data('name');

                const attributes = this.itemsData[name] || {};
                attributes.name = name;

                this.dataAttributeList.forEach(attribute => {
                    const defs = this.dataAttributesDefs[attribute] || {};

                    if (defs.notStorable) {
                        return;
                    }

                    const value = attributes[attribute] || null;

                    if (value) {
                        o[attribute] = value;
                    }
                });

                layout.push(o);
            });

            return layout;
        },

        validate: function (layout) {
            if (layout.length === 0) {
                this.notify('Layout cannot be empty', 'error');

                return false;
            }

            return true;
        }
    });
});
