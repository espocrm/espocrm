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

Espo.define('views/record/base', ['view', 'view-record-helper'], function (Dep, ViewRecordHelper) {

    return Dep.extend({

        type: 'edit',

        fieldsMode: 'edit',

        scope: null,

        isNew: false,

        dependencyDefs: {},

        fieldList: null,

        mode: null,

        hideField: function (name) {
            this.recordHelper.setFieldStateParam(name, 'hidden', true);

            var processHtml = function () {
                var $field = this.$el.find('div.field[data-name="' + name + '"]');
                var $label = this.$el.find('label.control-label[data-name="' + name + '"]');
                var $cell = $field.closest('.cell[data-name="' + name + '"]');

                $field.addClass('hidden');
                $label.addClass('hidden');
                $cell.addClass('hidden-cell');
            }.bind(this);
            if (this.isRendered()) {
                processHtml();
            } else {
                this.once('after:render', function () {
                    //processHtml();
                }, this);
            }

            var view = this.getFieldView(name);
            if (view) {
                view.disabled = true;
            }
        },

        showField: function (name) {
            this.recordHelper.setFieldStateParam(name, 'hidden', false);

            var processHtml = function () {
                var $field = this.$el.find('div.field[data-name="' + name + '"]');
                var $label = this.$el.find('label.control-label[data-name="' + name + '"]');
                var $cell = $field.closest('.cell[data-name="' + name + '"]');

                $field.removeClass('hidden');
                $label.removeClass('hidden');
                $cell.removeClass('hidden-cell');
            }.bind(this);

            if (this.isRendered()) {
                processHtml();
            } else {
                this.once('after:render', function () {
                    //processHtml();
                }, this);
            }

            var view = this.getFieldView(name);
            if (view) {
                view.disabled = false;
            }
        },

        setFieldReadOnly: function (name) {
            this.recordHelper.setFieldStateParam(name, 'readOnly', true);

            var view = this.getFieldView(name);
            if (view) {
                if (!view.readOnly) {
                    view.setReadOnly();
                }
            }
        },

        setFieldNotReadOnly: function (name) {
            this.recordHelper.setFieldStateParam(name, 'readOnly', false);

            var view = this.getFieldView(name);
            if (view) {
                if (view.readOnly) {
                    view.setNotReadOnly();
                    if (this.mode == 'edit') {
                        if (!view.readOnlyLocked && view.mode == 'detail') {
                            view.setMode('edit');
                            if (view.isRendered()) {
                                view.reRender();
                            }
                        }
                    }
                }
            }
        },

        setFieldRequired: function (name) {
            this.recordHelper.setFieldStateParam(name, 'required', true);

            var view = this.getFieldView(name);
            if (view) {
                var view = this.getFieldView(name);
                if (view) {
                    view.setRequired();
                }
            }
        },

        setFieldNotRequired: function (name) {
            this.recordHelper.setFieldStateParam(name, 'required', false);

            var view = this.getFieldView(name);
            if (view) {
                var view = this.getFieldView(name);
                if (view) {
                    view.setNotRequired();
                }
            }
        },

        showPanel: function (name) {
            this.recordHelper.setPanelStateParam(name, 'hidden', false);
            if (this.isRendered()) {
                this.$el.find('.panel[data-name="'+name+'"]').removeClass('hidden');
            }
        },

        hidePanel: function (name) {
            this.recordHelper.setPanelStateParam(name, 'hidden', true);
            if (this.isRendered()) {
                this.$el.find('.panel[data-name="'+name+'"]').addClass('hidden');
            }
        },

        setConfirmLeaveOut: function (value) {
            this.getRouter().confirmLeaveOut = value;
        },

        getFieldViews: function () {
            var fields = {};
            this.fieldList.forEach(function (item) {
                var view = this.getFieldView(item);
                if (view) {
                    fields[item] = view;
                }
            }, this);
            return fields;
        },

        getFields: function () {
            return this.getFieldViews();
        },

        getFieldView: function (name) {
            return this.getView(name) || null;
        },

        getField: function (name) {
            return this.getFieldView(name);
        },

        data: function () {
            return {
                hiddenPanels: this.recordHelper.getHiddenPanels(),
                hiddenFields: this.recordHelper.getHiddenFields()
            };
        },

        setup: function () {
            if (typeof this.model === 'undefined') {
                throw new Error('Model has not been injected into record view.');
            }

            this.recordHelper = new ViewRecordHelper();

            this.on('remove', function () {
                if (this.isChanged) {
                    this.model.set(this.attributes);
                }
                this.setIsNotChanged();
            }, this);

            this.events = this.events || {};

            this.scope = this.model.name;
            this.fieldList = this.options.fieldList || this.fieldList || [];

            this.numId = Math.floor((Math.random() * 10000) + 1);
            this.id = Espo.Utils.toDom(this.scope) + '-' + Espo.Utils.toDom(this.type) + '-' + this.numId;

            if (this.model.isNew()) {
                this.isNew = true;
            }

            this.attributes = this.model.getClonedAttributes();

            if (this.options.attributes) {
                this.model.set(this.options.attributes);
            }

            this._initDependancy();

            this.listenTo(this.model, 'change', function () {
                if (this.mode == 'edit') {
                    this.setIsChanged();
                }
            }, this);
        },

        applyDependancy: function () {
            this._handleDependencyAttributes();
        },

        _initDependancy: function () {
            Object.keys(this.dependencyDefs || {}).forEach(function (attr) {
                this.listenTo(this.model, 'change:' + attr, function () {
                    this._handleDependencyAttribute(attr);
                }, this);
            }, this);
            this._handleDependencyAttributes();
        },

        setIsChanged: function () {
            this.isChanged = true;
        },

        setIsNotChanged: function () {
            this.isChanged = false;
        },

        validate: function () {
            var notValid = false;
            var fields = this.getFields();
            for (var i in fields) {
                if (fields[i].mode == 'edit') {
                    if (!fields[i].disabled && !fields[i].readOnly) {
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
            this.setIsNotChanged();
        },

        beforeBeforeSave: function () {

        },

        beforeSave: function () {
            this.notify('Saving...');
        },

        afterSaveError: function () {
        },

        afterNotModified: function () {
            var msg = this.translate('notModified', 'messages');
            Espo.Ui.warning(msg, 'warning');
            this.setIsNotChanged();
        },

        afterNotValid: function () {
            this.notify('Not valid', 'error');
        },

        save: function (callback) {
            this.beforeBeforeSave();

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
                    if (!fields[i].disabled && !fields[i].readOnly) {
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
                        this.setFieldRequired(field);
                    }, this);
                    break;
                case 'setNotRequired':
                    fields.forEach(function (field) {
                        this.setFieldNotRequired(field);
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
                el: this.options.el + ' .field[data-name="' + name + '"]',
                defs: {
                    name: name,
                    params: params || {}
                }
            };
            if (readOnly) {
                o.readOnly = true;
            }

            if (this.recordHelper.getFieldStateParam(name, 'hidden')) {
                o.disabled = true;
            }
            if (this.recordHelper.getFieldStateParam(name, 'readOnly')) {
                o.readOnly = true;
            }
            if (this.recordHelper.getFieldStateParam(name, 'required') !== null) {
                o.defs.params.required = this.recordHelper.getFieldStateParam(name, 'required');
            }

            this.createView(name, view, o);

            if (!~this.fieldList.indexOf(name)) {
                this.fieldList.push(name);
            }
        },

        exit: function () {}

    });

});

