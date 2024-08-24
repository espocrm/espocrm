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

/**
 * @deprecated
 */
define('views/admin/layouts/relationships', ['views/admin/layouts/rows'], function (Dep) {

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
                    'info',
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

            this.loadLayout(() => {
                this.wait(false);
            });
        },

        loadLayout: function (callback) {
            this.getModelFactory().create(this.scope, (model) => {
                this.getHelper().layoutManager.getOriginal(this.scope, this.type, this.setId, (layout) => {

                    let allFields = [];

                    for (let field in model.defs.links) {
                        if (['hasMany', 'hasChildren'].indexOf(model.defs.links[field].type) !== -1) {
                            if (this.isLinkEnabled(model, field)) {
                                allFields.push(field);
                            }
                        }
                    }

                    allFields.sort((v1, v2) => {
                        return this.translate(v1, 'links', this.scope)
                            .localeCompare(this.translate(v2, 'links', this.scope));
                    });

                    allFields.push('_delimiter_');

                    this.enabledFieldsList = [];

                    this.enabledFields = [];
                    this.disabledFields = [];

                    for (let i in layout) {
                        let item = layout[i];
                        let o;

                        if (typeof item == 'string' || item instanceof String) {
                            o = {
                                name: item,
                                labelText: this.getLanguage().translate(item, 'links', this.scope)
                            };
                        }
                        else {
                            o = item;

                            o.labelText = this.getLanguage().translate(o.name, 'links', this.scope);
                        }

                        if (o.name[0] === '_') {
                            o.notEditable = true;

                            if (o.name === '_delimiter_') {
                                o.labelText = '. . .';
                            }
                        }

                        this.dataAttributeList.forEach(attribute => {
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
                        });

                        this.enabledFields.push(o);
                        this.enabledFieldsList.push(o.name);
                    }

                    for (let i in allFields) {
                        if (!_.contains(this.enabledFieldsList, allFields[i])) {
                            var name = allFields[i];

                            var label = this.getLanguage().translate(name, 'links', this.scope);

                            let o = {
                                name: name,
                                labelText: label,
                            };

                            if (o.name[0] === '_') {
                                o.notEditable = true;

                                if (o.name === '_delimiter_') {
                                    o.labelText = '. . .';
                                }
                            }

                            this.disabledFields.push(o);
                        }
                    }

                    this.rowLayout = this.enabledFields;

                    for (let i in this.rowLayout) {
                        let o = this.rowLayout[i];

                        o.labelText = this.getLanguage().translate(this.rowLayout[i].name, 'links', this.scope);

                        if (o.name === '_delimiter_') {
                            o.labelText = '. . .';
                        }

                        this.itemsData[this.rowLayout[i].name] = Espo.Utils.cloneDeep(this.rowLayout[i]);
                    }

                    callback();
                });
            });
        },

        validate: function () {
            return true;
        },

        isLinkEnabled: function (model, name) {
            return !model.getLinkParam(name, 'disabled') &&
                !model.getLinkParam(name, 'utility') &&
                !model.getLinkParam(name, 'layoutRelationshipsDisabled');
        },
    });
});
