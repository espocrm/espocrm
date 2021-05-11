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

/**
 * @deprecated
 */
define('views/admin/layouts/relationships', 'views/admin/layouts/rows', function (Dep) {

    return Dep.extend({

        dataAttributeList: [
            'name',
            'dynamicLogicVisible',
            'style',
            'dynamicLogicStyled',
        ],

        editable: true,

        dataAttributesDefs: {
            style: {
                type: 'enum',
                options: [
                    'default',
                    'success',
                    'danger',
                    'warning',
                ],
                translation: 'LayoutManager.options.style',
            },
            dynamicLogicVisible: {
                type: 'base',
                view: 'views/admin/field-manager/fields/dynamic-logic-conditions',
                tooltip: 'dynamicLogicVisible',
            },
            dynamicLogicStyled: {
                type: 'base',
                view: 'views/admin/field-manager/fields/dynamic-logic-conditions',
                tooltip: 'dynamicLogicStyled',
            },
            name: {
                readOnly: true,
            },
        },

        languageCategory: 'links',

        setup: function () {
            Dep.prototype.setup.call(this);

            this.dataAttributesDefs = Espo.Utils.cloneDeep(this.dataAttributesDefs);

            this.dataAttributesDefs.dynamicLogicVisible.scope = this.scope;
            this.dataAttributesDefs.dynamicLogicStyled.scope = this.scope;

            this.wait(true);

            this.loadLayout(function () {
                this.wait(false);
            }.bind(this));
        },

        loadLayout: function (callback) {
            this.getModelFactory().create(this.scope, function (model) {
                this.getHelper().layoutManager.getOriginal(this.scope, this.type, this.setId, function (layout) {

                    var allFields = [];

                    for (var field in model.defs.links) {
                        if (['hasMany', 'hasChildren'].indexOf(model.defs.links[field].type) != -1) {
                            if (this.isLinkEnabled(model, field)) {
                                allFields.push(field);
                            }
                        }
                    }

                    allFields.sort(function (v1, v2) {
                        return this.translate(v1, 'links', this.scope)
                            .localeCompare(this.translate(v2, 'links', this.scope));
                    }.bind(this));

                    allFields.push('_delimiter_');

                    this.enabledFieldsList = [];

                    this.enabledFields = [];
                    this.disabledFields = [];

                    for (var i in layout) {
                        var item = layout[i];
                        var o;

                        if (typeof item == 'string' || item instanceof String) {
                            o = {
                                name: item,

                                label: this.getLanguage().translate(item, 'links', this.scope)
                            };
                        }
                        else {
                            o = item;

                            o.label = this.getLanguage().translate(o.name, 'links', this.scope);
                        }

                        if (o.name[0] === '_') {
                            o.notEditable = true;

                            if (o.name == '_delimiter_') {
                                o.label = '. . .';
                            }
                        }
                        this.dataAttributeList.forEach(function (attribute) {
                            if (attribute === 'name') {
                                return;
                            }

                            if (attribute in o) {
                                return;
                            }

                            var value = this.getMetadata()
                                .get(['clientDefs', this.scope, 'relationshipPanels', o.name, attribute]);

                            if (value === null) {
                                return;
                            }

                            o[attribute] = value;
                        }, this);

                        this.enabledFields.push(o);
                        this.enabledFieldsList.push(o.name);
                    }

                    for (var i in allFields) {
                        if (!_.contains(this.enabledFieldsList, allFields[i])) {
                            var name = allFields[i];

                            var label = this.getLanguage().translate(name, 'links', this.scope);

                            var o = {
                                name: name,
                                label: label,
                            };

                            if (o.name[0] === '_') {
                                o.notEditable = true;

                                if (o.name == '_delimiter_') {
                                    o.label = '. . .';
                                }
                            }

                            this.disabledFields.push(o);
                        }
                    }

                    this.rowLayout = this.enabledFields;

                    for (var i in this.rowLayout) {
                        var o = this.rowLayout[i];

                        o.label = this.getLanguage().translate(this.rowLayout[i].name, 'links', this.scope);

                        if (o.name == '_delimiter_') {
                            o.label = '. . .';
                        }

                        this.itemsData[this.rowLayout[i].name] = Espo.Utils.cloneDeep(this.rowLayout[i]);
                    }

                    callback();
                }.bind(this));
            }.bind(this));
        },

        validate: function () {
            return true;
        },

        isLinkEnabled: function (model, name) {
            return !model.getLinkParam(name, 'disabled') && !model.getLinkParam(name, 'layoutRelationshipsDisabled');
        },
    });
});
