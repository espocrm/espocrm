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

define('views/record/merge', 'view', function (Dep) {

    return Dep.extend({

        template: 'record/merge',

        scope: null,

        data: function () {
            var rows = [];

            this.fields.forEach((field) => {
                var o = {
                    name: field,
                    scope: this.scope,
                };

                o.columns = [];

                this.models.forEach((m) => {
                    o.columns.push({
                        id: m.id,
                        fieldVariable: m.id + '-' + field,
                        isReadOnly: this.readOnlyFields[field] || false,
                    });
                });

                rows.push(o);
            });

            return {
                rows: rows,
                modelList: this.models,
                scope: this.scope,
                hasCreatedAt: this.hasCreatedAt,
                width: Math.round(((80 - this.models.length * 5) / this.models.length * 10)) / 10,
                dataList: this.getDataList(),
            };
        },

        events: {
            'change input[type="radio"][name="check-all"]': function (e) {
                e.stopPropagation();

                var id = e.currentTarget.value;

                $('input[data-id="'+id+'"]').prop('checked', true);
            },

            'click button[data-action="cancel"]': function () {
                this.getRouter().navigate('#' + this.scope, {trigger: true});
            },

            'click button[data-action="merge"]': function () {
                var id = $('input[type="radio"][name="check-all"]:checked').val();

                var model;

                this.models.forEach(m => {
                    if (m.id === id) {
                        model = m;
                    }
                });

                var attributes = {};

                $('input.field-radio:checked').each((i, el) => {
                    var field = el.name;
                    var id = $(el).attr('data-id');

                    if (model.id === id) {
                        return;
                    }

                    var fieldType = model.getFieldParam(field, 'type');
                    var fields = this.getFieldManager().getActualAttributeList(fieldType, field);

                    var modelFrom;

                    this.models.forEach(m => {
                        if (m.id === id) {
                            modelFrom = m;

                            return;
                        }
                    });

                    fields.forEach(field => {
                        attributes[field] = modelFrom.get(field);
                    });
                });

                this.notify('Merging...');

                var sourceIdList =
                    this.models
                        .filter(m => {
                            if (m.id !== model.id) {
                                return true;
                            }
                        })
                        .map(m => {
                            return m.id;
                        });

                Espo.Ajax
                    .postRequest('Action', {
                        entityType: this.scope,
                        action: 'merge',
                        id: model.id,
                        data: {
                            sourceIdList: sourceIdList,
                            attributes: attributes,
                        },
                    })
                    .then(() => {
                        this.notify('Merged', 'success');

                        this.getRouter().navigate(
                            '#' + this.scope + '/view/' + model.id,
                            {trigger: true}
                        );

                        if (this.collection) {
                            this.collection.fetch();
                        }
                    });
            }
        },

        afterRender: function () {
            $('input[data-id="' + this.models[0].id + '"]').prop('checked', true);
        },

        setup: function () {
            this.scope = this.options.models[0].name;
            this.models = this.options.models;

            var fieldManager = this.getFieldManager();

            var differentFieldList = [];
            var fieldsDefs = this.models[0].defs.fields;

            this.readOnlyFields = {};

            for (var field in fieldsDefs) {
                var type = fieldsDefs[field].type;

                if (type === 'linkMultiple') {
                    continue;
                }

                if (fieldsDefs[field].disabled) {
                    continue;
                }

                if (fieldsDefs[field].mergeDisabled) {
                    continue;
                }

                if (field === 'createdAt' || field === 'modifiedAt') {
                    continue;
                }

                if (fieldManager.isMergeable(type)) {
                    var actualAttributeList = fieldManager.getActualAttributeList(type, field);

                    var differs = false;

                    actualAttributeList.forEach((field) => {
                        var values = [];

                        this.models.forEach((model) => {
                            values.push(model.get(field));
                        });

                        var firstValue = values[0];

                        values.forEach((value) => {
                            if (!_.isEqual(firstValue, value)) {
                                differs = true;
                            }
                        });
                    });

                    if (differs) {
                        differentFieldList.push(field);

                        if (this.models[0].isFieldReadOnly(field)) {
                            this.readOnlyFields[field] = true;
                        }
                    }
                }
            }

            differentFieldList.sort((v1, v2) => {
                return this.translate(v1, 'fields', this.scope)
                    .localeCompare(this.translate(v2, 'fields', this.scope));
            });

            differentFieldList = differentFieldList.sort((v1, v2) => {
                if (!this.readOnlyFields[v1] && this.readOnlyFields[v2]) {
                    return -1;
                }

                return 1;
            });

            this.fields = differentFieldList;

            this.fields.forEach((field) => {
                var type = this.models[0].getFieldParam(field, 'type');

                this.models.forEach((model) => {
                    var viewName = model.getFieldParam(field, 'view') ||
                        this.getFieldManager().getViewName(type);

                    this.createView(model.id + '-' + field, viewName, {
                        model: model,
                        el: '.merge [data-id="'+model.id+'"] .field[data-name="' + field + '"]',
                        defs: {
                            name: field,
                        },
                        mode: 'detail',
                        readOnly: true,
                    });
                });
            });

            this.hasCreatedAt = this.getMetadata().get(['entityDefs', this.scope, 'fields', 'createdAt']);

            if (this.hasCreatedAt) {
                this.models.forEach((model) => {
                    this.createView(model.id + '-' + 'createdAt', 'views/fields/datetime', {
                        model: model,
                        el: '.merge [data-id="'+model.id+'"] .field[data-name="createdAt"]',
                        defs: {
                            name: 'createdAt',
                        },
                        mode: 'detail',
                        readOnly: true,
                    });
                });
            }
        },

        getDataList: function () {
            var dataList = [];

            this.models.forEach((model, i) => {
                var o = {};

                o.id = model.id;
                o.name = Handlebars.Utils.escapeExpression(model.get('name'));
                o.createdAtViewName = model.id + '-' + 'createdAt';

                dataList.push(o);
            });

            return dataList;
        },
    });
});
