/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2026 EspoCRM, Inc.
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

import View from 'view';
import ViewRecordHelper from 'view-record-helper';
import DynamicLogic from 'dynamic-logic';
import _ from 'underscore';
import Ui from 'ui';
import Utils from 'utils';
import Model from 'model';
import BaseFieldView from 'views/fields/base';
import {Xhr} from 'util/ajax';

/**
 * Save options.
 */
export interface SaveOptions {
    /**
     * HTTP headers.
     */
    headers?: Record<string, string>;
    /**
     * Don't show a not-modified warning.
     */
    skipNotModifiedWarning?: boolean;
    /**
     * A callback called after validate.
     */
    afterValidate?: () => void;
    /**
     * Bypass closing. Only for inline-edit.
     */
    bypassClose?: boolean;
}

/**
 * A panel soft-locked type.
 */
type PanelSoftLockedType = 'default' | 'acl' | 'delimiter' | 'dynamicLogic';

export interface BaseRecordViewSchema {
    model: Model;
    options: Record<string, any> & BaseRecordViewOptions;
}

export interface BaseRecordViewOptions {
    model: Model;
    recordHelper?: ViewRecordHelper;
}

/**
 * A base record view. To be extended.
 *
 * @internal
 */
class BaseRecordView<S extends BaseRecordViewSchema = BaseRecordViewSchema> extends View<S> {

    /**
     * A type.
     */
    protected type: string = 'edit'

    /**
     * An entity type.
     */
    entityType: string | null = null

    /**
     * A scope.
     */
    scope: string | null = null

    /**
     * Is new. Is set automatically.
     */
    isNew: boolean = false

    /**
     * @deprecated
     * @todo Remove in v10.2.
     */
    protected dependencyDefs: Record<string, any> = {}

    /**
     * Dynamic logic.
     */
    protected dynamicLogicDefs: Record<string, any> = {}

    /**
     * A field list.
     */
    protected fieldList: string[] | null = null

    /**
     * A mode.
     */
    mode: 'detail' | 'edit' | null = null

    /**
     * A last save cancel reason.
     */
    protected lastSaveCancelReason: string | null = null

    /**
     * Pre-save attribute values.
     *
     * @todo Use null?
     */
    protected attributes: Record<string, any>

    /**
     * A record-helper.
     */
    protected recordHelper: ViewRecordHelper

    /**
     * Read-only. Can be overridden by an option parameter.
     */
    readOnly = false

    readonly MODE_DETAIL = 'detail'
    readonly MODE_EDIT = 'edit'

    readonly TYPE_DETAIL = 'detail'
    readonly TYPE_EDIT = 'edit'

    protected isChanged: boolean = false

    /**
     * @internal
     */
    protected numId: string

    /**
     * @internal
     */
    protected id: string

    /**
     * @internal
     */
    protected updatedAttributes: Record<string, any> | null = null

    protected dynamicLogic: DynamicLogic;

    /**
     * @internal
     */
    protected forcePatchAttributeDependencyMap: Record<string, string[]>

    options: S['options']

    constructor(options: S['options'] & {model: S['model']}) {
        super(options);
    }

    /**
     * Get pre-save attributes.
     *
     * @since 10.0.0
     */
    getPreSaveAttributes(): Record<string, unknown> {
        return Utils.cloneDeep(this.attributes ?? {});
    }

    /**
     * Hide a field.
     *
     * @param name A field name.
     * @param [locked] To lock. Won't be able to un-hide.
     */
    hideField(name: string, locked: boolean = false) {
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
        } else {
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
     * @param name A field name.
     */
    showField(name: string) {
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
        } else {
            this.once('after:render', () => {
                processHtml();
            });
        }

        const view = this.getFieldView(name);

        if (view && !view.disabledLocked) {
            view.setNotDisabled();
        }
    }

    /**
     * Set a field as read-only.
     *
     * @param name A field name.
     * @param [locked] To lock. Won't be able to un-set.
     */
    setFieldReadOnly(name: string, locked: boolean = false) {
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
     * @param name A field name.
     */
    setFieldNotReadOnly(name: string) {
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
     * @param name A field name.
     */
    setFieldRequired(name: string) {
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
     * @param name A field name.
     */
    setFieldNotRequired(name: string) {
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
     * @param name A field name.
     * @param list Options.
     */
    setFieldOptionList(name: string, list: string[]) {
        const had = this.recordHelper.hasFieldOptionList(name);
        const previousList = this.recordHelper.getFieldOptionList(name);

        this.recordHelper.setFieldOptionList(name, list);

        const view = this.getFieldView(name);

        if (view && hasSetOptionList(view)) {
            view.setOptionList(list);
        }

        if (!had || !_(previousList).isEqual(list)) {
            this.trigger('set-field-option-list', name, list);
        }
    }

    /**
     * Reset field options (revert to default).
     *
     * @param name A field name.
     */
    resetFieldOptionList(name: string) {
        const had = this.recordHelper.hasFieldOptionList(name);

        this.recordHelper.clearFieldOptionList(name);

        const view = this.getFieldView(name);

        if (view && hasResetOptionList(view)) {
            view.resetOptionList();
        }

        if (had) {
            this.trigger('reset-field-option-list', name);
        }
    }

    /**
     * Show a panel.
     *
     * @param name A panel name.
     * @param [softLockedType] Omitted.
     */
    showPanel(name: string, softLockedType?: PanelSoftLockedType) {
        // noinspection BadExpressionStatementJS
        softLockedType;

        this.recordHelper.setPanelStateParam(name, 'hidden', false);

        if (this.isRendered()) {
            this.$el.find('.panel[data-name="'+name+'"]').removeClass('hidden');
        }
    }

    /**
     * Hide a panel.
     *
     * @param name A panel name.
     * @param [locked=false] Won't be able to un-hide.
     * @param [softLockedType='default']
     */
    hidePanel(name: string, locked: boolean = false, softLockedType?: PanelSoftLockedType) {
        // noinspection BadExpressionStatementJS
        locked;
        // noinspection BadExpressionStatementJS
        softLockedType;

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
    stylePanel(name: string) {
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
     * @param name A panel name.
     */
    unstylePanel(name: string) {
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
     * @param value True sets a required confirmation.
     */
    setConfirmLeaveOut(value: boolean) {
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
     * @param [withHidden] With hidden.
     */
    getFieldViews(withHidden: boolean = false): Record<string, BaseFieldView> {
        // noinspection BadExpressionStatementJS
        withHidden;

        const fields: Record<string, BaseFieldView> = {};

        this.fieldList?.forEach(item => {
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
     * @param name A field name.
     */
    getFieldView(name: string): BaseFieldView<any, any> | null {
        let view = this.getView<BaseFieldView<any, any>>(`${name}Field`) || null;

        // @todo Remove.
        if (!view) {
            view = this.getView(name) || null;
        }

        return view;
    }

    /**
     * @deprecated Use `getFieldView`.
     */
    getField(name: string): BaseFieldView | null {
        return this.getFieldView(name);
    }

    /**
     * Get a field list.
     */
    getFieldList(): string[] {
        return Object.keys(this.getFieldViews());
    }

    /**
     * Get a field view list.
     */
    getFieldViewList(): BaseFieldView[] {
        return this.getFieldList()
            .map(field => this.getFieldView(field))
            .filter(view => view !== null);
    }

    protected data(): Record<string, any> {
        return {
            scope: this.scope,
            entityType: this.entityType,
            hiddenPanels: this.recordHelper.getHiddenPanels(),
            hiddenFields: this.recordHelper.getHiddenFields(),
        };
    }

    /**
     * @todo Remove.
     * @internal
     */
    protected handleDataBeforeRender(data: Record<string, any>) {
        this.getFieldList().forEach((field) => {
            const viewKey = field + 'Field';

            data[field] = data[viewKey];
        });
    }

    /**
     * Warning. Is not called by record/detail.
     */
    protected setup() {
        if (typeof this.model === 'undefined') {
            throw new Error('Model has not been injected into record view.');
        }

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

        this.numId = Math.floor((Math.random() * 10000) + 1).toString();

        this.id = Utils.toDom(this.entityType) + '-' + Utils.toDom(this.type) + '-' + this.numId;

        if (this.model.isNew()) {
            this.isNew = true;
        }

        this.setupBeforeFinal();
    }

    /**
     * Set up before final.
     */
    protected setupBeforeFinal() {
        this.attributes = this.model.getClonedAttributes();

        this.listenTo(this.model, 'change', (m, o) => {
            if (o.sync) {
                for (const attribute in m.attributes) {
                    if (!m.hasChanged(attribute)) {
                        continue;
                    }

                    if (this.attributes) {
                        this.attributes[attribute] = Utils.cloneDeep(m.get(attribute));
                    }
                }

                return;
            }

            if (this.mode === this.MODE_EDIT) {
                this.setIsChanged();
            }
        });

        if (this.options.attributes) {
            this.model.setMultiple(this.options.attributes);
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
     * @param attribute An attribute name.
     * @param value A value.
     */
    protected setInitialAttributeValue(attribute: string, value: unknown) {
        if (!this.attributes) {
            this.attributes = {};
        }

        this.attributes[attribute] = value;
    }

    // noinspection JSUnusedGlobalSymbols
    /**
     * Check whether a current attribute value differs from initial.
     *
     * @param {string} name An attribute name.
     * @return {boolean}
     */
    protected checkAttributeIsChanged(name: string): boolean {
        return !_.isEqual(this.attributes?.[name], this.model.get(name));
    }

    /**
     * Reset model changes.
     */
    protected resetModelChanges() {
        if (this.updatedAttributes) {
            this.attributes = this.updatedAttributes;

            this.updatedAttributes = null;
        }

        const attributes = this.model.attributes;

        const preSaveAttributes = this.attributes ?? {};

        for (const attr in attributes) {
            if (!(attr in preSaveAttributes)) {
                this.model.unset(attr);
            }
        }

        this.model.setMultiple(preSaveAttributes, {skipReRenderInEditMode: true});
    }

    /**
     * Init dynamic logic.
     */
    protected initDynamicLogic() {
        this.dynamicLogicDefs = Utils.clone(this.dynamicLogicDefs || {});
        this.dynamicLogicDefs.fields = Utils.clone(this.dynamicLogicDefs.fields);
        this.dynamicLogicDefs.panels = Utils.clone(this.dynamicLogicDefs.panels);

        this.dynamicLogic = new DynamicLogic(this.dynamicLogicDefs, this);

        this.listenTo(this.model, 'sync', (_m, _a, o: Record<string, any>) => {
            if (o && o.action !== 'save' && o.action !== 'fetch') {
                return;
            }

            // Pre-save attributes not yet prepared.
            setTimeout(() => this.processDynamicLogic(), 0);
        });

        this.listenTo(this.model, 'change', (_m, o: Record<string, any>) => {
            if (o.action === 'save' || o.action === 'fetch') {
                // To be processed by the 'sync' handler.
                return;
            }

            this.processDynamicLogic({action: o.action});
        });

        this.processDynamicLogic();
    }

    /**
     * Process dynamic logic.
     *
     * @param [options] Options.
     */
    protected processDynamicLogic(
        options: {
            action?: string | 'ui',
        } = {}
    ) {
        this.dynamicLogic.process(options);
    }

    /**
     * @internal
     */
    protected initDependency() {
        Object.keys((this as any).dependencyDefs || {}).forEach((attr) => {
            this.listenTo(this.model, 'change:' + attr, () => {
                this._handleDependencyAttribute(attr);
            });
        });

        this._handleDependencyAttributes();
    }

    /**
     * Set up a field level security.
     */
    protected setupFieldLevelSecurity() {
        if (!this.entityType) {
            return;
        }

        const forbiddenFieldList = this.getAcl().getScopeForbiddenFieldList(this.entityType, 'read');

        forbiddenFieldList.forEach(field => this.hideField(field, true));

        const readOnlyFieldList = this.getAcl().getScopeForbiddenFieldList(this.entityType, 'edit');

        readOnlyFieldList.forEach(field => this.setFieldReadOnly(field, true));
    }

    /**
     * Set is changed.
     */
    protected setIsChanged() {
        this.isChanged = true;
    }

    /**
     * Set is not changed.
     */
    protected setIsNotChanged() {
        this.isChanged = false;
    }

    /**
     * Validate.
     *
     * @return True if not valid.
     */
    validate(): boolean {
        const invalidFieldList: string[] = [];

        this.getFieldList().forEach(field => {
            const fieldIsInvalid = this.validateField(field);

            if (fieldIsInvalid) {
                invalidFieldList.push(field)
            }
        });

        if (invalidFieldList.length) {
            this.onInvalid(invalidFieldList);
        }

        return !!invalidFieldList.length;
    }

    /**
     * @param invalidFieldList Invalid fields.
     */
    protected onInvalid(invalidFieldList: string[]) {
        // noinspection BadExpressionStatementJS
        invalidFieldList;
    }

    /**
     * Validate a specific field.
     *
     * @param field A field name.
     * @return True if not valid.
     */
    validateField(field: string): boolean {
        const msg = this.translate('fieldInvalid', 'messages')
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

                        Ui.error(msg, true);
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
     */
    protected afterSave() {
        if (this.isNew) {
            Ui.success(this.translate('Created'));
        } else {
            Ui.success(this.translate('Saved'));
        }

        this.setIsNotChanged();
    }

    /**
     * Processed before before-save.
     */
    protected beforeBeforeSave() {}

    /**
     * Processed before save.
     */
    protected beforeSave() {
        Ui.notify(this.translate('saving', 'messages'));
    }

    /**
     * Processed after save error.
     */
    protected afterSaveError() {}

    /**
     * Processed after save a not modified record.
     */
    protected afterNotModified() {
        Ui.warning(this.translate('notModified', 'messages'));

        this.setIsNotChanged();
    }

    /**
     * Processed after save not valid.
     */
    protected afterNotValid() {
        Ui.error(this.translate('Not valid'));
    }

    /**
     * Get changed attribute values. For new record, returns all attributes.
     *
     * @param attributeList
     */
    protected getChangedAttributes(attributeList: string[] | null = null): Record<string, unknown> {
        const attributes = this.model.getClonedAttributes();

        if (this.model.isNew()) {
            return attributes;
        }

        const setAttributes = {} as Record<string, any>;

        for (const attr in attributes) {
            if (Utils.areEqual(this.attributes?.[attr], attributes[attr])) {
                continue;
            }

            setAttributes[attr] = attributes[attr];
        }

        const map = this.forcePatchAttributeDependencyMap ?? {};

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
     * Save.
     *
     * @param [options] Options.
     */
    save(options?: Record<string, any> & SaveOptions): Promise<void> {
        options = options || {};

        const headers = options.headers || {};

        const model = this.model;

        this.lastSaveCancelReason = null;

        this.beforeBeforeSave();

        // A model is supposed to always contain actual values.
        // Fetch may not be needed, but some field views may not have data sync implemented.
        // We resort to fetching the entire form.

        const fetchedAttributes = this.fetch();

        this.model.setMultiple(fetchedAttributes, {silent: true});

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

        const optimisticConcurrencyControl = this.entityType ?
            this.getMetadata().get(['entityDefs', this.entityType, 'optimisticConcurrencyControl']) : null;

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

                    if (ajaxPromise.xhr?.getResponseHeader('X-Record-Link-Updated')) {
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
     * @param xhr XHR.
     * @param [options] Options.
     * @param saveResolve Resolve the save promise.
     * @param saveReject Reject the same promise.
     */
    protected handleSaveError(
        xhr: Xhr,
        options?: SaveOptions,
        saveResolve?: () => void,
        saveReject?: () => void
    ): Promise<boolean> {

        let handlerData: any = null;

        if ([409, 500].includes(xhr.status)) {
            const statusReason = xhr.getResponseHeader('X-Status-Reason');

            if (!statusReason) {
                return Promise.resolve(false);
            }

            try {
                handlerData = JSON.parse(statusReason);
            } catch (e) {}

            if (!handlerData) {
                handlerData = {
                    reason: statusReason.toString(),
                };

                if (xhr.responseText) {
                    let data: any;

                    try {
                        data = JSON.parse(xhr.responseText);
                    } catch (e) {
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

            const methodName = 'errorHandler' + Utils.upperCaseFirst(reason);

            if (methodName in this) {
                xhr.errorIsHandled = true;

                // @ts-ignore
                const skipReject = this[methodName](handlerData.data, options, saveResolve, saveReject);

                resolve(skipReject || false);

                return;
            }

            resolve(false);
        });
    }

    /**
     * Fetch data from the form.
     */
    fetch(): Record<string, unknown> {
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
     * Process fetch. Returns null if not valid.
     */
    processFetch(): Record<string, unknown> | null {
        const data = this.fetch();

        this.model.setMultiple(data);

        if (this.validate()) {
            return null;
        }

        return data;
    }

    /**
     * @deprecated As of v9.3.0.
     * @todo Remove in v11.0.
     */
    protected populateDefaults(): Promise<void> | undefined {
        return undefined;
    }

    // noinspection JSUnusedGlobalSymbols
    /**
     * @internal
     */
    protected errorHandlerDuplicate(
        duplicates: any[],
        options: Record<string, any>,
        resolve: () => void,
        reject: (reason: 'error' | 'cancel') => void,
    ) {
        // noinspection BadExpressionStatementJS
        duplicates;
        // noinspection BadExpressionStatementJS
        options;
        // noinspection BadExpressionStatementJS
        resolve;
        // noinspection BadExpressionStatementJS
        reject;
    }

    private _handleDependencyAttributes() {
        // noinspection JSDeprecatedSymbols
        Object.keys((this as any).dependencyDefs || {}).forEach(attr => {
            this._handleDependencyAttribute(attr);
        });
    }

    protected _handleDependencyAttribute(attr: string) {
        // noinspection JSDeprecatedSymbols
        const data = (this as any).dependencyDefs[attr];
        const value = this.model.get(attr);

        if (value in (data.map || {})) {
            (data.map[value] || []).forEach((item: any) => {
                this._doDependencyAction(item);
            });

            return;
        }

        if ('default' in data) {
            (data.default || []).forEach((item: any) => {
                this._doDependencyAction(item);
            });
        }
    }

    /**
     * Legacy.
     */
    private _doDependencyAction(data: any) {
        const action = data.action;

        const methodName = 'dependencyAction' + Utils.upperCaseFirst(action);

        // @ts-ignore
        if (methodName in this && typeof this.methodName === 'function') {
            // @ts-ignore
            this.methodName(data);

            return;
        }

        const fieldList: string[] = data.fieldList || data.fields || [];
        const panelList: string[] = data.panelList || data.panels || [];

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
     * @param name A field name.
     * @param [view] A view name/path.
     * @param [params] Field params.
     * @param [mode='edit'] A mode.
     * @param [readOnly] Read-only.
     * @param [options] View options.
     *
     * @internal
     */
    protected createField(
        name: string,
        view : string | null = null,
        params: Record<string, any> | null = null,
        mode: 'detail' | 'edit' = 'edit',
        readOnly: boolean = false,
        options: Record<string, any> | null = null,
    ) {
        const o = {
            model: this.model,
            mode: mode || 'edit',
            selector: '.field[data-name="' + name + '"]',
            defs: {
                name: name,
                params: params || {},
            },
        } as Record<string, any>;

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

        if (this.fieldList && !this.fieldList.includes(name)) {
            this.fieldList.push(name);
        }
    }

    /**
     * Get a currently focused field view.
     */
    getFocusedFieldView(): BaseFieldView | null {
        const activeElement = window.document.activeElement;

        if (!activeElement) {
            return null;
        }

        const fieldElement = activeElement.closest<HTMLElement>('.field');

        if (!fieldElement) {
            return null;
        }

        const name = fieldElement.dataset.name;

        if (!name) {
            return null;
        }

        return this.getFieldView(name);
    }

    /**
     * Process exit.
     *
     * @param [after] An exit parameter.
     */
    protected exit(after?: string) {
        // noinspection BadExpressionStatementJS
        after;
    }

    /**
     * Is changed.
     *
     * @since 10.0.0
     */
    public hasChanged(): boolean {
        return this.isChanged
    }
}

export default BaseRecordView;

type HasSetOptionList = {
    setOptionList(list: string[]): void;
};

function hasSetOptionList(view: any): view is HasSetOptionList {
    return typeof view?.setOptionList === 'function';
}

type HasResetOptionList = {
    resetOptionList(): void;
};

function hasResetOptionList(view: any): view is HasResetOptionList {
    return typeof view?.resetOptionList === 'function';
}
