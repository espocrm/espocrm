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

Espo.define('views/admin/integrations/edit', ['view', 'model'], function (Dep, Model) {

    return Dep.extend({

        template: 'admin/integrations/edit',

        data: function () {
            return {
                integration: this.integration,
                dataFieldList: this.dataFieldList,
                helpText: this.helpText
            };
        },

        events: {
            'click button[data-action="cancel"]': function () {
                this.getRouter().navigate('#Admin/integrations', {trigger: true});
            },
            'click button[data-action="save"]': function () {
                this.save();
            },
        },

        setup: function () {
            this.integration = this.options.integration;

            this.helpText = false;
            if (this.getLanguage().has(this.integration, 'help', 'Integration')) {
                this.helpText = this.translate(this.integration, 'help', 'Integration');
            }

            this.fieldList = [];

            this.dataFieldList = [];

            this.model = new Model();
            this.model.id = this.integration;
            this.model.name = 'Integration';
            this.model.urlRoot = 'Integration';

            this.model.defs = {
                fields: {
                    enabled: {
                        required: true,
                        type: 'bool'
                    },
                }
            };

            this.wait(true);

            var fields = this.fields = this.getMetadata().get('integrations.' + this.integration + '.fields');

            Object.keys(this.fields).forEach(function (name) {
                this.model.defs.fields[name] = this.fields[name];
                this.dataFieldList.push(name);
            }, this);

            this.model.populateDefaults();

            this.listenToOnce(this.model, 'sync', function () {
                this.createFieldView('bool', 'enabled');
                Object.keys(this.fields).forEach(function (name) {
                    this.createFieldView(this.fields[name]['type'], name, null, this.fields[name]);
                }, this);

                this.wait(false);
            }, this);

            this.model.fetch();
        },

        hideField: function (name) {
            this.$el.find('label[data-name="'+name+'"]').addClass('hide');
            this.$el.find('div.field[data-name="'+name+'"]').addClass('hide');
            var view = this.getView(name);
            if (view) {
                view.disabled = true;
            }
        },

        showField: function (name) {
            this.$el.find('label[data-name="'+name+'"]').removeClass('hide');
            this.$el.find('div.field[data-name="'+name+'"]').removeClass('hide');
            var view = this.getView(name);
            if (view) {
                view.disabled = false;
            }
        },

        afterRender: function () {
            if (!this.model.get('enabled')) {
                this.dataFieldList.forEach(function (name) {
                    this.hideField(name);
                }, this);
            }

            this.listenTo(this.model, 'change:enabled', function () {
                if (this.model.get('enabled')) {
                    this.dataFieldList.forEach(function (name) {
                        this.showField(name);
                    }, this);
                } else {
                    this.dataFieldList.forEach(function (name) {
                        this.hideField(name);
                    }, this);
                }
            }, this);
        },

        createFieldView: function (type, name, readOnly, params) {
            this.createView(name, this.getFieldManager().getViewName(type), {
                model: this.model,
                el: this.options.el + ' .field[data-name="'+name+'"]',
                defs: {
                    name: name,
                    params: params
                },
                mode: readOnly ? 'detail' : 'edit',
                readOnly: readOnly,
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
                var fieldView = this.getView(field);
                if (fieldView && !fieldView.disabled) {
                    notValid = fieldView.validate() || notValid;
                }
            }, this);

            if (notValid) {
                this.notify('Not valid', 'error');
                return;
            }

            this.listenToOnce(this.model, 'sync', function () {
                this.notify('Saved', 'success');
            }, this);

            this.notify('Saving...');
            this.model.save();
        },

    });

});
