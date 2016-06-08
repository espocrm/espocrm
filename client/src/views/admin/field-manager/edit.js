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

Espo.define('views/admin/field-manager/edit', ['view', 'model'], function (Dep, Model) {

    return Dep.extend({

        template: 'admin/field-manager/edit',

        entityTypeWithTranslatedOptionsList: ['enum', 'multiEnum', 'array', 'phone'],

        data: function () {
            return {
                scope: this.scope,
                field: this.field,
                defs: this.defs,
                params: this.params,
                type: this.type,
                fieldList: this.fieldList,
            };
        },

        events: {
            'click button[data-action="close"]': function () {
                this.getRouter().navigate('#Admin/fieldManager/scope=' + this.scope, {trigger: true});
            },
            'click button[data-action="save"]': function () {
                this.save();
            },
        },

        setup: function () {
            this.scope = this.options.scope;
            this.field = this.options.field;
            this.type = this.options.type;
            this.defs = {};

            this.fieldList = [];

            this.isNew = false;
            if (!this.field) {
                this.isNew = true;
            }

            this.model = new Model();
            this.model.name = 'Admin';
            this.model.urlRoot = 'Admin/fieldManager/' + this.scope;

            this.model.defs = {
                fields: {
                    name: {required: true},
                    label: {required: true},
                },
            };

            if (!this.isNew) {
                this.model.id = this.field;
                this.model.scope = this.scope;
                this.model.set('name', this.field);
                this.model.set('label', this.getLanguage().translate(this.field, 'fields', this.scope));
            } else {
                this.model.set('type', this.type);
            }


            this.wait(true);
            this.getModelFactory().create(this.scope, function (model) {

                if (!this.isNew) {
                    this.type = model.getFieldType(this.field);
                    this.defs = model.defs.fields[this.field];
                }

                this.params = [];
                var params = this.getFieldManager().getParams(this.type) || [];
                params.forEach(function (o) {
                    var item = o.name;
                    var disableParamName = 'customization' + Espo.Utils.upperCaseFirst(item) + 'Disabled';
                    if (this.getMetadata().get('entityDefs.' + this.scope + '.fields.' + this.field + '.' + disableParamName)) {
                        return;
                    }
                    this.params.push(o);
                }, this);

                this.params.forEach(function (o) {
                    this.model.defs.fields[o.name] = o;
                }, this);

                this.model.set(this.defs);

                if (this.isNew) {
                    this.model.populateDefaults();
                }

                this.createFieldView('varchar', 'name', !this.isNew, {
                    trim: true
                });
                this.createFieldView('varchar', 'label', null, {
                    trim: true
                });

                this.params.forEach(function (o) {
                    if (o.hidden) {
                        return;
                    }
                    this.createFieldView(o.type, o.name, null, o);
                }, this);

                this.wait(false);
            }.bind(this));
        },

        afterRender: function () {
            this.getView('name').on('change', function (m) {
                var name = this.model.get('name');
                this.model.set('label', name);
                if (name) {
                    name = name.replace('-', '').replace(/[^\w\s]/gi, '').replace(/ (.)/g, function(match, g) {
                        return g.toUpperCase();
                    }).replace(' ', '');
                    if (name.length) {
                         name = name.charAt(0).toLowerCase() + name.slice(1);
                    }
                }
                this.model.set('name', name);
            }, this);
        },

        createFieldView: function (type, name, readOnly, params) {
            var viewName = (params || {}).view || this.getFieldManager().getViewName(type);
            this.createView(name, viewName, {
                model: this.model,
                el: this.options.el + ' .field[data-name="' + name + '"]',
                defs: {
                    name: name,
                    params: params
                },
                mode: readOnly ? 'detail' : 'edit',
                readOnly: readOnly,
                scope: this.scope,
                field: this.field,
            });
            this.fieldList.push(name);
        },

        save: function () {
            this.fieldList.forEach(function (field) {
                var view = this.getView(field);
                if (!view.readOnly) {
                    view.fetchToModel();
                }
            }, this);

            var notValid = false;
            this.fieldList.forEach(function (field) {
                notValid = this.getView(field).validate() || notValid;
            }, this);

            if (notValid) {
                this.notify('Not valid', 'error');
                return;
            }

            this.listenToOnce(this.model, 'sync', function () {
                Espo.Ui.notify(false);

                this.getMetadata().load(function () {
                    this.getMetadata().storeToCache();
                    this.trigger('after:save');
                }.bind(this), true);

                var langData = this.getLanguage().data;
                if (this.scope in langData) {
                    if (!('fields' in langData[this.scope])) {
                        langData[this.scope]['fields'] = {};
                    }
                    langData[this.scope]['fields'][this.model.get('name')] = this.model.get('label');


                    if (this.getMetadata().get(['fields', this.model.get('type'), 'translatedOptions']) && this.model.get('translatedOptions')) {
                        langData[this.scope].options = langData[this.scope].options || {};
                        langData[this.scope]['options'][this.model.get('name')] = this.model.get('translatedOptions') || {};
                    }
                }
            }.bind(this));

            this.notify('Saving...');
            this.model.save();
        },

    });

});
