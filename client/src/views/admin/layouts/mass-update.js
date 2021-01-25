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

define('views/admin/layouts/mass-update', 'views/admin/layouts/rows', function (Dep) {

    return Dep.extend({

        dataAttributeList: ['name'],

        editable: false,

        ignoreList: [],

        ignoreTypeList: ['duration'],

        dataAttributesDefs: {
            name: {
                readOnly: true
            }
        },

        setup: function () {
            Dep.prototype.setup.call(this);

            this.wait(true);
            this.loadLayout(function () {
                this.wait(false);
            }.bind(this));
        },

        loadLayout: function (callback) {
            this.getModelFactory().create(this.scope, function (model) {
                this.getHelper().layoutManager.getOriginal(this.scope, this.type, this.setId, function (layout) {

                    var allFields = [];
                    for (var field in model.defs.fields) {
                        if (!model.getFieldParam(field, 'readOnly') && this.isFieldEnabled(model, field)) {
                            allFields.push(field);
                        }
                    }

                    allFields.sort(function (v1, v2) {
                        return this.translate(v1, 'fields', this.scope).localeCompare(this.translate(v2, 'fields', this.scope));
                    }.bind(this));

                    this.enabledFieldsList = [];

                    this.enabledFields = [];
                    this.disabledFields = [];
                    for (var i in layout) {
                        this.enabledFields.push({
                            name: layout[i],
                            label: this.getLanguage().translate(layout[i], 'fields', this.scope)
                        });
                        this.enabledFieldsList.push(layout[i]);
                    }

                    for (var i in allFields) {
                        if (!_.contains(this.enabledFieldsList, allFields[i])) {
                            this.disabledFields.push({
                                name: allFields[i],
                                label: this.getLanguage().translate(allFields[i], 'fields', this.scope)
                            });
                        }
                    }
                    this.rowLayout = this.enabledFields;

                    for (var i in this.rowLayout) {
                        this.rowLayout[i].label = this.getLanguage().translate(this.rowLayout[i].name, 'fields', this.scope);

                        this.itemsData[this.rowLayout[i].name] = Espo.Utils.cloneDeep(this.rowLayout[i]);
                    }

                    callback();
                }.bind(this));
            }.bind(this));
        },

        fetch: function () {
            var layout = [];
            $("#layout ul.enabled > li").each(function (i, el) {
                layout.push($(el).data('name'));
            }.bind(this));
            return layout;
        },

        validate: function () {
            return true;
        },

        isFieldEnabled: function (model, name) {
            if (this.ignoreList.indexOf(name) != -1) {
                return false;
            }
            if (this.ignoreTypeList.indexOf(model.getFieldParam(name, 'type')) != -1) {
                return false;
            }

            var layoutList = model.getFieldParam(name, 'layoutAvailabilityList');
            if (layoutList && !~layoutList.indexOf(this.type)) return;

            return !model.getFieldParam(name, 'disabled') && !model.getFieldParam(name, 'layoutMassUpdateDisabled') && !model.getFieldParam(name, 'readOnly');
        },

    });
});
