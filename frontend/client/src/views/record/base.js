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
 ************************************************************************/

Espo.define('Views.Record.Base', 'View', function (Dep) {

    return Dep.extend({

        type: 'edit',

        fieldsMode: 'edit',

        scope: null,

        isNew: false,

        dependencyDefs: {},

        fieldList: null,

        hideField: function (name) {
            var $field = this.$el.find('div.field-' + name);
            var $label = this.$el.find('label.field-label-' + name);

            $field.addClass('hidden');
            $label.addClass('hidden');
            $field.closest('.cell-' + name).addClass('hidden-cell');

            var view = this.getFieldView(name);
            if (view) {
                view.enabled = false;
            }
        },

        showField: function (name) {
            var $field = this.$el.find('div.field-' + name);
            var $label = this.$el.find('label.field-label-' + name);

            $field.removeClass('hidden');
            $label.removeClass('hidden');
            $field.closest('.cell-' + name).removeClass('hidden-cell');
            var view = this.getFieldView(name);
            if (view) {
                view.enabled = true;
            }
        },

        setFieldReadOnly: function (name) {
            var view = this.getFieldView(name);
            if (view) {
                if (!view.readOnly) {
                    view.readOnly = true;
                    view.setMode('detail');
                    if (view.isRendered()) {
                        view.reRender();
                    }
                }
            }
        },

        setFieldNotReadOnly: function (name) {
            var view = this.getFieldView(name);
            if (view) {
                if (view.readOnly) {
                    view.readOnly = false;
                    if (this.mode == 'edit') {
                        view.setMode('edit');
                    }
                    if (view.isRendered()) {
                        view.reRender();
                    }
                }
            }
        },

        showPanel: function (name) {
            this.$el.find('.panel[data-panel-name="'+name+'"]').removeClass('hidden');
        },

        hidePanel: function (name) {
            this.$el.find('.panel[data-panel-name="'+name+'"]').addClass('hidden');
        },

        getFields: function () {
            var fields = {};
            this.fieldList.forEach(function (item) {
                var view = this.getFieldView(item);
                if (view) {
                    fields[item] = view;
                }
            }, this);
            return fields;
        },

        getFieldView: function (name) {
            return this.getView(name) || null;
        },


        setup: function () {
            if (typeof this.model === 'undefined') {
                throw new Error('Model has not been injected into record view.');
            }

            this.scope = this.model.name;
            this.fieldList = this.options.fieldList || this.fieldList || [];

            this.numId = Math.floor((Math.random() * 10000) + 1);
            this.id = Espo.Utils.toDom(this.scope) + '-' + Espo.Utils.toDom(this.type) + '-' + this.numId;

            if (this.model.isNew()) {
                this.isNew = true;
            }

            this.attributes = this.model.getClonedAttributes();

            this._initDependancy();
        },

        _initDependancy: function () {
            Object.keys(this.dependencyDefs || {}).forEach(function (attr) {
                this.listenTo(this.model, 'change:' + attr, function () {
                    this._handleDependencyAttribute(attr);
                }, this);
            }, this);

            this.on('after:render', function () {
                this._handleDependencyAttributes();
            }, this);
        },

        validate: function () {
            var notValid = false;
            var fields = this.getFields();
            for (var i in fields) {
                if (fields[i].mode == 'edit') {
                    if (fields[i].enabled && !fields[i].readOnly) {
                        notValid = fields[i].validate() || notValid;
                    }
                }
            };
            return notValid
        },

        afterSave: function () {
            if (this.isNew) {
                this.notify('Created', 'success');
            } else {
                this.notify('Saved', 'success');
            }
        },

        beforeSave: function () {
            this.notify('Saving...');
        },

        afterSaveError: function () {
        },

        afterNotModified: function () {
            var msg = this.translate('notModified', 'messages');
            Espo.Ui.warning(msg, 'warning');
        },

        afterNotValid: function () {
            this.notify('Not valid', 'error');
        },

        save: function (callback) {
            this.disableButtons();
            var data = this.fetch();

            var self = this;
            var model = this.model;

            var attrsInitialy = this.attributes;

            var attrsBefore = this.model.getClonedAttributes();

            data = _.extend(Espo.Utils.cloneDeep(attrsBefore), data);

            var attrs = false;
            if (model.isNew()) {
                attrs = data;
            } else {
                for (var attr in data) {
                    if (_.isEqual(attrsInitialy[attr], data[attr])) {
                        continue;
                    }
                    (attrs || (attrs = {}))[attr] =    data[attr];
                }
            }

            if (!attrs) {
                this.trigger('cancel:save');
                this.afterNotModified();
                return true;
            }

            model.set(attrs, {silent: true});

            if (this.validate()) {
                model.attributes = attrsBefore;
                this.trigger('cancel:save');
                this.afterNotValid();
                return;
            }

            this.beforeSave();

            this.trigger('before:save');

            model.save(attrs, {
                success: function () {
                    this.afterSave();
                    if (self.isNew) {
                        self.isNew = false;
                    }
                    this.trigger('after:save');
                    model.trigger('after:save');
                    if (!callback) {
                        this.exit('save');
                    } else {
                        callback(this);
                    }
                }.bind(this),
                error: function (e, xhr) {
                    var r = xhr.getAllResponseHeaders();
                    var response = null;

                    if (xhr.status == 409) {
                        var header = xhr.getResponseHeader('X-Status-Reason');
                        try {
                            var response = JSON.parse(header);
                        } catch (e) {
                            console.error('Error while parsing response');
                        }
                    }

                    if (xhr.status == 400) {
                        if (!this.isNew) {
                            this.model.set(this.attributes);
                        }
                    }

                    if (response) {
                        if (response.reason == 'Duplicate') {
                            xhr.errorIsHandled = true;
                            self.showDuplicate(response.data);
                        }
                    }

                    this.afterSaveError();

                    model.attributes = attrsBefore;
                    self.trigger('cancel:save');

                }.bind(this),
                patch: !model.isNew()
            });
            return true;
        },

        fetch: function () {
            var data = {};
            var fields = this.getFields();
            for (var i in fields) {
                if (fields[i].mode == 'edit') {
                    if (fields[i].enabled && !fields[i].readOnly) {
                        _.extend(data, fields[i].fetch());
                    }
                }
            };
            return data;
        },

        showDuplicate: function (duplicates) {
        },

        _handleDependencyAttributes: function () {
            Object.keys(this.dependencyDefs || {}).forEach(function (attr) {
                this._handleDependencyAttribute(attr);
            }, this);
        },

        _handleDependencyAttribute: function (attr) {
            var data = this.dependencyDefs[attr];
            var value = this.model.get(attr);
            if (value in (data.map || {})) {
                (data.map[value] || []).forEach(function (item) {
                    this._doDependencyAction(item);
                }, this);
            } else {
                if ('default' in data) {
                    (data.default || []).forEach(function (item) {
                        this._doDependencyAction(item);
                    }, this);
                }
            }
        },

        _doDependencyAction: function (data) {
            var action = data.action;

            var methodName = 'dependencyAction' + Espo.Utils.upperCaseFirst(action);
            if (methodName in this && typeof this.methodName == 'function') {
                this.methodName(data);
                return;
            }

            var fields = data.fields || [];
            var panels = data.panels || [];

            switch (action) {
                case 'hide':
                    fields.forEach(function (item) {
                        this.hideField(item);
                    }, this);
                    panels.forEach(function (item) {
                        this.hidePanel(item);
                    }, this);
                    break;
                case 'show':
                    fields.forEach(function (item) {
                        this.showField(item);
                    }, this);
                    panels.forEach(function (item) {
                        this.showPanel(item);
                    }, this);
                    break;
                case 'setRequired':
                    fields.forEach(function (field) {
                        var fieldView = this.getFieldView(field);
                        if (fieldView) {
                            fieldView.setRequired();
                        }
                    }, this);
                    break;
                case 'setNotRequired':
                    fields.forEach(function (field) {
                        var fieldView = this.getFieldView(field);
                        if (fieldView) {
                            fieldView.setRequired();
                        }
                    }, this);
                    break;
                case 'setReadOnly':
                    fields.forEach(function (field) {
                        this.setFieldReadOnly(field);
                    }, this);
                    break;
                case 'setNotReadOnly':
                    fields.forEach(function (field) {
                        this.setFieldNotReadOnly(field);
                    }, this);
                    break;
            }
        },

        createField: function (name, view, params, mode, readOnly) {
            var o = {
                model: this.model,
                mode: mode || 'edit',
                el: this.options.el + ' .field-' + name,
                defs: {
                    name: name,
                    params: params || {}
                }
            };
            if (readOnly) {
                o.readOnly = true;
            }
            this.createView(name, view, o);

            if (!~this.fieldList.indexOf(name)) {
                this.fieldList.push(name);
            }
        }

    });

});

