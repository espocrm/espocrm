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

define(
    'views/record/base',
    ['view', 'view-record-helper', 'dynamic-logic', 'lib!underscore'],
    function (Dep, ViewRecordHelper, DynamicLogic, _) {

    return Dep.extend({

        type: 'edit',

        fieldsMode: 'edit',

        entityType: null,

        scope: null,

        isNew: false,

        dependencyDefs: {},

        dynamicLogicDefs: {},

        fieldList: null,

        mode: null,

        lastSaveCancelReason: null,

        hideField: function (name, locked) {
            this.recordHelper.setFieldStateParam(name, 'hidden', true);

            if (locked) {
                this.recordHelper.setFieldStateParam(name, 'hiddenLocked', true);
            }

            var processHtml = function () {
                var fieldView = this.getFieldView(name);

                if (fieldView) {
                    var $field = fieldView.$el;
                    var $cell = $field.closest('.cell[data-name="' + name + '"]');
                    var $label = $cell.find('label.control-label[data-name="' + name + '"]');

                    $field.addClass('hidden');
                    $label.addClass('hidden');
                    $cell.addClass('hidden-cell');
                }
                else {
                    this.$el.find('.cell[data-name="' + name + '"]').addClass('hidden-cell');
                    this.$el.find('.field[data-name="' + name + '"]').addClass('hidden');
                    this.$el.find('label.control-label[data-name="' + name + '"]').addClass('hidden');
                }
            }.bind(this);

            if (this.isRendered()) {
                processHtml();
            }
            else {
                this.once('after:render', function () {
                    processHtml();
                }, this);
            }

            var view = this.getFieldView(name);

            if (view) {
                view.setDisabled(locked);
            }
        },

        showField: function (name) {
            if (this.recordHelper.getFieldStateParam(name, 'hiddenLocked')) {
                return;
            }

            this.recordHelper.setFieldStateParam(name, 'hidden', false);

            var processHtml = function () {
                var fieldView = this.getFieldView(name);

                if (fieldView) {
                    var $field = fieldView.$el;
                    var $cell = $field.closest('.cell[data-name="' + name + '"]');
                    var $label = $cell.find('label.control-label[data-name="' + name + '"]');

                    $field.removeClass('hidden');
                    $label.removeClass('hidden');
                    $cell.removeClass('hidden-cell');
                }
                else {
                    this.$el.find('.cell[data-name="' + name + '"]').removeClass('hidden-cell');
                    this.$el.find('.field[data-name="' + name + '"]').removeClass('hidden');
                    this.$el.find('label.control-label[data-name="' + name + '"]').removeClass('hidden');
                }
            }.bind(this);

            if (this.isRendered()) {
                processHtml();
            }
            else {
                this.once('after:render', function () {
                    processHtml();
                }, this);
            }

            var view = this.getFieldView(name);

            if (view) {
                if (!view.disabledLocked) {
                    view.setNotDisabled();
                }
            }
        },

        setFieldReadOnly: function (name, locked) {
            let previousvalue = this.recordHelper.getFieldStateParam(name, 'readOnly');

            this.recordHelper.setFieldStateParam(name, 'readOnly', true);

            if (locked) {
                this.recordHelper.setFieldStateParam(name, 'readOnlyLocked', true);
            }

            var view = this.getFieldView(name);

            if (view) {
                view.setReadOnly(locked);
            }

            if (!previousvalue) {
                this.trigger('set-field-read-only', name);
            }
        },

        setFieldNotReadOnly: function (name) {
            let previousvalue = this.recordHelper.getFieldStateParam(name, 'readOnly');

            this.recordHelper.setFieldStateParam(name, 'readOnly', false);

            var view = this.getFieldView(name);

            if (view) {
                if (view.readOnly) {
                    view.setNotReadOnly();

                    if (this.mode === 'edit') {
                        if (!view.readOnlyLocked && view.mode === 'detail') {
                            view.setMode('edit');
                            if (view.isRendered()) {
                                view.reRender();
                            }
                        }
                    }
                }
            }

            if (previousvalue) {
                this.trigger('set-field-not-read-only', name);
            }
        },

        setFieldRequired: function (name) {
            let previousvalue = this.recordHelper.getFieldStateParam(name, 'required');

            this.recordHelper.setFieldStateParam(name, 'required', true);

            var view = this.getFieldView(name);
            if (view) {
                view.setRequired();
            }

            if (!previousvalue) {
                this.trigger('set-field-required', name);
            }
        },

        setFieldNotRequired: function (name) {
            let previousvalue = this.recordHelper.getFieldStateParam(name, 'required');

            this.recordHelper.setFieldStateParam(name, 'required', false);

            var view = this.getFieldView(name);

            if (view) {
                view.setNotRequired();
            }

            if (previousvalue) {
                this.trigger('set-field-not-required', name);
            }
        },

        setFieldOptionList: function (name, list) {
            let had = this.recordHelper.hasFieldOptionList(name);
            let previousList = this.recordHelper.getFieldOptionList(name);

            this.recordHelper.setFieldOptionList(name, list);

            var view = this.getFieldView(name);
            if (view) {
                if ('setOptionList' in view) {
                    view.setOptionList(list);
                }
            }

            if (!had || !_(previousList).isEqual(list)) {
                this.trigger('set-field-option-list', name, list);
            }
        },

        resetFieldOptionList: function (name) {
            let had = this.recordHelper.hasFieldOptionList(name);

            this.recordHelper.clearFieldOptionList(name);

            var view = this.getFieldView(name);

            if (view) {
                if ('resetOptionList' in view) {
                    view.resetOptionList();
                }
            }

            if (had) {
                this.trigger('reset-field-option-list', name);
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

        stylePanel: function (name) {
            this.recordHelper.setPanelStateParam(name, 'styled', true);

            var process = function () {
                var $panel = this.$el.find('.panel[data-name="'+name+'"]');
                var $btn = $panel.find('> .panel-heading .btn');

                var style = $panel.attr('data-style');

                if (!style) {
                    return;
                }

                $panel.removeClass('panel-default');
                $panel.addClass('panel-' + style);

                $btn.removeClass('btn-default');
                $btn.addClass('btn-' + style);
            }.bind(this);

            if (this.isRendered()) {
                process();

                return;
            }

            this.once('after:render', function () {
                process();
            }, this);
        },

        unstylePanel: function (name) {
            this.recordHelper.setPanelStateParam(name, 'styled', false);

            var process = function () {
                var $panel = this.$el.find('.panel[data-name="'+name+'"]');
                var $btn = $panel.find('> .panel-heading .btn');

                var style = $panel.attr('data-style');

                if (!style) {
                    return;
                }

                $panel.removeClass('panel-' + style);
                $panel.addClass('panel-default');

                $btn.removeClass('btn-' + style);
                $btn.addClass('btn-default');
            }.bind(this);

            if (this.isRendered()) {
                process();

                return;
            }

            this.once('after:render', function () {
                process();
            }, this);
        },

        setConfirmLeaveOut: function (value) {
            if (!this.getRouter()) {
                return;
            }

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
            var view =  this.getView(name + 'Field') || null;

            // TODO remove
            if (!view) {
                view = this.getView(name) || null;
            }

            return view;
        },

        getField: function (name) {
            return this.getFieldView(name);
        },

        getFieldList: function () {
            var fieldViews = this.getFieldViews();

            return Object.keys(fieldViews);
        },

        getFieldViewList: function () {
            return this.getFieldList()
                .map(field => this.getFieldView(field))
                .filter(view => view !== null);
        },

        data: function () {
            return {
                scope: this.scope,
                entityType: this.entityType,
                hiddenPanels: this.recordHelper.getHiddenPanels(),
                hiddenFields: this.recordHelper.getHiddenFields(),
            };
        },

        // TODO remove
        handleDataBeforeRender: function (data) {
            this.getFieldList().forEach(function (field) {
                var viewKey = field + 'Field';

                data[field] = data[viewKey];
            }, this);
        },

        setup: function () {
            if (typeof this.model === 'undefined') {
                throw new Error('Model has not been injected into record view.');
            }

            this.recordHelper = new ViewRecordHelper();

            this.once('remove', function () {
                if (this.isChanged) {
                    this.resetModelChanges();
                }

                this.setIsNotChanged();
            }, this);

            this.events = this.events || {};

            this.entityType = this.model.name;
            this.scope = this.options.scope || this.entityType;

            this.fieldList = this.options.fieldList || this.fieldList || [];

            this.numId = Math.floor((Math.random() * 10000) + 1);

            this.id = Espo.Utils.toDom(this.entityType) + '-' + Espo.Utils.toDom(this.type) + '-' + this.numId;

            if (this.model.isNew()) {
                this.isNew = true;
            }

            this.setupBeforeFinal();
        },

        setupBeforeFinal: function () {
            this.attributes = this.model.getClonedAttributes();

            this.listenTo(this.model, 'change', function () {
                if (this.mode === 'edit') {
                    this.setIsChanged();
                }
            }, this);

            if (this.options.attributes) {
                this.model.set(this.options.attributes);
            }

            this.listenTo(this.model, 'sync', (m, o) => {
                 this.attributes = this.model.getClonedAttributes();
            });

            this.initDependancy();
            this.initDynamicLogic();
        },

        setInitalAttributeValue: function (attribute, value) {
            this.attributes[attribute] = value;
        },

        checkAttributeIsChanged: function (name) {
            return !_.isEqual(this.attributes[name], this.model.get(name));
        },

        resetModelChanges: function () {
            if (this.updatedAttributes) {
                this.attributes = this.updatedAttributes;

                this.updatedAttributes = null;
            }

            var attributes = this.model.attributes;

            for (var attr in attributes) {
                if (!(attr in this.attributes)) {
                    this.model.unset(attr);
                }
            }

            this.model.set(this.attributes, {skipReRender: true});
        },

        setModelAttributes: function (setAttributes, options) {
            for (var item in this.model.attributes) {
                if (!(item in setAttributes)) {
                    this.model.unset(item);
                }
            }
            this.model.set(setAttributes, options || {});
        },

        initDynamicLogic: function () {
            this.dynamicLogicDefs = Espo.Utils.clone(this.dynamicLogicDefs || {});
            this.dynamicLogicDefs.fields = Espo.Utils.clone(this.dynamicLogicDefs.fields);
            this.dynamicLogicDefs.panels = Espo.Utils.clone(this.dynamicLogicDefs.panels);

            this.dynamicLogic = new DynamicLogic(this.dynamicLogicDefs, this);

            this.listenTo(this.model, 'change', this.processDynamicLogic, this);
            this.processDynamicLogic();
        },

        processDynamicLogic: function () {
            this.dynamicLogic.process();
        },

        applyDependancy: function () {
            this._handleDependencyAttributes();
        },

        initDependancy: function () {
            Object.keys(this.dependencyDefs || {}).forEach(function (attr) {
                this.listenTo(this.model, 'change:' + attr, function () {
                    this._handleDependencyAttribute(attr);
                }, this);
            }, this);

            this._handleDependencyAttributes();
        },

        setupFieldLevelSecurity: function () {
            var forbiddenFieldList = this.getAcl().getScopeForbiddenFieldList(this.entityType, 'read');

            forbiddenFieldList.forEach(function (field) {
                this.hideField(field, true);
            }, this);

            var readOnlyFieldList = this.getAcl().getScopeForbiddenFieldList(this.entityType, 'edit');

            readOnlyFieldList.forEach(function (field) {
                this.setFieldReadOnly(field, true);
            }, this);
        },

        setIsChanged: function () {
            this.isChanged = true;
        },

        setIsNotChanged: function () {
            this.isChanged = false;
        },

        validate: function () {
            var notValid = false;

            var fieldViews = this.getFieldViews();

            var invalidFieldMap = {};

            for (var field in fieldViews) {
                var fieldView = fieldViews[field];

                if (fieldView.mode === 'edit' && !fieldView.disabled && !fieldView.readOnly) {
                    var fieldInvalid = fieldView.validate();

                    invalidFieldMap[field] = fieldInvalid;

                    notValid = fieldInvalid  || notValid;
                }

                if (
                    !invalidFieldMap[field] &&
                    this.dynamicLogic &&
                    this.dynamicLogicDefs &&
                    this.dynamicLogicDefs.fields &&
                    this.dynamicLogicDefs.fields[field] &&
                    this.dynamicLogicDefs.fields[field].invalid &&
                    this.dynamicLogicDefs.fields[field].invalid.conditionGroup
                ) {
                    var invalidConditionGroup = this.dynamicLogicDefs.fields[field].invalid.conditionGroup;

                    var fieldInvalid = this.dynamicLogic.checkConditionGroup(invalidConditionGroup);

                    notValid = fieldInvalid  || notValid;

                    if (fieldInvalid) {
                        var msg =
                            this.translate('fieldInvalid', 'messages')
                                .replace('{field}', this.translate(field, 'fields', this.entityType));

                        fieldView.showValidationMessage(msg);

                        fieldView.trigger('invalid');
                    }
                }
            }

            return notValid;
        },

        afterSave: function () {
            if (this.isNew) {
                this.notify('Created', 'success');
            }
            else {
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

        save: function (options) {
            options = options || {};

            var headers = options.headers || {};

            this.lastSaveCancelReason = null;

            this.beforeBeforeSave();

            var data = this.fetch();

            var model = this.model;

            var initialAttributes = this.attributes;

            var beforeSaveAttributes = this.model.getClonedAttributes();

            data = _.extend(Espo.Utils.cloneDeep(beforeSaveAttributes), data);

            var setAttributes = false;

            if (model.isNew()) {
                setAttributes = data;
            }
            else {
                for (var name in data) {
                    if (_.isEqual(initialAttributes[name], data[name])) {
                        continue;
                    }

                    (setAttributes || (setAttributes = {}))[name] = data[name];
                }
            }

            if (!setAttributes) {
                this.afterNotModified();

                this.lastSaveCancelReason = 'notModified';

                this.trigger('cancel:save', {reason: 'notModified'});

                return new Promise((resolve, reject) => {
                    reject('notModified');
                });
            }

            model.set(setAttributes, {silent: true});

            if (this.validate()) {
                model.attributes = beforeSaveAttributes;

                this.afterNotValid();

                this.lastSaveCancelReason = 'invalid';

                this.trigger('cancel:save', {reason: 'invalid'});

                return new Promise((resolve, reject) => {
                    reject('invalid');
                });
            }

            var optimisticConcurrencyControl = this.getMetadata()
                .get(['entityDefs', this.entityType, 'optimisticConcurrencyControl']);

            if (optimisticConcurrencyControl && this.model.get('versionNumber') !== null) {
                headers['X-Version-Number'] = this.model.get('versionNumber');
            }

            this.beforeSave();

            this.trigger('before:save');
            model.trigger('before:save');

            return new Promise((resolve, reject) => {
                model
                    .save(
                        setAttributes,
                        {
                            patch: !model.isNew(),
                            headers: headers,
                        }
                    )
                    .then(() => {
                        this.trigger('save', initialAttributes);

                        this.afterSave();

                        var isNew = this.isNew;

                        if (this.isNew) {
                            this.isNew = false;
                        }

                        this.trigger('after:save');
                        model.trigger('after:save');

                        resolve();
                    })
                    .catch((xhr) => {
                        this.handleSaveError(xhr);

                        this.afterSaveError();

                        this.setModelAttributes(beforeSaveAttributes);

                        this.lastSaveCancelReason = 'error';

                        this.trigger('error:save');
                        this.trigger('cancel:save', {reason: 'error'});

                        reject('error');
                    });
            });
        },

        handleSaveError: function (xhr) {
            var response = null;

            if (~[409, 500].indexOf(xhr.status)) {
                var statusReason = xhr.getResponseHeader('X-Status-Reason');

                if (statusReason) {
                    try {
                        var response = JSON.parse(statusReason);
                    }
                    catch (e) {}

                    if (!response && xhr.responseText) {
                        response = {
                            reason: statusReason.toString(),
                        };

                        try {
                            var data = JSON.parse(xhr.responseText);
                        }
                        catch (e) {
                            console.error('Could not parse error response body.');

                            return;
                        }

                        response.data = data;
                    }
                }
            }

            if (!response || !response.reason) {
                return;
            }

            var reason = response.reason;

            var handlerName =
                this.getMetadata()
                    .get(['clientDefs', this.scope, 'saveErrorHandlers', reason]) ||
                this.getMetadata()
                    .get(['clientDefs', 'Global', 'saveErrorHandlers', reason]);

            if (handlerName) {
                require(handlerName, (Handler) => {
                    var handler = new Handler(this);

                    handler.process(response.data);
                });

                xhr.errorIsHandled = true;

                return;
            }

            var methodName = 'errorHandler' + Espo.Utils.upperCaseFirst(reason);

            if (methodName in this) {
                xhr.errorIsHandled = true;

                this[methodName](response.data);
            }
        },

        fetch: function () {
            var data = {};
            var fieldViews = this.getFieldViews();

            for (var i in fieldViews) {
                var view = fieldViews[i];

                if (view.mode === 'edit') {
                    if (!view.disabled && !view.readOnly && view.isFullyRendered()) {
                        _.extend(data, view.fetch());
                    }
                }
            };

            return data;
        },

        processFetch: function () {
            var data = this.fetch();

            this.model.set(data);

            if (this.validate()) {
                return;
            }

            return data;
        },

        populateDefaults: function () {
            this.model.populateDefaults();

            var defaultHash = {};

            if (!this.getUser().get('portalId')) {
                if (this.model.hasField('assignedUser') || this.model.hasField('assignedUsers')) {
                    var assignedUserField = 'assignedUser';

                    if (this.model.hasField('assignedUsers')) {
                        assignedUserField = 'assignedUsers';
                    }

                    var fillAssignedUser = true;

                    if (this.getPreferences().get('doNotFillAssignedUserIfNotRequired')) {
                        fillAssignedUser = false;

                        if (this.model.getFieldParam(assignedUserField, 'required')) {
                            fillAssignedUser = true;
                        }
                        else if (this.getAcl().get('assignmentPermission') === 'no') {
                            fillAssignedUser = true;
                        }
                        else if (
                            this.getAcl().get('assignmentPermission') === 'team' &&
                            !this.getUser().get('defaultTeamId')
                        ) {
                            fillAssignedUser = true;
                        }
                        else if (
                            ~this.getAcl()
                                .getScopeForbiddenFieldList(this.model.name, 'edit').indexOf(assignedUserField)
                            ) {

                            fillAssignedUser = true;
                        }
                    }

                    if (fillAssignedUser) {
                        if (assignedUserField === 'assignedUsers') {
                            defaultHash['assignedUsersIds'] = [this.getUser().id];
                            defaultHash['assignedUsersNames'] = {};

                            defaultHash['assignedUsersNames'][this.getUser().id] = this.getUser().get('name');
                        }
                        else {
                            defaultHash['assignedUserId'] = this.getUser().id;

                            defaultHash['assignedUserName'] = this.getUser().get('name');
                        }
                    }
                }

                var defaultTeamId = this.getUser().get('defaultTeamId');

                if (defaultTeamId) {
                    if (this.model.hasField('teams') && !this.model.getFieldParam('teams', 'default')) {
                        defaultHash['teamsIds'] = [defaultTeamId];
                        defaultHash['teamsNames'] = {};
                        defaultHash['teamsNames'][defaultTeamId] = this.getUser().get('defaultTeamName');
                    }
                }
            }

            if (this.getUser().get('portalId')) {
                if (
                    this.model.hasField('account') &&
                    ~['belongsTo', 'hasOne'].indexOf(this.model.getLinkType('account'))
                ) {
                    if (this.getUser().get('accountId')) {
                        defaultHash['accountId'] =  this.getUser().get('accountId');
                        defaultHash['accountName'] = this.getUser().get('accountName');
                    }
                }

                if (
                    this.model.hasField('contact') &&
                    ~['belongsTo', 'hasOne'].indexOf(this.model.getLinkType('contact'))
                ) {
                    if (this.getUser().get('contactId')) {
                        defaultHash['contactId'] = this.getUser().get('contactId');
                        defaultHash['contactName'] = this.getUser().get('contactName');
                    }
                }

                if (this.model.hasField('parent') && this.model.getLinkType('parent') === 'belongsToParent') {
                    if (!this.getConfig().get('b2cMode')) {
                        if (this.getUser().get('accountId')) {
                            if (~(this.model.getFieldParam('parent', 'entityList') || []).indexOf('Account')) {
                                defaultHash['parentId'] = this.getUser().get('accountId');
                                defaultHash['parentName'] = this.getUser().get('accountName');
                                defaultHash['parentType'] = 'Account';
                            }
                        }
                    }
                    else {
                        if (this.getUser().get('contactId')) {
                            if (~(this.model.getFieldParam('parent', 'entityList') || []).indexOf('Contact')) {
                                defaultHash['contactId'] = this.getUser().get('contactId');
                                defaultHash['parentName'] = this.getUser().get('contactName');
                                defaultHash['parentType'] = 'Contact';
                            }
                        }
                    }
                }

                if (this.model.hasField('accounts') && this.model.getLinkType('accounts') === 'hasMany') {
                    if (this.getUser().get('accountsIds')) {
                        defaultHash['accountsIds'] = this.getUser().get('accountsIds');
                        defaultHash['accountsNames'] = this.getUser().get('accountsNames');
                    }
                }

                if (this.model.hasField('contacts') && this.model.getLinkType('contacts') === 'hasMany') {
                    if (this.getUser().get('contactId')) {
                        defaultHash['contactsIds'] = [this.getUser().get('contactId')];

                        var names = {};

                        names[this.getUser().get('contactId')] = this.getUser().get('contactName');
                        defaultHash['contactsNames'] = names;
                    }
                }
            }

            for (var attr in defaultHash) {
                if (this.model.has(attr)) {
                    delete defaultHash[attr];
                }
            }

            this.model.set(defaultHash, {silent: true});
        },

        errorHandlerDuplicate: function (duplicates) {
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
            if (methodName in this && typeof this.methodName === 'function') {
                this.methodName(data);

                return;
            }

            var fieldList = data.fieldList || data.fields || [];
            var panelList = data.panelList || data.panels || [];

            switch (action) {
                case 'hide':
                    panelList.forEach(function (item) {
                        this.hidePanel(item);
                    }, this);

                    fieldList.forEach(function (item) {
                        this.hideField(item);
                    }, this);

                    break;

                case 'show':
                    panelList.forEach(function (item) {
                        this.showPanel(item);
                    }, this);

                    fieldList.forEach(function (item) {
                        this.showField(item);
                    }, this);

                    break;

                case 'setRequired':
                    fieldList.forEach(function (field) {
                        this.setFieldRequired(field);
                    }, this);

                    break;

                case 'setNotRequired':
                    fieldList.forEach(function (field) {
                        this.setFieldNotRequired(field);
                    }, this);

                    break;

                case 'setReadOnly':
                    fieldList.forEach(function (field) {
                        this.setFieldReadOnly(field);
                    }, this);

                    break;

                case 'setNotReadOnly':
                    fieldList.forEach(function (field) {
                        this.setFieldNotReadOnly(field);
                    }, this);

                    break;
            }
        },

        createField: function (name, view, params, mode, readOnly, options) {
            var o = {
                model: this.model,
                mode: mode || 'edit',
                el: this.options.el + ' .field[data-name="' + name + '"]',
                defs: {
                    name: name,
                    params: params || {},
                },
            };

            if (readOnly) {
                o.readOnly = true;
            }

            view = view || this.model.getFieldParam(name, 'view');

            if (!view) {
                var type = this.model.getFieldType(name) || 'base';
                view = this.getFieldManager().getViewName(type);
            }

            if (options) {
                for (var param in options) {
                    o[param] = options[param];
                }
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

            if (this.recordHelper.hasFieldOptionList(name)) {
                o.customOptionList = this.recordHelper.getFieldOptionList(name);
            }

            var viewKey = name + 'Field';

            this.createView(viewKey, view, o);

            if (!~this.fieldList.indexOf(name)) {
                this.fieldList.push(name);
            }
        },

        exit: function (after) {},

    });
});
