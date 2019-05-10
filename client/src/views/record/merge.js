/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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
            this.fields.forEach(function (field) {
                var o = {
                    name: field,
                    scope: this.scope,
                };
                o.columns = [];
                this.models.forEach(function (m) {
                    o.columns.push({
                        id: m.id,
                        fieldVariable: m.id + '-' + field,
                    });
                });
                rows.push(o);
            }.bind(this));
            return {
                rows: rows,
                modelList: this.models,
                scope: this.scope,
                hasCreatedAt: this.hasCreatedAt,
                width: Math.round(((80 - this.models.length * 5) / this.models.length * 10)) / 10,
                dataList: this.getDataList()
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

                this.models.forEach(function (m) {
                    if (m.id == id) {
                        model = m;
                    }
                }.bind(this));

                var self = this;

                var attributes = {};
                $('input.field-radio:checked').each(function (i, el) {
                    var field = el.name;
                    var id = $(el).data('id');
                    if (model.id != id) {
                        var fieldType = model.getFieldParam(field, 'type');
                        var fields = self.getFieldManager().getActualAttributeList(fieldType, field);
                        var modelFrom;
                        self.models.forEach(function (m) {
                            if (m.id == id) {
                                modelFrom = m;
                                return;
                            }
                        });
                        fields.forEach(function (field) {
                            attributes[field] = modelFrom.get(field);
                        });

                    }
                });

                self.notify('Merging...');

                $.ajax({
                    url: this.scope + '/action/merge',
                    type: 'POST',
                    data: JSON.stringify({
                        attributes: attributes,
                        targetId: model.id,
                        sourceIds: this.models.filter(function (m) {
                            if (m.id != model.id) return true;
                        }).map(function (m) {
                            return m.id;
                        })
                    })
                }).done(function () {
                    this.notify('Merged', 'success');
                    this.getRouter().navigate('#' + this.scope + '/view/' + model.id, {trigger: true});
                    if (this.collection) {
                        this.collection.fetch();
                    }
                }.bind(this));
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

            for (var field in fieldsDefs) {
                var type = fieldsDefs[field].type;
                if (type === 'linkMultiple') continue;
                if (fieldsDefs[field].disabled) continue
                if (fieldsDefs[field].mergeDisabled) continue

                if (fieldManager.isMergeable(type) && !this.models[0].isFieldReadOnly(field)) {
                    var actualAttributeList = fieldManager.getActualAttributeList(type, field);

                    var differs = false;
                    actualAttributeList.forEach(function (field) {
                        var values = [];
                        this.models.forEach(function (model) {
                            values.push(model.get(field));
                        });
                        var firstValue = values[0];
                        values.forEach(function (value) {
                            if (!_.isEqual(firstValue, value)) {
                                differs = true;
                            }
                        });
                    }.bind(this));
                    if (differs) {
                        differentFieldList.push(field);
                    }
                }
            }
            this.fields = differentFieldList;

            this.fields.forEach(function (field) {
                var type = this.models[0].getFieldParam(field, 'type');

                this.models.forEach(function (model) {
                    var viewName = model.getFieldParam(field, 'view') || this.getFieldManager().getViewName(type);

                    this.createView(model.id + '-' + field, viewName, {
                        model: model,
                        el: '.merge [data-id="'+model.id+'"] .field[data-name="' + field + '"]',
                        defs: {
                            name: field,
                        },
                        mode: 'detail',
                        readOnly: true,
                    });
                }.bind(this));

            }.bind(this));

            this.hasCreatedAt = this.getMetadata().get(['entityDefs', this.scope, 'fields', 'createdAt']);

            if (this.hasCreatedAt) {
                this.models.forEach(function (model) {
                    this.createView(model.id + '-' + 'createdAt', 'views/fields/datetime', {
                        model: model,
                        el: '.merge [data-id="'+model.id+'"] .field[data-name="createdAt"]',
                        defs: {
                            name: 'createdAt',
                        },
                        mode: 'detail',
                        readOnly: true,
                    });
                }, this);
            }
        },

        getDataList: function () {
            var dataList = [];
            this.models.forEach(function (model, i) {
                var o = {};
                o.id = model.id;
                o.name = Handlebars.Utils.escapeExpression(model.get('name'));
                o.createdAtViewName = model.id + '-' + 'createdAt';
                dataList.push(o);
            }, this);
            return dataList;
        },
    });
});
