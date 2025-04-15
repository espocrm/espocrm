/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

/** @module views/record/base */

import View from 'view';
import ViewRecordHelper from 'view-record-helper';
import DynamicLogic from 'dynamic-logic';
import _ from 'underscore';
import $ from 'jquery';
import DefaultsPopulator from 'helpers/model/defaults-populator';

/**
 * A base record view. To be extended.
 */
class BaseRecordView extends View {

    /**
     * A type.
     */
    type = 'edit'

    /**
     * An entity type.
     *
     * @type {string|null}
     */
    entityType = null

    /**
     * A scope.
     *
     * @type {string|null}
     */
    scope = null

    /**
     * Is new. Is set automatically.
     */
    isNew = false

    /**
     * @deprecated
     * @protected
     */
    dependencyDefs = {}

    /**
     * Dynamic logic.
     *
     * @protected
     * @type {Object}
     */
    dynamicLogicDefs = {}

    /**
     * A field list.
     *
     * @protected
     */
    fieldList = null

    /**
     * A mode.
     *
     * @type {'detail'|'edit'|null}
     */
    mode = null

    /**
     * A last save cancel reason.
     *
     * @protected
     * @type {string|null}
     */
    lastSaveCancelReason = null

    /**
     * Pre-save attribute values.
     *
     * @type {Record.<string, *>|undefined}
     */
    attributes

    /**
     * A record-helper.
     *
     * @type {module:view-record-helper}
     */
    recordHelper = null

    /** @const */
    MODE_DETAIL = 'detail'
    /** @const */
    MODE_EDIT = 'edit'

    /** @const */
    TYPE_DETAIL = 'detail'
    // noinspection JSUnusedGlobalSymbols
    /** @const  */
    TYPE_EDIT = 'edit'

    /**
     * Hide a field.
     *
     * @param {string} name A field name.
     * @param {boolean } [locked] To lock. Won't be able to un-hide.
     */
    hideField(name, locked) {
        this.recordHelper.setFieldStateParam(name, 'hidden', true);

        if (locked) {
            this.recordHelper.setFieldStateParam(name, 'hiddenLocked', true);
        }

        const processHtml = () => {
            const fieldView = this.getFieldView(name);

            if (fieldView) {
                const $field = fieldView.$el;
                const $cell = $field.closest('.cell[data-name="' + name + '"]');
                const $label = $cell.find('label.control-label[data-name="' + name + '"]');

                $field.addClass('hidden');
                $label.addClass('hidden');
                $cell.addClass('hidden-cell');
            } else {
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

        const view = this.getFieldView(name);

        if (view) {
            view.setDisabled(locked);
        }
    }

    /**
     * Show a field.
     *
     * @param {string} name A field name.
     */
    showField(name) {
        if (this.recordHelper.getFieldStateParam(name, 'hiddenLocked')) {
            return;
        }

        this.recordHelper.setFieldStateParam(name, 'hidden', false);

        const processHtml = () => {
            const fieldView = this.getFieldView(name);

            if (fieldView) {
                const $field = fieldView.$el;
                const $cell = $field.closest('.cell[data-name="' + name + '"]');
                const $label = $cell.find('label.control-label[data-name="' + name + '"]');

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

        const view = this.getFieldView(name);

        if (view) {
            if (!view.disabledLocked) {
                view.setNotDisabled();
            }
        }
    }

    /**
     * Set a field as read-only.
     *
     * @param {string} name A field name.
     * @param {boolean } [locked] To lock. Won't be able to un-set.
     */
    setFieldReadOnly(name, locked) {
        const previousValue = this.recordHelper.getFieldStateParam(name, 'readOnly');

        this.recordHelper.setFieldStateParam(name, 'readOnly', true);

        if (locked) {
            this.recordHelper.setFieldStateParam(name, 'readOnlyLocked', true);
        }

        const view = this.getFieldView(name);

        if (view) {
            view.setReadOnly(locked)
                .catch(() => {});
        }

        if (!previousValue) {
            this.trigger('set-field-read-only', name);
        }

        /**
         * @todo
         *   Move to fields/base. Listen to recordHelper 'field-change' (if recordHelper is available).
         *   Same for set state methods.
         *   Issue is that sometimes state is changed in between view initialization (for bottom views with fields).
         */

        if (!view && !this.isReady) {
            this.once('ready', () => {
                const view = this.getFieldView(name);

                if (
                    view &&
                    !view.readOnly &&
                    this.recordHelper.getFieldStateParam(name, 'readOnly')
                ) {
                    view.setReadOnly(locked);
                }
            })
        }
    }

    /**
     * Set a field as not read-only.
     *
     * @param {string} name A field name.
     */
    setFieldNotReadOnly(name) {
        const previousValue = this.recordHelper.getFieldStateParam(name, 'readOnly');

        this.recordHelper.setFieldStateParam(name, 'readOnly', false);

        if (this.readOnly) {
            return;
        }

        const view = this.getFieldView(name);

        if (view && view.readOnly) {
            view.setNotReadOnly();

            if (this.mode === this.MODE_EDIT) {
                if (!view.readOnlyLocked && view.isDetailMode()) {
                    view.setEditMode()
                        .then(() => view.reRender());
                }
            }
        }

        if (previousValue) {
            this.trigger('set-field-not-read-only', name);
        }

        if (!view && !this.isReady) {
            this.once('ready', () => {
                const view = this.getFieldView(name);

                if (
                    view &&
                    view.readOnly &&
                    !this.recordHelper.getFieldStateParam(name, 'readOnly')
                ) {
                    view.setNotReadOnly();
                }
            })
        }
    }

    /**
     * Set a field as required.
     *
     * @param {string} name A field name.
     */
    setFieldRequired(name) {
        const previousValue = this.recordHelper.getFieldStateParam(name, 'required');

        this.recordHelper.setFieldStateParam(name, 'required', true);

        const view = this.getFieldView(name);

        if (view) {
            view.setRequired();
        }

        if (!previousValue) {
            this.trigger('set-field-required', name);
        }
    }

    /**
     * Set a field as not required.
     *
     * @param {string} name A field name.
     */
    setFieldNotRequired(name) {
        const previousValue = this.recordHelper.getFieldStateParam(name, 'required');

        this.recordHelper.setFieldStateParam(name, 'required', false);

        const view = this.getFieldView(name);

        if (view) {
            view.setNotRequired();
        }

        if (previousValue) {
            this.trigger('set-field-not-required', name);
        }
    }

    /**
     * Set an option list for a field.
     *
     * @param {string} name A field name.
     * @param {string[]} list Options.
     */
    setFieldOptionList(name, list) {
        const had = this.recordHelper.hasFieldOptionList(name);
        const previousList = this.recordHelper.getFieldOptionList(name);

        this.recordHelper.setFieldOptionList(name, list);

        const view = this.getFieldView(name);

        if (view) {
            if ('setOptionList' in view) {
                view.setOptionList(list);
            }
        }

        if (!had || !_(previousList).isEqual(list)) {
            this.trigger('set-field-option-list', name, list);
        }
    }

    /**
     * Reset field options (revert to default).
     *
     * @param {string} name A field name.
     */
    resetFieldOptionList(name) {
        const had = this.recordHelper.hasFieldOptionList(name);

        this.recordHelper.clearFieldOptionList(name);

        const view = this.getFieldView(name);

        if (view) {
            if ('resetOptionList' in view) {
                view.resetOptionList();
            }
        }

        if (had) {
            this.trigger('reset-field-option-list', name);
        }
    }

    /**
     * Show a panel.
     *
     * @param {string} name A panel name.
     * @param [softLockedType] Omitted.
     */
    showPanel(name, softLockedType) {
        this.recordHelper.setPanelStateParam(name, 'hidden', false);

        if (this.isRendered()) {
            this.$el.find('.panel[data-name="'+name+'"]').removeClass('hidden');
        }
    }

    /**
     * Hide a panel.
     *
     * @param {string} name A panel name.
     * @param {boolean} [locked=false] Won't be able to un-hide.
     * @param {module:views/record/detail~panelSoftLockedType} [softLockedType='default']
     */
    hidePanel(name, locked, softLockedType) {
        this.recordHelper.setPanelStateParam(name, 'hidden', true);

        if (this.isRendered()) {
            this.$el.find('.panel[data-name="'+name+'"]').addClass('hidden');
        }
    }

    /**
     * Style a panel. Style is set in the `data-style` DOM attribute.
     *
     * @param {string} name A panel name.
     */
    stylePanel(name) {
        this.recordHelper.setPanelStateParam(name, 'styled', true);

        const process = () => {
            const $panel = this.$el.find('.panel[data-name="' + name + '"]');
            const $btn = $panel.find('> .panel-heading .btn');

            const style = $panel.attr('data-style');

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
    }

    /**
     * Un-style a panel.
     *
     * @param {string} name A panel name.
     */
    unstylePanel(name) {
        this.recordHelper.setPanelStateParam(name, 'styled', false);

        const process = () => {
            const $panel = this.$el.find('.panel[data-name="' + name + '"]');
            const $btn = $panel.find('> .panel-heading .btn');

            const style = $panel.attr('data-style');

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
    }

    /**
     * Set/unset a confirmation upon leaving the form.
     *
     * @param {boolean} value True sets a required confirmation.
     */
    setConfirmLeaveOut(value) {
        if (!this.getRouter()) {
            return;
        }

        if (value) {
            this.getRouter().addLeaveOutObject(this);
        } else {
            this.getRouter().removeLeaveOutObject(this);
        }
    }

    /**
     * Get field views.
     *
     * @param {boolean} [withHidden] With hidden.
     * @return {Object.<string, module:views/fields/base>}
     */
    getFieldViews(withHidden) {
        const fields = {};

        this.fieldList.forEach(item => {
            const view = this.getFieldView(item);

            if (view) {
                fields[item] = view;
            }
        });

        return fields;
    }

    /**
     * Get a field view.
     *
     * @param {string} name A field name.
     * @return {module:views/fields/base|null}
     */
    getFieldView(name) {
        /** @type {module:views/fields/base|null} */
        let view =  this.getView(name + 'Field') || null;

        // @todo Remove.
        if (!view) {
            view = this.getView(name) || null;
        }

        return view;
    }

    /**
     * @deprecated Use `getFieldView`.
     * @return {module:views/fields/base|null}
     */
    getField(name) {
        return this.getFieldView(name);
    }

    /**
     * Get a field list.
     *
     * @return {string[]}
     */
    getFieldList() {
        return Object.keys(this.getFieldViews());
    }

    /**
     * Get a field view list.
     *
     * @return {module:views/fields/base[]}
     */
    getFieldViewList() {
        return this.getFieldList()
            .map(field => this.getFieldView(field))
            .filter(view => view !== null);
    }

    /**
     * @inheritDoc
     */
    data() {
        return {
            scope: this.scope,
            entityType: this.entityType,
            hiddenPanels: this.recordHelper.getHiddenPanels(),
            hiddenFields: this.recordHelper.getHiddenFields(),
        };
    }

    /**
     * @todo Remove.
     * @private
     */
    handleDataBeforeRender(data) {
        this.getFieldList().forEach((field) => {
            const viewKey = field + 'Field';

            data[field] = data[viewKey];
        });
    }

    /**
     * @inheritDoc
     * Warning. Is not called by record/detail.
     */
    setup() {
        if (typeof this.model === 'undefined') {
            throw new Error('Model has not been injected into record view.');
        }

        /** @type {module:view-record-helper} */
        this.recordHelper = this.options.recordHelper || new ViewRecordHelper();

        this.dynamicLogicDefs = this.options.dynamicLogicDefs || this.dynamicLogicDefs;

        this.on('remove', () => {
            if (this.isChanged) {
                this.resetModelChanges();
            }

            this.setIsNotChanged();
        });

        this.entityType = this.model.entityType || this.model.name || 'Common';
        this.scope = this.options.scope || this.entityType;

        this.fieldList = this.options.fieldList || this.fieldList || [];

        this.numId = Math.floor((Math.random() * 10000) + 1);

        this.id = Espo.Utils.toDom(this.entityType) + '-' +
            Espo.Utils.toDom(this.type) + '-' + this.numId;

        if (this.model.isNew()) {
            this.isNew = true;
        }

        this.setupBeforeFinal();
    }

    /**
     * Set up before final.
     *
     * @protected
     */
    setupBeforeFinal() {
        this.attributes = this.model.getClonedAttributes();

        this.listenTo(this.model, 'change', (m, o) => {
            if (o.sync) {
                for (const attribute in m.attributes) {
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

        this.initDependency();
        this.initDynamicLogic();
    }

    /**
     * Set an initial attribute value.
     *
     * @protected
     * @param {string} attribute An attribute name.
     * @param {*} value
     */
    setInitialAttributeValue(attribute, value) {
        this.attributes[attribute] = value;
    }

    // noinspection JSUnusedGlobalSymbols
    /**
     * Check whether a current attribute value differs from initial.
     *
     * @param {string} name An attribute name.
     * @return {boolean}
     */
    checkAttributeIsChanged(name) {
        return !_.isEqual(this.attributes[name], this.model.get(name));
    }

    /**
     * Reset model changes.
     */
    resetModelChanges() {
        if (this.updatedAttributes) {
            this.attributes = this.updatedAttributes;

            this.updatedAttributes = null;
        }

        const attributes = this.model.attributes;

        for (const attr in attributes) {
            if (!(attr in this.attributes)) {
                this.model.unset(attr);
            }
        }

        this.model.set(this.attributes, {skipReRenderInEditMode: true});
    }

    /**
     * Init dynamic logic.
     *
     * @protected
     */
    initDynamicLogic() {
        this.dynamicLogicDefs = Espo.Utils.clone(this.dynamicLogicDefs || {});
        this.dynamicLogicDefs.fields = Espo.Utils.clone(this.dynamicLogicDefs.fields);
        this.dynamicLogicDefs.panels = Espo.Utils.clone(this.dynamicLogicDefs.panels);

        this.dynamicLogic = new DynamicLogic(this.dynamicLogicDefs, this);

        this.listenTo(this.model, 'sync', (m, a, /** Record */o) => {
            if (o && o.action !== 'save' && o.action !== 'fetch') {
                return;
            }

            // Pre-save attributes not yet prepared.
            setTimeout(() => this.processDynamicLogic(), 0);
        });

        this.listenTo(this.model, 'change', (m, /** Record */o) => {
            if (o.action === 'save' || o.action === 'fetch') {
                // To be processed by the 'sync' handler.
                return;
            }

            this.processDynamicLogic();
        });

        this.processDynamicLogic();
    }

    /**
     * Process dynamic logic.
     *
     * @protected
     */
    processDynamicLogic() {
        this.dynamicLogic.process();
    }

    /**
     * @protected
     * @internal
     */
    initDependency() {
        // noinspection JSDeprecatedSymbols
        Object.keys(this.dependencyDefs || {}).forEach((attr) => {
            this.listenTo(this.model, 'change:' + attr, () => {
                this._handleDependencyAttribute(attr);
            });
        });

        this._handleDependencyAttributes();
    }

    /**
     * Set up a field level security.
     *
     * @protected
     */
    setupFieldLevelSecurity() {
        const forbiddenFieldList = this.getAcl().getScopeForbiddenFieldList(this.entityType, 'read');

        forbiddenFieldList.forEach((field) => {
            this.hideField(field, true);
        });

        const readOnlyFieldList = this.getAcl().getScopeForbiddenFieldList(this.entityType, 'edit');

        readOnlyFieldList.forEach((field) => {
            this.setFieldReadOnly(field, true);
        });
    }

    /**
     * Set is changed.
     *
     * @protected
     */
    setIsChanged() {
        this.isChanged = true;
    }

    /**
     * Set is not changed.
     *
     * @protected
     */
    setIsNotChanged() {
        this.isChanged = false;
    }

    /**
     * Validate.
     *
     * @return {boolean} True if not valid.
     */
    validate() {
        const invalidFieldList = [];

        this.getFieldList().forEach(field => {
            const fieldIsInvalid = this.validateField(field);

            if (fieldIsInvalid) {
                invalidFieldList.push(field)
            }
        });

        if (!!invalidFieldList.length) {
            this.onInvalid(invalidFieldList);
        }

        return !!invalidFieldList.length;
    }

    /**
     * @protected
     * @param {string[]} invalidFieldList Invalid fields.
     */
    onInvalid(invalidFieldList) {}

    /**
     * Validate a specific field.
     *
     * @param {string} field A field name.
     * @return {boolean} True if not valid.
     */
    validateField(field) {
        const msg =
            this.translate('fieldInvalid', 'messages')
                .replace('{field}', this.translate(field, 'fields', this.entityType));
        const fieldView = this.getFieldView(field);

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
                const rect = fieldView.$el.get(0).getBoundingClientRect();

                if (
                    rect.top === 0 &&
                    rect.bottom === 0 &&
                    rect.left === 0 &&
                    fieldView.$el.closest('.panel.hidden').length
                ) {
                    setTimeout(() => {
                        const msg = this.translate('Not valid') + ': ' +
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
            const invalidConditionGroup = this.dynamicLogicDefs.fields[field].invalid.conditionGroup;

            const fieldInvalid = this.dynamicLogic.checkConditionGroup(invalidConditionGroup);

            notValid = fieldInvalid || notValid;

            if (fieldInvalid) {

                fieldView.showValidationMessage(msg);

                fieldView.trigger('invalid');
            }
        }

        return notValid;
    }

    /**
     * Processed after save.
     *
     * @protected
     */
    afterSave() {
        if (this.isNew) {
            Espo.Ui.success(this.translate('Created'));
        }
        else {
            Espo.Ui.success(this.translate('Saved'));
        }

        this.setIsNotChanged();
    }

    /**
     * Processed before before-save.
     *
     * @protected
     */
    beforeBeforeSave() {}

    /**
     * Processed before save.
     *
     * @protected
     */
    beforeSave() {
        Espo.Ui.notify(this.translate('saving', 'messages'));
    }

    /**
     * Processed after save error.
     *
     * @protected
     */
    afterSaveError() {}

    /**
     * Processed after save a not modified record.
     *
     * @protected
     */
    afterNotModified() {
        Espo.Ui.warning(this.translate('notModified', 'messages'));

        this.setIsNotChanged();
    }

    /**
     * Processed after save not valid.
     *
     * @protected
     */
    afterNotValid() {
        Espo.Ui.error(this.translate('Not valid'));
    }

    /**
     * Get changed attribute values. For new record, returns all attributes.
     *
     * @protected
     * @param {string[]} [attributeList]
     * @return {Record}
     */
    getChangedAttributes(attributeList = null) {
        const attributes = this.model.getClonedAttributes();

        if (this.model.isNew()) {
            return attributes;
        }

        const setAttributes = {};

        for (const attr in attributes) {
            if (Espo.Utils.areEqual(this.attributes[attr], attributes[attr])) {
                continue;
            }

            setAttributes[attr] = attributes[attr];
        }

        /** @type {Record.<string, string[]>} */
        const map = this.forcePatchAttributeDependencyMap || {};

        for (const attr in map) {
            if (attr in setAttributes) {
                continue;
            }

            if (attributeList && !attributeList.includes(attr)) {
                continue;
            }

            const depAttributes = map[attr];

            const treatAsChanged = !!depAttributes.find(attr => attr in setAttributes);

            if (treatAsChanged) {
                setAttributes[attr] = attributes[attr];
            }
        }

        return setAttributes;
    }

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
     * @return {Promise}
     */
    save(options) {
        options = options || {};

        const headers = options.headers || {};

        const model = this.model;

        this.lastSaveCancelReason = null;

        this.beforeBeforeSave();

        // A model is supposed to always contain actual values.
        // Fetch may not be needed, but some field views may not have data sync implemented.
        // We resort to fetching the entire form.

        const fetchedAttributes = this.fetch();

        this.model.set(fetchedAttributes, {silent: true});

        const setAttributes = this.getChangedAttributes(Object.keys(fetchedAttributes));

        if (Object.keys(setAttributes).length === 0) {
            if (!options.skipNotModifiedWarning) {
                this.afterNotModified();
            }

            this.lastSaveCancelReason = 'notModified';

            this.trigger('cancel:save', {reason: 'notModified'});

            return Promise.reject('notModified');
        }

        if (this.validate()) {
            this.afterNotValid();

            this.lastSaveCancelReason = 'invalid';

            this.trigger('cancel:save', {reason: 'invalid'});

            return Promise.reject('invalid');
        }

        if (options.afterValidate) {
            options.afterValidate();
        }

        const optimisticConcurrencyControl = this.getMetadata()
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

        const initialAttributes = this.attributes;

        return new Promise((resolve, reject) => {
            const ajaxPromise = model
                .save(setAttributes, {
                    patch: !model.isNew(),
                    headers: headers,
                });

            ajaxPromise
                .then(() => {
                    this.trigger('save', initialAttributes, Object.keys(setAttributes));

                    this.afterSave();

                    if (this.isNew) {
                        this.isNew = false;
                    }

                    this.trigger('after:save');
                    model.trigger('after:save');

                    if (ajaxPromise.xhr.getResponseHeader('X-Record-Link-Updated')) {
                        model.trigger('update-all');
                    }

                    resolve();
                })
                .catch(xhr => {
                    this.handleSaveError(xhr, options, resolve, reject)
                        .then(skipReject => {
                            if (skipReject) {
                                return;
                            }

                            reject('error');
                        });

                    this.afterSaveError();

                    this.lastSaveCancelReason = 'error';

                    this.trigger('error:save');
                    this.trigger('cancel:save', {reason: 'error'});
                });
        });
    }

    /**
     * Handle a save error.
     *
     * @param {module:ajax.Xhr} xhr XHR.
     * @param {module:views/record/base~saveOptions} [options] Options.
     * @param {function} saveResolve Resolve the save promise.
     * @param {function} saveReject Reject the same promise.
     * @return {Promise<boolean>}
     *
     * @protected
     */
    handleSaveError(xhr, options, saveResolve, saveReject) {
        let handlerData = null;

        if (~[409, 500].indexOf(xhr.status)) {
            const statusReason = xhr.getResponseHeader('X-Status-Reason');

            if (!statusReason) {
                return Promise.resolve(false);
            }

            try {
                handlerData = JSON.parse(statusReason);
            }
            catch (e) {}

            if (!handlerData) {
                handlerData = {
                    reason: statusReason.toString(),
                };

                if (xhr.responseText) {
                    let data;

                    try {
                        data = JSON.parse(xhr.responseText);
                    }
                    catch (e) {
                        console.error('Could not parse error response body.');

                        return Promise.resolve(false);
                    }

                    handlerData.data = data;
                }
            }
        }

        if (!handlerData || !handlerData.reason) {
            return Promise.resolve(false);
        }

        const reason = handlerData.reason;

        const handlerName =
            this.getMetadata()
                .get(['clientDefs', this.scope, 'saveErrorHandlers', reason]) ||
            this.getMetadata()
                .get(['clientDefs', 'Global', 'saveErrorHandlers', reason]);

        return new Promise(resolve => {
            if (handlerName) {
                Espo.loader.require(handlerName, Handler => {
                    const handler = new Handler(this);

                    handler.process(handlerData.data, options);

                    resolve(false);
                });

                xhr.errorIsHandled = true;

                return;
            }

            const methodName = 'errorHandler' + Espo.Utils.upperCaseFirst(reason);

            if (methodName in this) {
                xhr.errorIsHandled = true;

                const skipReject = this[methodName](handlerData.data, options, saveResolve, saveReject);

                resolve(skipReject || false);

                return;
            }

            resolve(false);
        });
    }

    /**
     * Fetch data from the form.
     *
     * @return {Object.<string, *>}
     */
    fetch() {
        let data = {};
        const fieldViews = this.getFieldViews();

        for (const i in fieldViews) {
            const view = fieldViews[i];

            if (!view.isEditMode()) {
                continue;
            }

            if (!view.disabled && !view.readOnly && view.isFullyRendered()) {
                data = {...data, ...view.fetch()};
            }
        }

        return data;
    }

    /**
     * Process fetch.
     *
     * @return {Object<string,*>|null}
     */
    processFetch() {
        const data = this.fetch();

        this.model.set(data);

        if (this.validate()) {
            return null;
        }

        return data;
    }

    /**
     * Populate defaults.
     *
     * @return {Promise|undefined}
     */
    populateDefaults() {
        const populator = new DefaultsPopulator();

        return populator.populate(this.model);
    }

    // noinspection JSUnusedGlobalSymbols
    /**
     * @protected
     * @param duplicates
     */
    errorHandlerDuplicate(duplicates) {}

    /**
     * @private
     */
    _handleDependencyAttributes() {
        // noinspection JSDeprecatedSymbols
        Object.keys(this.dependencyDefs || {}).forEach(attr => {
            this._handleDependencyAttribute(attr);
        });
    }

    /**
     * @private
     */
    _handleDependencyAttribute(attr) {
        // noinspection JSDeprecatedSymbols
        const data = this.dependencyDefs[attr];
        const value = this.model.get(attr);

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
    }

    /**
     * @private
     */
    _doDependencyAction(data) {
        const action = data.action;

        const methodName = 'dependencyAction' + Espo.Utils.upperCaseFirst(action);

        if (methodName in this && typeof this.methodName === 'function') {
            this.methodName(data);

            return;
        }

        const fieldList = data.fieldList || data.fields || [];
        const panelList = data.panelList || data.panels || [];

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
    }

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
    createField(name, view, params, mode, readOnly, options) {
        const o = {
            model: this.model,
            mode: mode || 'edit',
            selector: '.field[data-name="' + name + '"]',
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
            const type = this.model.getFieldType(name) || 'base';
            view = this.getFieldManager().getViewName(type);
        }

        if (options) {
            for (const param in options) {
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

        const viewKey = name + 'Field';

        this.createView(viewKey, view, o);

        if (!~this.fieldList.indexOf(name)) {
            this.fieldList.push(name);
        }
    }

    /**
     * Get a currently focused field view.
     *
     * @return {module:views/fields/base|null}
     */
    getFocusedFieldView() {
        const $active = $(window.document.activeElement);

        if (!$active.length) {
            return null;
        }

        const $field = $active.closest('.field');

        if (!$field.length) {
            return null;
        }

        const name = $field.attr('data-name');

        if (!name) {
            return null;
        }

        return this.getFieldView(name);
    }

    /**
     * Process exit.
     *
     * @param {string} [after] An exit parameter.
     */
    exit(after) {}
}

export default BaseRecordView;
