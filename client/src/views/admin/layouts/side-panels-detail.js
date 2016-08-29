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

Espo.define('views/admin/layouts/side-panels-detail', 'views/admin/layouts/rows', function (Dep) {

    return Dep.extend({

        dataAttributes: ['name'],

        dataAttributesDefs: {
        },

        editable: false,

        ignoreList: [],

        ignoreTypeList: [],

        viewType: 'detail',

        setup: function () {
            Dep.prototype.setup.call(this);

            this.wait(true);
            this.loadLayout(function () {
                this.wait(false);
            }.bind(this));
        },

        loadLayout: function (callback) {
            this.getHelper().layoutManager.get(this.scope, this.type, function (layout) {
                this.readDataFromLayout(layout);
                if (callback) {
                    callback();
                }
            }.bind(this), false);
        },

        readDataFromLayout: function (layout) {
            var panelListAll = [];
            var labels = {};
            (this.getMetadata().get(['clientDefs', this.scope, 'sidePanels', this.viewType]) || []).forEach(function (item) {
                if (!item.name) return;
                panelListAll.push(item.name);
                if (item.label) {
                    labels[item.name] = item.label;
                }
            }, this);

            this.disabledFields = [];

            layout = layout || {};

            this.rowLayout = [];

            panelListAll.forEach(function (item) {
                var disabled = false;
                if ((layout[item] || {}).disabled) {
                    disabled = true;
                }
                var labelText;
                if (labels[item]) {
                    labelText = this.getLanguage().translate(labels[item], 'labels', this.scope);
                } else {
                    labelText = this.getLanguage().translate(item, 'panels', this.scope);
                }

                if (disabled) {
                    this.disabledFields.push({
                        name: item,
                        label: labelText
                    });
                } else {
                    this.rowLayout.push({
                        name: item,
                        label: labelText
                    });
                }
            }, this);

        },


        fetch: function () {
            var layout = {};
            $("#layout ul.disabled > li").each(function (i, el) {
                var o = {};
                var name = $(el).attr('data-name');
                layout[name] = {
                    disabled: true
                };
            }.bind(this));
            return layout;
        },

    });
});


