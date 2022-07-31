/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2022 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

    /**
     * A base record view. To be extended.
     *
     * @class
     * @name Class
     * @extends module:view.Class
     * @memberOf module:views/record/base
     */
    return Dep.extend(/** @lends module:views/record/base.Class# */{

        /**
         * A type.
         */
        type: 'edit',

        /**
         * An entity type.
         */
        entityType: null,

        /**
         * A scope.
         */
        scope: null,

        /**
         * Is new. Is set automatically.
         */
        isNew: false,

        /**
         * @deprecated
         * @protected
         */
        dependencyDefs: {},

        /**
         * Dynamic logic.
         *
         * @protected
         * @type {Object}
         */
        dynamicLogicDefs: {},

        /**
         * A field list.
         *
         * @protected
         */
        fieldList: null,

        /**
         * A mode.
         *
         * @type {'detail'|'edit'|null}
         */
        mode: null,

        /**
         * A last save cancel reason.
         *
         * @protected
         * @type {string|null}
         */
        lastSaveCancelReason: null,

        /**
         * A record-helper.
         *
         * @protected
         * @type {module:view-record-helper.Class}
         */
        recordHelper: null,

        /**
         * @const
         */
        MODE_DETAIL: 'detail',

        /**
         * @const
         */
        MODE_EDIT: 'edit',

        /**
         * @const
         */
        TYPE_DETAIL: 'detail',

        /**
         * @const
         */
        TYPE_EDIT: 'edit',

        /**
         * Hide a field.
         *
         * @param {string} name A field name.
         * @param {boolean } [locked] To lock. Won't be able to un-hide.
         */
        hideField: function (name, locked) {
            this.recordHelper.setFieldStateParam(name, 'hidden', true);

            if (locked) {
                this.recordHelper.setFieldStateParam(name, 'hiddenLocked', true);
            }

            var processHtml = () => {
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
            };

            if (this.isRendered()) {
                processHtml();
            }
            else {
                this.once('after:render', () => {
                    processHtml();
                });
            }

            let view = this.getFieldView(name);

            if (view) {
                view.setDisabled(locked);
            }
        },

        /**
         * Show a field.
         *
         * @param {string} name A field name.
         */
        showField: function (name) {
            if (this.recordHelper.getFieldStateParam(name, 'hiddenLocked')) {
                return;
            }

            this.recordHelper.setFieldStateParam(name, 'hidden', false);

            var processHtml = () => {
                var fieldView = this.getFieldView(name);

                if (fieldView) {
                    var $field = fieldView.$el;
                    var $cell = $field.closest('.cell[data-name="' + name + '"]');
                    var $label = $cell.find('label.control-label[data-name="' + name + '"]');

                    $field.removeClass('hidden');
                    $label.removeClass('hidden');
                    $cell.removeClass('hidden-cell');

                    return;
                }

                this.$el.find('.cell[data-name="' + name + '"]').removeClass('hidden-cell');
                this.$el.find('.field[data-name="' + name + '"]').removeClass('hidden');
                this.$el.find('label.control-label[data-name="' + name + '"]').removeClass('hidden');
            };

            if (this.isRendered()) {
                processHtml();
            }
            else {
                this.once('after:render', () => {
                    processHtml();
                });
            }

            let view = this.getFieldView(name);

            if (view) {
                if (!view.disabledLocked) {
                    view.setNotDisabled();
                }
            }
        },

        /**
         * Set a field as read-only.
         *
         * @param {string} name A field name.
         * @param {boolean } [locked] To lock. Won't be able to un-set.
         */
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

        /**
         * Set a field as not read-only.
         *
         * @param {string} name A field name.
         */
        setFieldNotReadOnly: function (name) {
            let previousvalue = this.recordHelper.getFieldStateParam(name, 'readOnly');

            this.recordHelper.setFieldStateParam(name, 'readOnly', false);

            var view = this.getFieldView(name);

            if (view) {
                if (view.readOnly) {
                    view.setNotReadOnly();

                    if (this.mode === this.MODE_EDIT) {
                        if (!view.readOnlyLocked && view.isDetailMode()) {
                            view.setEditMode()
                                .then(() => view.reRender());
                        }
                    }
                }
            }

            if (previousvalue) {
                this.trigger('set-field-not-read-only', name);
            }
        },

        /**
         * Set a field as required.
         *
         * @param {string} name A field name.
         */
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

        /**
         * Set a field as not required.
         *
         * @param {string} name A field name.
         */
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

        /**
         * Set an option list for a field.
         *
         * @param {string} name A field name.
         * @param {string[]} list Options.
         */
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

        /**
         * Reset field options (revert to default).
         *
         * @param {string} name A field name.
         */
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

        /**
         * Show a panel.
         *
         * @param {string} name A panel name.
         */
        showPanel: function (name) {
            this.recordHelper.setPanelStateParam(name, 'hidden', false);

            if (this.isRendered()) {
                this.$el.find('.panel[data-name="'+name+'"]').removeClass('hidden');
            }
        },

        /**
         * Hide a panel.
         *
         * @param {string} name A panel name.
         */
        hidePanel: function (name) {
            this.recordHelper.setPanelStateParam(name, 'hidden', true);

            if (this.isRendered()) {
                this.$el.find('.panel[data-name="'+name+'"]').addClass('hidden');
            }
        },

        /**
         * Style a panel. Style is set in the `data-style` DOM attribute.
         *
         * @param {string} name A panel name.
         */
        stylePanel: function (name) {
            this.recordHelper.setPanelStateParam(name, 'styled', true);

            var process = () => {
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
            };

            if (this.isRendered()) {
                process();

                return;
            }

            this.once('after:render', () => {
                process();
            });
        },

        /**
         * Un-style a panel.
         *
         * @param {string} name A panel name.
         */
        unstylePanel: function (name) {
            this.recordHelper.setPanelStateParam(name, 'styled', false);

            var process = () => {
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
            };

            if (this.isRendered()) {
                process();

                return;
            }

            this.once('after:render', () => {
                process();
            });
        },

        /**
         * Set/unset a confirmation upon leaving the form.
         *
         * @param {boolean} value True sets a required confirmation.
         */
        setConfirmLeaveOut: function (value) {
            if (!this.getRouter()) {
                return;
            }

            this.getRouter().confirmLeaveOut = value;
        },

        /**
         * Get field views.
         *
         * @param {boolean} [withHidden] With hidden.
         * @return {Object.<string,module:views/fields/base.Class>}
         */
        getFieldViews: function (withHidden) {
            var fields = {};

            this.fieldList.forEach(item => {
                var view = this.getFieldView(item);

                if (view) {
                    fields[item] = view;
                }
            });

            return fields;
        },

        /**
         * @deprecated Use `getFieldViews`.
         * @return {Object<string, module:views/fields/base.Class>}
         */
        getFields: function () {
            return this.getFieldViews();
        },

        /**
         * Get a field view.
         *
         * @param {string} name A field name.
         * @return {module:views/fields/base.Class|null}
         */
        getFieldView: function (name) {
            /** @type {module:views/fields/base.Class|null} */
            let view =  this.getView(name + 'Field') || null;

            // @todo Remove.
            if (!view) {
                view = this.getView(name) || null;
            }

            return view;
        },

        /**
         * @deprecated Use `getFieldView`.
         * @return {module:views/fields/base.Class|null}
         */
        getField: function (name) {
            return this.getFieldView(name);
        },

        /**
         * Get a field list.
         *
         * @return {string[]}
         */
        getFieldList: function () {
            return Object.keys(this.getFieldViews());
        },

        /**
         * Get a field view list.
         *
         * @return {module:views/fields/base.Class[]}
         */
        getFieldViewList: function () {
            return this.getFieldList()
                .map(field => this.getFieldView(field))
                .filter(view => view !== null);
        },

        /**
         * @inheritDoc
         */
        data: function () {
            return {
                scope: this.scope,
                entityType: this.entityType,
                hiddenPanels: this.recordHelper.getHiddenPanels(),
                hiddenFields: this.recordHelper.getHiddenFields(),
            };
        },

        /**
         * @todo Remove.
         * @private
         */
        handleDataBeforeRender: function (data) {
            this.getFieldList().forEach((field) => {
                var viewKey = field + 'Field';

                data[field] = data[viewKey];
            });
        },

        /**
         * @inheritDoc
         */
        setup: function () {
            if (typeof this.model === 'undefined') {
                throw new Error('Model has not been injected into record view.');
            }

            /** @type {module:view-record-helper.Class} */
            this.recordHelper = new ViewRecordHelper();

            this.dynamicLogicDefs = this.options.dynamicLogicDefs || this.dynamicLogicDefs;

            this.once('remove', () => {
                if (this.isChanged) {
                    this.resetModelChanges();
                }

                this.setIsNotChanged();
            });

            this.events = this.events || {};

            this.entityType = this.model.name;
            this.scope = this.options.scope || this.entityType;

            this.fieldList = this.options.fieldList || this.fieldList || [];

            this.numId = Math.floor((Math.random() * 10000) + 1);

            this.id = Espo.Utils.toDom(this.entityType) + '-' +
                Espo.Utils.toDom(this.type) + '-' + this.numId;

            if (this.model.isNew()) {
                this.isNew = true;
            }

            this.setupBeforeFinal();
        },

        /**
         * Set up before final.
         *
         * @protected
         */
        setupBeforeFinal: function () {
            this.attributes = this.model.getClonedAttributes();

            this.listenTo(this.model, 'change', (m, o) => {
                if (o.sync) {
                    for (let attribute in m.attributes) {
                        if (!m.hasChanged(attribute)) {
                            continue;
                        }

                        this.attributes[attribute] = Espo.Utils.cloneDeep(
                            m.get(attribute)
                        );
                    }

                    return;
                }

                if (this.mode === this.MODE_EDIT) {
                    this.setIsChanged();
                }
            });

            if (this.options.attributes) {
                this.model.set(this.options.attributes);
            }

            this.listenTo(this.model, 'sync', () => {
                 this.attributes = this.model.getClonedAttributes();
            });

            this.initDependancy();
            this.initDynamicLogic();
        },

        /**
         * Set an initial attribute value.
         *
         * @protected
         * @param {string} attribute An attribute name.
         * @param {*} value
         */
        setInitialAttributeValue: function (attribute, value) {
            this.attributes[attribute] = value;
        },

        /**
         * Check whether a current attribute value differs from initial.
         *
         * @param {string} name An attribute name.
         * @return {boolean}
         */
        checkAttributeIsChanged: function (name) {
            return !_.isEqual(this.attributes[name], this.model.get(name));
        },

        /**
         * Reset model changes.
         */
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

        /**
         * Set model attribute values.
         *
         * @param {Object.<string,*>} setAttributes Values.
         * @param {Object.<string,*>} [options] Options.
         */
        setModelAttributes: function (setAttributes, options) {
            for (var item in this.model.attributes) {
                if (!(item in setAttributes)) {
                    this.model.unset(item);
                }
            }

            this.model.set(setAttributes, options || {});
        },

        /**
         * Init dynamic logic.
         *
         * @protected
         */
        initDynamicLogic: function () {
            this.dynamicLogicDefs = Espo.Utils.clone(this.dynamicLogicDefs || {});
            this.dynamicLogicDefs.fields = Espo.Utils.clone(this.dynamicLogicDefs.fields);
            this.dynamicLogicDefs.panels = Espo.Utils.clone(this.dynamicLogicDefs.panels);

            this.dynamicLogic = new DynamicLogic(this.dynamicLogicDefs, this);

            this.listenTo(this.model, 'change', () => this.processDynamicLogic());
            this.processDynamicLogic();
        },

        /**
         * Process dynamic logic.
         *
         * @protected
         */
        processDynamicLogic: function () {
            this.dynamicLogic.process();
        },

        /**
         * @deprecated
         */
        applyDependancy: function () {
            this._handleDependencyAttributes();
        },

        /**
         * @deprecated
         */
        initDependancy: function () {
            Object.keys(this.dependencyDefs || {}).forEach((attr) => {
                this.listenTo(this.model, 'change:' + attr, () => {
                    this._handleDependencyAttribute(attr);
                });
            });

            this._handleDependencyAttributes();
        },

        /**
         * Set up a field level security.
         *
         * @protected
         */
        setupFieldLevelSecurity: function () {
            var forbiddenFieldList = this.getAcl().getScopeForbiddenFieldList(this.entityType, 'read');

            forbiddenFieldList.forEach((field) => {
                this.hideField(field, true);
            });

            let readOnlyFieldList = this.getAcl().getScopeForbiddenFieldList(this.entityType, 'edit');

            readOnlyFieldList.forEach((field) => {
                this.setFieldReadOnly(field, true);
            });
        },

        /**
         * Set is changed.
         *
         * @protected
         */
        setIsChanged: function () {
            this.isChanged = true;
        },

        /**
         * Set is not changed.
         *
         * @protected
         */
        setIsNotChanged: function () {
            this.isChanged = false;
        },

        /**
         * Validate.
         *
         * @return {boolean} True if not valid.
         */
        validate: function () {
            let invalidFieldList = [];

            this.getFieldList().forEach(field => {
                let fieldIsInvalid = this.validateField(field);

                if (fieldIsInvalid) {
                    invalidFieldList.push(field)
                }
            });

            if (!!invalidFieldList.length) {
                this.onInvalid(invalidFieldList);
            }

            return !!invalidFieldList.length;
        },

        /**
         * @protected
         * @param {string[]} invalidFieldList Invalid fields.
         */
        onInvalid: function (invalidFieldList) {},

        /**
         * Validate a specific field.
         *
         * @param {string} field A field name.
         * @return {boolean} True if not valid.
         */
        validateField: function (field) {
            let fieldView = this.getFieldView(field);

            if (!fieldView) {
                return false;
            }

            let notValid = false;

            if (
                fieldView.isEditMode() &&
                !fieldView.disabled &&
                !fieldView.readOnly
            ) {
                notValid = fieldView.validate() || notValid;
            }

            if (notValid) {
                if (fieldView.$el) {
                    let rect = fieldView.$el.get(0).getBoundingClientRect();

                    if (
                        rect.top === 0 &&
                        rect.bottom === 0 &&
                        rect.left === 0 &&
                        fieldView.$el.closest('.panel.hidden').length
                    ) {
                        setTimeout(() => {
                            let msg = this.translate('Not valid') + ': ' +
                                (
                                    fieldView.lastValidationMessage ||
                                    this.translate(field, 'fields', this.entityType)
                                );

                            Espo.Ui.error(msg, true);
                        }, 10);
                    }
                }

                return true;
            }

            if (
                this.dynamicLogic &&
                this.dynamicLogicDefs &&
                this.dynamicLogicDefs.fields &&
                this.dynamicLogicDefs.fields[field] &&
                this.dynamicLogicDefs.fields[field].invalid &&
                this.dynamicLogicDefs.fields[field].invalid.conditionGroup
            ) {
                let invalidConditionGroup = this.dynamicLogicDefs.fields[field].invalid.conditionGroup;

                let fieldInvalid = this.dynamicLogic.checkConditionGroup(invalidConditionGroup);

                notValid = fieldInvalid || notValid;

                if (fieldInvalid) {
                    let msg =
                        this.translate('fieldInvalid', 'messages')
                            .replace('{field}', this.translate(field, 'fields', this.entityType));

                    fieldView.showValidationMessage(msg);

                    fieldView.trigger('invalid');
                }
            }

            return notValid;
        },

        /**
         * Processed after save.
         */
        afterSave: function () {
            if (this.isNew) {
                this.notify('Created', 'success');
            }
            else {
                this.notify('Saved', 'success');
            }

            this.setIsNotChanged();
        },

        /**
         * Processed before before-save.
         */
        beforeBeforeSave: function () {},

        /**
         * Processed before save.
         */
        beforeSave: function () {
            this.notify('Saving...');
        },

        /**
         * Processed after save error.
         */
        afterSaveError: function () {},

        /**
         * Processed after save a not modified record.
         */
        afterNotModified: function () {
            var msg = this.translate('notModified', 'messages');

            Espo.Ui.warning(msg);

            this.setIsNotChanged();
        },

        /**
         * Processed after save not valid.
         */
        afterNotValid: function () {
            this.notify('Not valid', 'error');
        },

        /**
         * Save options.
         *
         * @typedef {Object} module:views/record/base~saveOptions
         *
         * @property {Object.<string,string>} [headers] HTTP headers.
         * @property {boolean} [skipNotModifiedWarning] Don't show a not-modified warning.
         * @property {function():void} [afterValidate] A callback called after validate.
         * @property {boolean} [bypassClose] Bypass closing. Only for inline-edit.
         */

        /**
         * Save.
         *
         * @param {module:views/record/base~saveOptions} [options] Options.
         * @return {Promise<never,string>}
         */
        save: function (options) {
            options = options || {};

            var headers = options.headers || {};

            var model = this.model;

            this.lastSaveCancelReason = null;

            this.beforeBeforeSave();

            let fetchedAttributes = this.fetch();
            var initialAttributes = this.attributes;
            var beforeSaveAttributes = this.model.getClonedAttributes();

            let attributes = _.extend(
                Espo.Utils.cloneDeep(beforeSaveAttributes),
                fetchedAttributes
            );

            let setAttributes = {};

            if (model.isNew()) {
                setAttributes = attributes;
            }

            if (!model.isNew()) {
                for (let attr in attributes) {
                    if (_.isEqual(initialAttributes[attr], attributes[attr])) {
                        continue;
                    }

                    setAttributes[attr] = attributes[attr];
                }

                let forcePatchAttributeDependencyMap = this.forcePatchAttributeDependencyMap || {};

                for (let attr in forcePatchAttributeDependencyMap) {
                    if (attr in setAttributes) {
                        continue;
                    }

                    if (!(attr in fetchedAttributes)) {
                        continue;
                    }

                    let depAttributeList = forcePatchAttributeDependencyMap[attr];

                    let treatAsChanged = !! depAttributeList.find(attr => attr in setAttributes);

                    if (treatAsChanged) {
                        setAttributes[attr] = attributes[attr];
                    }
                }
            }

            if (Object.keys(setAttributes).length === 0) {
                if (!options.skipNotModifiedWarning) {
                    this.afterNotModified();
                }

                this.lastSaveCancelReason = 'notModified';

                this.trigger('cancel:save', {reason: 'notModified'});

                return Promise.reject('notModified');
            }

            model.set(setAttributes, {silent: true});

            if (this.validate()) {
                model.attributes = beforeSaveAttributes;

                this.afterNotValid();

                this.lastSaveCancelReason = 'invalid';

                this.trigger('cancel:save', {reason: 'invalid'});

                return Promise.reject('invalid');
            }

            if (options.afterValidate) {
                options.afterValidate();
            }

            var optimisticConcurrencyControl = this.getMetadata()
                .get(['entityDefs', this.entityType, 'optimisticConcurrencyControl']);

            if (optimisticConcurrencyControl && this.model.get('versionNumber') !== null) {
                headers['X-Version-Number'] = this.model.get('versionNumber');
            }

            if (this.model.isNew() && this.options.duplicateSourceId) {
                headers['X-Duplicate-Source-Id'] = this.options.duplicateSourceId;
            }

            this.beforeSave();

            this.trigger('before:save');
            model.trigger('before:save');

            let onError = (xhr, reject) => {
                this.handleSaveError(xhr, options);
                this.afterSaveError();

                this.setModelAttributes(beforeSaveAttributes);

                this.lastSaveCancelReason = 'error';

                this.trigger('error:save');
                this.trigger('cancel:save', {reason: 'error'});

                reject('error');
            };

            return new Promise((resolve, reject) => {
                model
                    .save(
                        setAttributes,
                        {
                            patch: !model.isNew(),
                            headers: headers,
                            // Catch use a promise-catch, as it's called
                            // after the default ajaxError callback.
                            error: (m, xhr) => onError(xhr, reject),
                        },
                    )
                    .then(() => {
                        this.trigger('save', initialAttributes);

                        this.afterSave();

                        if (this.isNew) {
                            this.isNew = false;
                        }

                        this.trigger('after:save');
                        model.trigger('after:save');

                        resolve();
                    });
            });
        },

        /**
         * Handle a save error.
         *
         * @param {JQueryXHR} xhr XHR.
         * @param {module:views/record/base~saveOptions} [options] Options.
         */
        handleSaveError: function (xhr, options) {
            var response = null;

            if (~[409, 500].indexOf(xhr.status)) {
                var statusReason = xhr.getResponseHeader('X-Status-Reason');

                if (statusReason) {
                    try {
                        response = JSON.parse(statusReason);
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
                    let handler = new Handler(this);

                    handler.process(response.data, options);
                });

                xhr.errorIsHandled = true;

                return;
            }

            var methodName = 'errorHandler' + Espo.Utils.upperCaseFirst(reason);

            if (methodName in this) {
                xhr.errorIsHandled = true;

                this[methodName](response.data, options);
            }
        },

        /**
         * Fetch data from the form.
         *
         * @return {Object.<string,*>}
         */
        fetch: function () {
            let data = {};
            let fieldViews = this.getFieldViews();

            for (let i in fieldViews) {
                let view = fieldViews[i];

                if (!view.isEditMode()) {
                    continue;
                }

                if (!view.disabled && !view.readOnly && view.isFullyRendered()) {
                    _.extend(data, view.fetch());
                }
            }

            return data;
        },

        /**
         * Process fetch.
         *
         * @return {Object<string,*>|null}
         */
        processFetch: function () {
            var data = this.fetch();

            this.model.set(data);

            if (this.validate()) {
                return null;
            }

            return data;
        },

        /**
         * Populate defaults.
         */
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

        /**
         * @protected
         * @param duplicates
         */
        errorHandlerDuplicate: function (duplicates) {},

        /**
         * @private
         */
        _handleDependencyAttributes: function () {
            Object.keys(this.dependencyDefs || {}).forEach((attr) => {
                this._handleDependencyAttribute(attr);
            });
        },

        /**
         * @private
         */
        _handleDependencyAttribute: function (attr) {
            var data = this.dependencyDefs[attr];
            var value = this.model.get(attr);

            if (value in (data.map || {})) {
                (data.map[value] || []).forEach((item) => {
                    this._doDependencyAction(item);
                });

                return;
            }

            if ('default' in data) {
                (data.default || []).forEach((item) => {
                    this._doDependencyAction(item);
                });
            }
        },

        /**
         * @private
         */
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
                    panelList.forEach((item) => {
                        this.hidePanel(item);
                    });

                    fieldList.forEach((item) => {
                        this.hideField(item);
                    });

                    break;

                case 'show':
                    panelList.forEach((item) => {
                        this.showPanel(item);
                    });

                    fieldList.forEach((item) => {
                        this.showField(item);
                    });

                    break;

                case 'setRequired':
                    fieldList.forEach((field) => {
                        this.setFieldRequired(field);
                    });

                    break;

                case 'setNotRequired':
                    fieldList.forEach((field) => {
                        this.setFieldNotRequired(field);
                    });

                    break;

                case 'setReadOnly':
                    fieldList.forEach((field) => {
                        this.setFieldReadOnly(field);
                    });

                    break;

                case 'setNotReadOnly':
                    fieldList.forEach((field) => {
                        this.setFieldNotReadOnly(field);
                    });

                    break;
            }
        },

        /**
         * Create a field view.
         *
         * @protected
         * @param {string} name A field name.
         * @param {string|null} [view] A view name/path.
         * @param {Object<string,*>} [params] Field params.
         * @param {'detail'|'edit'} [mode='edit'] A mode.
         * @param {boolean} [readOnly] Read-only.
         * @param {Object<string,*>} [options] View options.
         */
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

        /**
         * Get a currently focused field view.
         *
         * @return {?module:views/fields/base.Class}
         */
        getFocusedFieldView: function () {
            let $active = $(window.document.activeElement);

            if (!$active.length) {
                return null;
            }

            let $field = $active.closest('.field');

            if (!$field.length) {
                return null;
            }

            let name = $field.attr('data-name');

            if (!name) {
                return null;
            }

            return this.getFieldView(name);
        },

        /**
         * Process exit.
         *
         * @param {string} [after] An exit parameter.
         */
        exit: function (after) {},
    });
});
