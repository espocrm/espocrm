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

/** @module views/fields/base */

import View from 'view';
import Select from 'ui/select';
import Model from 'model';
import Ui from 'ui';
import _ from 'underscore';
import JQuery from 'jquery'

const $ = JQuery;

/**
 * Options.
 */
export interface BaseOptions {
    /**
     * A field name.
     */
    name: string;
    /**
     * A model.
     */
    model?: import('model').default;
    /**
     * Disabled inline edit.
     */
    inlineEditDisabled?: boolean;
    /**
     * Is read-only.
     */
    readOnly?: boolean;
    /**
     * A label text (already translated).
     */
    labelText?: string;
    /**
     * A field mode.
     */
    mode?: 'detail' | 'edit' | 'list' | 'search';
    /**
     * A record helper.
     */
    recordHelper?: import('view-record-helper').default;
    disabledLocked?: boolean;
    disabled?: boolean;
    readOnlyLocked?: boolean;
    readOnlyDisabled?: boolean;
    tooltipText?: string;
    tooltip?: string;
    defs?: Record<string, any>;
    validateCallback?: () => boolean;
    dataObject?: Record<string, any>;
    searchParams?: Record<string, any>;
}

/**
 * Base parameters.
 *
 * @property inlineEditDisabled Disable inline edit.
 * @property readOnly Is read-only.
 */
export interface BaseParams {
    inlineEditDisabled?: boolean;
    readOnly?: boolean;
}

/**
 * @internal
 */
export interface BaseViewSchema {
    model: Model;
}

export type FieldValidator = () => boolean;

type Mode = 'list' | 'listLink' | 'detail' | 'edit' | 'search';

/**
 * A base field view. Can be in different modes. Each mode uses a separate template.
 *
 * @todo Document events.
 */
export default class BaseFieldView<
    S extends BaseViewSchema = BaseViewSchema,
    O extends BaseOptions = BaseOptions,
    P extends BaseParams = BaseParams,
> extends View<{model: S['model']}> {

    options: O & {params: P}

    /**
     * @param options Options.
     */
    constructor(options: {[s: string]: unknown} & O & {params: P}) {
        super(options);

        this.name = options.name;

        if (options.labelText != null) {
            this.labelText = options.labelText;
        }
    }

    /**
     * A field type.
     */
    readonly type: string = 'base'

    /**
     * List mode template.
     */
    protected listTemplate: string = 'fields/base/list'

    // noinspection JSUnusedGlobalSymbols
    /**
     * List-link mode template.
     */
    protected listLinkTemplate: string = 'fields/base/list-link'

    /**
     * Detail mode template.
     */
    protected detailTemplate: string = 'fields/base/detail'

    /**
     * Edit mode template.
     */
    protected editTemplate: string = 'fields/base/edit'

    /**
     * Search mode template.
     */
    protected searchTemplate: string = 'fields/base/search'

    // noinspection JSUnusedGlobalSymbols
    /**
     * List template content.
     */
    protected listTemplateContent: string

    // noinspection JSUnusedGlobalSymbols
    /**
     * Detail template content.
     */
    protected detailTemplateContent: string

    // noinspection JSUnusedGlobalSymbols
    /**
     * Edit template content.
     */
    protected editTemplateContent: string

    /**
     * A validation list. A function returning true if non-valid, or a name.
     * For the latter, there should be a `validate{Name}` method in the class.
     *
     * Functions are supported as of v8.3.
     */
    protected validations: (FieldValidator | string)[] = ['required']

    /**
     * List mode.
     */
    readonly MODE_LIST = 'list'

    /**
     * List-link mode.
     */
    readonly MODE_LIST_LINK = 'listLink'

    /**
     * Detail mode.
     */
    readonly MODE_DETAIL = 'detail'

    /**
     * Edit mode.
     */
    readonly MODE_EDIT = 'edit'

    /**
     * Search mode.
     */
    readonly MODE_SEARCH = 'search'

    /**
     * A field name.
     */
    name: string

    /**
     * A field parameter list. To be used for custom fields not defined in metadata > fields.
     *
     * @since 9.0.0
     */
    paramList: string[]

    /**
     * Definitions.
     */
    protected defs: Record<string, any>

    /**
     * Field parameters.
     */
    params: {required?: boolean} & Partial<P> & Record<string, any>

    /**
     * A mode.
     */
    mode: Mode | undefined = 'detail'

    /**
     * Search params.
     */
    protected searchParams: {[s: string]: any} | null = null

    /**
     * Inline edit disabled.
     */
    private inlineEditDisabled: boolean = false

    /**
     * Field is disabled.
     */
    disabled: boolean = false

    /**
     * Field is read-only.
     */
    readOnly: boolean = false

    /**
     * Read-only locked.
     */
    readOnlyLocked: boolean = false

    /**
     * A label text.
     */
    protected labelText: string

    /**
     * @internal
     */
    attributeList: string[] | null = null

    /**
     * Attribute values before edit.
     *
     * @internal
     */
    initialAttributes: {[s: string]: any} | null = null

    /**
     * Validation popover timeout.
     */
    readonly VALIDATION_POPOVER_TIMEOUT = 3000

    /**
     * @internal
     */
    private validateCallback: (() => boolean) | undefined

    /**
     * An element selector to point validation popovers to.
     */
    protected validationElementSelector: string

    /**
     * A view-record helper.
     */
    protected recordHelper: import('view-record-helper').default | null = null

    /**
     * @internal
     */
    private $label: JQuery | null = null

    /**
     * A main form element. Use `mainInputElement` instead.
     *
     * @internal
     */
    protected $element: JQuery | null = null

    /**
     * A main form element.
     * @since 9.2.0
     */
    protected mainInputElement: HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement | null = null

    /**
     * Is searchable once a search filter is added (no need to type or selecting anything).
     * Actual for search mode.
     *
     * @internal
     */
    initialSearchIsNotIdle: boolean = false

    /**
     * An entity type.
     */
    protected entityType: string | null = null

    /**
     * A last validation message.
     *
     * @internal
     */
    lastValidationMessage: string | null = null

    /**
     * Additional data.
     */
    protected dataObject: Record<string, any>

    private _isInlineEditMode: boolean = false

    private disabledLocked: boolean = false

    protected searchData: Record<string, any>

    private _hasTemplateContent: boolean

    protected searchTypeList: string[]

    protected fieldType: string

    private tooltip: string

    protected tooltipText: string

    private validationMessageSuspended: boolean

    private _popoverMap: any

    private _timeoutMap: any

    /**
     * Is the field required.
     */
    isRequired(): boolean {
        return (this.params.required ?? false) as boolean;
    }

    /**
     * Get a cell element. Available only after the view is  rendered.
     */
    private get$cell(): JQuery {
        return this.$el.parent();
    }

    protected getCellElement(): HTMLElement | null {
        return this.get$cell().get(0) ?? null;
    }

    /**
     * Is in inline-edit mode.
     */
    isInlineEditMode(): boolean {
        return this._isInlineEditMode;
    }

    /**
     * Set disabled.
     *
     * @param [locked] Won't be able to set back.
     */
    setDisabled(locked: boolean = false) {
        this.disabled = true;

        if (locked) {
            this.disabledLocked = true;
        }
    }

    /**
     * Set not-disabled.
     */
    setNotDisabled() {
        if (this.disabledLocked) {
            return;
        }

        this.disabled = false;
    }

    /**
     * Set required.
     */
    setRequired() {
        this.params.required = true;

        if (this.isEditMode()) {
            if (this.isRendered()) {
                this.showRequiredSign();
            }
            else {
                this.once('after:render', () => {
                    this.showRequiredSign();
                });
            }
        }
    }

    /**
     * Set not required.
     */
    setNotRequired() {
        this.params.required = false;

        this.get$cell().removeClass('has-error');

        if (this.isEditMode()) {
            if (this.isRendered()) {
                this.hideRequiredSign();
            }
            else {
                this.once('after:render', () => {
                    this.hideRequiredSign();
                });
            }
        }
    }

    /**
     * Set read-only.
     *
     * @param [locked] Won't be able to set back.
     */
    async setReadOnly(locked: boolean): Promise<unknown> {
        if (this.readOnlyLocked) {
            return Promise.reject();
        }

        this.readOnly = true;

        if (locked) {
            this.readOnlyLocked = true;
        }

        if (!this.isReady) {
            if (!this.mode || !this._initCalled) {
                this.mode = 'detail';

                return Promise.resolve();
            }

            return this.setDetailMode();
        }

        if (this.isEditMode()) {
            if (this.isInlineEditMode()) {
                return this.inlineEditClose();
            }

            await this.setDetailMode();

            return await this.reRender();
        }

        return Promise.resolve();
    }

    /**
     * Set not read only.
     */
    setNotReadOnly() {
        if (this.readOnlyLocked) {
            return;
        }

        this.readOnly = false;
    }

    /**
     * Get a label element. Available only after the view is rendered.
     *
     * @internal
     */
    getLabelElement(): JQuery | null {
        if (this.$label && this.$label.get(0) && !document.contains(this.$label.get(0) as HTMLElement)) {
            this.$label = null;
        }

        if (!this.$label || !this.$label.length) {
            this.$label = this.$el.parent().children('label');
        }

        return this.$label ?? null;
    }

    /**
     * Hide field and label. Available only after the view is rendered.
     */
    hide() {
        this.$el.addClass('hidden');
        const $cell = this.get$cell();

        $cell.children('label').addClass('hidden');
        $cell.addClass('hidden-cell');
    }

    /**
     * Show field and label. Available only after the view is rendered.
     */
    show() {
        this.$el.removeClass('hidden');

        const $cell = this.get$cell();

        $cell.children('label').removeClass('hidden');
        $cell.removeClass('hidden-cell');
    }

    /**
     * @inheritDoc
     */
    protected data(): Record<string, any> {
        const data = {
            scope: this.model.entityType || this.model.name,
            name: this.name,
            defs: this.defs,
            params: this.params,
            value: this.getValueForDisplay(),
        } as Record<string, any>;

        if (this.isSearchMode()) {
            data.searchParams = this.searchParams;
            data.searchData = this.searchData;
            data.searchValues = this.getSearchValues();
            data.searchType = this.getSearchType();
            data.searchTypeList = this.getSearchTypeList();
        }

        return data;
    }

    /**
     * Get a value for display. Is available by using a `{value}` placeholder in templates.
     */
    protected getValueForDisplay(): any {
        return this.model.get(this.name);
    }

    /**
     * Is in list, detail or list-link mode.
     */
    isReadMode(): boolean {
        return this.mode === this.MODE_LIST ||
            this.mode === this.MODE_DETAIL ||
            this.mode === this.MODE_LIST_LINK;
    }

    /**
     * Is in list or list-link mode.
     */
    isListMode(): boolean {
        return this.mode === this.MODE_LIST || this.mode === this.MODE_LIST_LINK;
    }

    /**
     * Is in detail mode.
     */
    isDetailMode(): boolean {
        return this.mode === this.MODE_DETAIL;
    }

    /**
     * Is in edit mode.
     */
    isEditMode(): boolean {
        return this.mode === this.MODE_EDIT;
    }

    /**
     * Is in search mode.
     */
    isSearchMode(): boolean {
        return this.mode === this.MODE_SEARCH;
    }

    /**
     * Set detail mode.
     */
    setDetailMode(): Promise<void> {
        return this.setMode(this.MODE_DETAIL) || Promise.resolve();
    }

    /**
     * Set edit mode.
     */
    setEditMode(): Promise<void> {
        return this.setMode(this.MODE_EDIT) || Promise.resolve();
    }

    /**
     * Set a mode.
     *
     * @internal
     */
    setMode(mode: Mode): Promise<void> {
        const modeIsChanged = this.mode !== mode && this.mode;
        const modeBefore = this.mode;

        this.mode = mode;

        const property = mode + 'Template';

        const self = this as any;

        if (!(property in this)) {
            self[property] = 'fields/' + Espo.Utils.camelCaseToHyphen(this.type) + '/' + this.mode;
        }

        if (!this._hasTemplateContent) {
            this.setTemplate(self[property]);
        }

        const contentProperty = mode + 'TemplateContent';

        if (!this._hasTemplateContent) {
            if (contentProperty in self && self[contentProperty] != null) {
                this.setTemplateContent(self[contentProperty] as string);
            }
        }

        if (modeIsChanged) {
            if (modeBefore) {
                this.trigger('mode-changed');
            }

            return this._onModeSet();
        }

        return Promise.resolve();
    }

    /**
     * Called on mode change and on value change before re-rendering.
     * To be used for additional initialization that depends on field
     * values or mode.
     */
    protected prepare(): Promise<void> | undefined {
        return undefined;
    }

    private _onModeSet(): Promise<void> {
        if (this.isListMode()) {
            return this.onListModeSet() || Promise.resolve();
        }

        if (this.isDetailMode()) {
            return this.onDetailModeSet() || Promise.resolve();
        }

        if (this.isEditMode()) {
            return this.onEditModeSet() || Promise.resolve();
        }

        return Promise.resolve();
    }

    /**
     * Additional initialization for the detail mode.
     */
    protected onDetailModeSet(): Promise<void> | undefined {
        return this.prepare();
    }

    /**
     * Additional initialization for the edit mode.
     */
    protected onEditModeSet(): Promise<void> | undefined {
        return this.prepare();
    }

    /**
     * Additional initialization for the list mode.
     *
     * @protected
     * @returns {Promise|undefined}
     */
    protected onListModeSet(): Promise<void> | undefined {
        return this.prepare();
    }

    private _initCalled: boolean = false

    /**
     * @internal
     */
    protected init() {
        this.validations = Espo.Utils.clone(this.validations);
        this.searchTypeList = Espo.Utils.clone(this.searchTypeList);

        this._hasTemplateContent = !!this.templateContent;

        this.defs = this.options.defs || {};
        this.name = this.options.name || this.defs.name;
        this.params = this.options.params ?? this.defs.params ?? {};
        this.validateCallback = this.options.validateCallback;

        this.fieldType = this.model.getFieldParam(this.name, 'type') || this.type;
        this.entityType = this.model.entityType || this.model.name;

        this.recordHelper = this.options.recordHelper ?? null;
        this.dataObject = Espo.Utils.clone(this.options.dataObject || {});

        if (!this.labelText) {
            this.labelText = this.translate(this.name, 'fields', this.entityType ?? undefined);
        }

        const paramList = this.paramList || this.getFieldManager().getParamList(this.type).map(it => it.name);

        paramList.forEach(name => {
            if (name in this.params) {
                return;
            }

            (this.params as any)[name] = this.model.getFieldParam(this.name, name);

            if (typeof this.params[name] === 'undefined') {
                (this.params as any)[name] = null;
            }
        });

        const additionalParamList = ['inlineEditDisabled'];

        additionalParamList.forEach((item) => {
            (this.params as any)[item] = this.model.getFieldParam(this.name, item) || null;
        });

        this.readOnly = this.readOnly || this.params.readOnly ||
            this.model.getFieldParam(this.name, 'readOnly') ||
            this.model.getFieldParam(this.name, 'clientReadOnly');

        if (
            !this.model.isNew() &&
            this.model.getFieldParam(this.name, 'readOnlyAfterCreate')
        ) {
            this.readOnly = true;
        }

        this.readOnlyLocked = this.options.readOnlyLocked || this.readOnly;

        this.inlineEditDisabled = this.options.inlineEditDisabled ||
            this.params.inlineEditDisabled || this.inlineEditDisabled;

        this.readOnly = this.readOnlyLocked || this.options.readOnly || false;

        this.tooltip = this.options.tooltip || this.params.tooltip ||
            this.model.getFieldParam(this.name, 'tooltip') || this.tooltip;

        if (this.options.readOnlyDisabled) {
            this.readOnly = false;
        }

        this.disabledLocked = this.options.disabledLocked || false;
        this.disabled = this.disabledLocked || this.options.disabled || this.disabled;

        let mode = this.options.mode || this.mode || this.MODE_DETAIL;

        if (mode === this.MODE_EDIT && this.readOnly) {
            mode = this.MODE_DETAIL;
        }

        this.mode = undefined;

        this._initCalled = true;

        this.wait(
            this.setMode(mode)
        );

        if (this.isSearchMode()) {
            this.searchParams = _.clone(this.options.searchParams || {});
            this.searchData = {};
            this.setupSearch();

            this.events['keydown.' + this.cid] = e => {
                if (Espo.Utils.getKeyFromKeyEvent(e.originalEvent as KeyboardEvent) === 'Control+Enter') {
                    this.trigger('search');
                }
            };
        }

        this.on('highlight', () => {
            const $cell = this.get$cell();

            $cell.addClass('highlighted');
            $cell.addClass('transition');

            setTimeout(() => {
                $cell.removeClass('highlighted');
            }, 3000);

            setTimeout(() => {
                $cell.removeClass('transition');
            }, 3000 + 2000);
        });

        this.on('invalid', () => {
            const $cell = this.get$cell();

            $cell.addClass('has-error');

            this.$el.one('click', () => {
                $cell.removeClass('has-error');
            });

            this.once('render', () => {
                $cell.removeClass('has-error');
            });
        });

        this.on('after:render', () => {
            if (this.isEditMode()) {
                if (this.hasRequiredMarker()) {
                    this.showRequiredSign();

                    return;
                }

                this.hideRequiredSign();

                return;
            }

            if (this.hasRequiredMarker()) {
                this.hideRequiredSign();
            }

            if (this.isSearchMode()) {
                const $searchType = this.$el.find('select.search-type');

                if ($searchType.length) {
                    Select.init($searchType, {matchAnyWord: true});
                }
            }
        });

        if ((this.isDetailMode() || this.isEditMode()) && this.tooltip) {
            this.initTooltip();
        }

        if (this.isDetailMode()) {
            if (!this.inlineEditDisabled) {
                this.listenToOnce(this, 'after:render', () => this.initInlineEdit());
            }
        }

        if (!this.isSearchMode()) {
            this.attributeList = this.getAttributeList(); // for backward compatibility, to be removed

            this.listenTo(this.model, 'change', (model, options) => {
                if (options.ui && (!options.fromField || options.fromField === this.name)) {
                    return;
                }

                let changed = false;

                for (const attribute of this.getAttributeList()) {
                    if (model.hasChanged(attribute)) {
                        changed = true;

                        break;
                    }
                }

                if (!changed) {
                    return;
                }

                if (options.fromField === this.name) {
                    return;
                }

                if (options.skipReRenderInEditMode && this.isEditMode()) {
                    return;
                }

                if (options.skipReRender) {
                    return;
                }

                if (this.isEditMode() && this.toSkipReRenderOnChange()) {
                    return;
                }

                const reRender = () => {
                    if (!this.isRendered() && !this.isBeingRendered()) {
                        return;
                    }

                    this.reRender();

                    if (options.highlight) {
                        this.trigger('highlight');
                    }
                };

                if (!this.isReady) {
                    this.once('ready', () => {
                        const promise = this.prepare();

                        if (promise) {
                            promise.then(() => reRender());
                        }
                    });

                    return;
                }

                const promise = this.prepare();

                if (promise) {
                    promise.then(() => reRender());

                    return;
                }

                reRender();
            });

            this.listenTo(this, 'change', () => {
                const attributes = this.fetch();

                this.model.setMultiple(attributes, {
                    ui: true,
                    fromView: this,
                    fromField: this.name,
                    action: 'ui',
                });
            });
        }
    }

    highlight() {
        const $cell = this.get$cell();

        $cell.addClass('highlighted');
    }

    /** @inheritDoc */
    protected setupFinal() {
        this.wait(
            this._onModeSet()
        );
    }

    /**
     * @internal
     */
    private initTooltip() {
        let $a : JQuery;

        this.once('after:render', () => {
            $a = $('<a>')
                .attr('role', 'button')
                .attr('tabindex', '-1')
                .addClass('text-muted field-info')
                .append(
                    $('<span>').addClass('fas fa-info-circle')
                );

            const $label = this.getLabelElement();

            $label?.append(' ');

            this.getLabelElement()?.append($a);

            let tooltipText = this.options.tooltipText || this.tooltipText;

            if (!tooltipText && typeof this.tooltip === 'string') {
                const [scope, field] = this.tooltip.includes('.') ?
                    this.tooltip.split('.') :
                    [this.entityType, this.tooltip];

                tooltipText = this.translate(field, 'tooltips', scope ?? undefined);
            }

            tooltipText = tooltipText || this.translate(this.name, 'tooltips', this.entityType ?? undefined) || '';
            tooltipText = this.getHelper()
                .transformMarkdownText(tooltipText, {linksInNewTab: true}).toString();

            Ui.popover($a.get(0) as Element, {
                placement: 'bottom',
                content: tooltipText,
                preventDestroyOnRender: true,
            }, this as View);
        });
    }

    /**
     * Show a required-field sign.
     */
    private showRequiredSign() {
        const $label = this.getLabelElement();

        if (!$label) {
            return;
        }

        let $sign = $label.find('span.required-sign');

        if ($label.length && !$sign.length) {
            const $text = $label.find('span.label-text');

            $('<span class="required-sign"> *</span>').insertAfter($text);
            $sign = $label.find('span.required-sign');
        }

        $sign.show();
    }

    /**
     * Hide a required-field sign.
     */
    private hideRequiredSign() {
        const $label = this.getLabelElement();
        const $sign = $label?.find('span.required-sign');

        $sign?.hide();
    }

    /**
     * Get search-params data.
     */
    protected getSearchParamsData(): Record<string, any> {
        return this.searchParams?.data || {};
    }

    /**
     * Get search values.
     */
    protected getSearchValues(): Record<string, any> {
        return this.getSearchParamsData().values || {};
    }

    /**
     * Get a current search type.
     */
    protected getSearchType(): string | null {
        return this.getSearchParamsData().type ?? this.searchParams?.type ?? null;
    }

    /**
     * Get the search type list.
     */
    protected getSearchTypeList(): string[] {
        return this.searchTypeList;
    }

    /**
     * @internal
     */
    private initInlineEdit() {
        const cell = this.getCellElement();

        const edit = document.createElement('a');
        edit.role = 'button';
        edit.classList.add('pull-right', 'inline-edit-link' ,'hidden');
        edit.append(
            (() => {
                const span = document.createElement('span');
                span.classList.add('fas', 'fa-pencil-alt', 'fa-sm');

                return span;
            })()
        )

        if (!cell) {
            this.listenToOnce(this, 'after:render', () => this.initInlineEdit());

            return;
        }

        cell.prepend(edit);

        edit.addEventListener('click', () => this.inlineEdit());

        cell.addEventListener('mouseenter', e => {
            e.stopPropagation();

            if (this.disabled || this.readOnly) {
                return;
            }

            if (this.isDetailMode()) {
                edit.classList.remove('hidden');
            }
        });

        cell.addEventListener('mouseleave', e => {
            e.stopPropagation();

            if (this.isDetailMode()) {
                edit.classList.add('hidden');
            }
        });

        this.on('after:render', () => {
            if (!this.isDetailMode()) {
                edit.classList.add('hidden');
            }
        });
    }

    /**
     * Initializes a form element reference.
     */
    protected initElement() {
        this.mainInputElement = this.element?.querySelector(`[data-name="${this.name}"]`) ??
            this.element?.querySelector(`[name="${this.name}"]`) ??
            this.element?.querySelector('.main-element');

        this.$element = this.mainInputElement ? $(this.mainInputElement) : $();

        if (this.isEditMode()) {
            this.$element.on('change', () => {
                this.trigger('change');
            });
        }
    }

    protected afterRender() {
        if (this.isEditMode() || this.isSearchMode()) {
            this.initElement();
        }

        if (this.isReadMode()) {
            this.afterRenderRead();
        }

        if (this.isListMode()) {
            this.afterRenderList();
        }

        if (this.isDetailMode()) {
            this.afterRenderDetail();
        }

        if (this.isEditMode()) {
            this.afterRenderEdit();
        }

        if (this.isSearchMode()) {
            this.afterRenderSearch();
        }
    }

    /**
     * Called after the view is rendered in list or read mode.
     */
    protected afterRenderRead() {}

    /**
     * Called after the view is rendered in list mode.
     *
     * @protected
     */
    protected afterRenderList() {}

    /**
     * Called after the view is rendered in detail mode.
     *
     * @protected
     */
    protected afterRenderDetail() {}

    /**
     * Called after the view is rendered in edit mode.
     *
     * @protected
     */
    protected afterRenderEdit() {}

    /**
     * Called after the view is rendered in search mode.
     *
     * @protected
     */
    protected afterRenderSearch() {}

    /**
     * Initialization.
     */
    protected setup() {}

    /**
     * Initialization for search mode.
     */
    protected setupSearch() {}

    /**
     * Get list of model attributes that relate to the field.
     * Changing of any attributes makes the field to re-render.
     */
    protected getAttributeList(): string[] {
        return this.getFieldManager().getAttributeList(this.fieldType, this.name);
    }

    /**
     * Invoke inline-edit saving.
     *
     * @param options
     */
    private inlineEditSave(options: {bypassClose?: boolean} = {}) {
        options = options || {}

        if (this.recordHelper) {
            this.recordHelper.trigger('inline-edit-save', this.name, options);

            return;
        }

        // Code below supposed not to be executed.

        let data = this.fetch();

        const model = this.model;
        const prev = this.initialAttributes ?? {};

        model.setMultiple(data, {silent: true});
        data = model.attributes;

        let attrs = false as boolean | Record<string, any>;

        for (const attr in data) {
            if (_.isEqual(prev[attr], data[attr])) {
                continue;
            }

            const itemAttrs = (attrs || (attrs = {})) as Record<string, any>;

            itemAttrs[attr] = data[attr];
        }

        if (!attrs) {
            this.inlineEditClose();
        }

        const isInvalid = this.validateCallback ? this.validateCallback() : this.validate();

        if (isInvalid) {
            Ui.error(this.translate('Not valid'));

            // @todo Revise.
            model.setMultiple(prev, {silent: true});

            return;
        }

        Ui.notify(this.translate('saving', 'messages'));

        model
            .save(attrs as Record<string, any>, {patch: true})
            .then(() => {
                this.trigger('after:inline-save');
                this.trigger('after:save');

                model.trigger('after:save');

                Ui.success(this.translate('Saved'));
            })
            .catch(() => {
                Ui.error(this.translate('Error occurred'));

                // @todo Revise.
                model.setMultiple(prev, {silent: true});

                this.reRender();
            });

        if (!options.bypassClose) {
            this.inlineEditClose(true);
        }
    }

    removeInlineEditLinks() {
        const $cell = this.get$cell();

        $cell.find('.inline-save-link').remove();
        $cell.find('.inline-cancel-link').remove();
        $cell.find('.inline-edit-link').addClass('hidden');
    }

    private addInlineEditLinks() {
        const $cell = this.get$cell();

        const saveLink = document.createElement('a');
        saveLink.role = 'button';
        saveLink.tabIndex = -1;
        saveLink.title = this.translate('Update') + ' · ' + 'Ctrl+Enter';
        saveLink.innerHTML = `<span class="fas fa-check"></span>`;
        saveLink.classList.add('inline-save-link');

        const cancelLink = document.createElement('a');
        cancelLink.role = 'button';
        cancelLink.tabIndex = -1;
        cancelLink.title = this.translate('Cancel') + ' · ' + 'Esc';
        cancelLink.innerHTML = `<span class="fas fa-arrow-right-to-bracket"></span>`;
        cancelLink.classList.add('inline-cancel-link');

        $cell.prepend(saveLink);
        $cell.prepend(cancelLink);

        $cell.find('.inline-edit-link').addClass('hidden');

        saveLink.onclick = () => this.inlineEditSave();
        cancelLink.onclick = () => this.inlineEditClose();
    }

    /**
     * @internal
     */
    setIsInlineEditMode(value: boolean) {
        this._isInlineEditMode = value;
    }

    /**
     * Exist inline-edit mode.
     *
     * @param noReset
     */
    inlineEditClose(noReset: boolean = false): Promise<void> {
        this.trigger('inline-edit-off', {noReset: noReset});

        if (this.recordHelper) {
            this.recordHelper.off('continue-inline-edit');
        }

        this.$el.off('keydown.inline-edit');

        this._isInlineEditMode = false;

        if (!this.isEditMode()) {
            return Promise.resolve();
        }

        if (!noReset) {
            this.model.setMultiple(this.initialAttributes ?? {}, {
                skipReRenderInEditMode: true,
                action: 'cancel-edit',
            });
        }

        const promise = this.setDetailMode()
            .then(() => this.reRender(true))
            .then(() => this.removeInlineEditLinks());

        this.trigger('after:inline-edit-off', {noReset: noReset});

        return promise;
    }

    /**
     * Switch to inline-edit mode.
     */
    async inlineEdit(): Promise<void> {
        if (this.recordHelper && this.recordHelper.isChanged()) {
            await this.confirm({
                message: this.translate('changesLossConfirmation', 'messages'),
                cancelCallback: () => this.recordHelper?.trigger('continue-inline-edit'),
            });
        }

        this.trigger('edit', this);

        this.initialAttributes = this.model.getClonedAttributes();

        this._isInlineEditMode = true;

        this.trigger('inline-edit-on');

        await this.setEditMode();
        await this.reRender(true);

        this.addInlineEditLinks();

        if (this.recordHelper) {
            this.recordHelper.on('continue-inline-edit', () => this.focusOnInlineEdit())
        }

        this.$el.on('keydown.inline-edit', (e: any) => {
            const key = Espo.Utils.getKeyFromKeyEvent(e);

            if (key === 'Control+Enter') {
                e.stopPropagation();

                if (document.activeElement instanceof HTMLInputElement) {
                    // Fields may need to fetch data first.
                    document.activeElement.dispatchEvent(new Event('change', {bubbles: true}));
                }

                this.fetchToModel();
                this.inlineEditSave();

                setTimeout(() => {
                    this.get$cell().trigger('focus');
                }, 100);

                return;
            }

            if (key === 'Escape') {
                e.stopPropagation();

                this.inlineEditClose()
                    .then(() => {
                        this.get$cell().trigger('focus');
                    });

                return;
            }

            if (key === 'Control+KeyS') {
                e.preventDefault();
                e.stopPropagation();

                this.fetchToModel();
                this.inlineEditSave({bypassClose: true});
            }
        });

        setTimeout(() => this.focusOnInlineEdit(), 10);
    }

    protected focusOnInlineEdit() {
        const $element = this.$element && this.$element.length ?
            this.$element :
            this.$el.find('.form-control').first();

        if (!$element) {
            return;
        }

        $element.first().focus();
    }

    /**
     * Suspend a validation message.
     *
     * @internal
     */
    suspendValidationMessage(time?: number) {
        this.validationMessageSuspended = true;

        setTimeout(() => this.validationMessageSuspended = false, time || 200);
    }

    /**
     * Show a validation message.
     *
     * @param {string} message A message.
     * @param {string|JQuery|Element} [target] A target element or selector.
     * @param {View} [view] A child view that contains the target. The closest view should to passed.
     *   Should be omitted if there is no child views or the target is not rendered by a child view.
     */
    showValidationMessage(message: string, target?: string | Element, view?: View) {
        if (this.validationMessageSuspended) {
            return;
        }

        let $el: any;

        target = target || this.validationElementSelector || '.main-element';

        if (typeof target === 'string' || target instanceof String) {
            $el = this.$el.find(target);
        } else {
            $el = $(target);
        }

        if (!$el.length && this.$element) {
            $el = this.$element;
        }

        if (!$el.length) {
            $el = this.$el;
        }

        if ($el.length) {
            const rect = $el.get(0).getBoundingClientRect();

            this.lastValidationMessage = message;

            if (rect.top === 0 && rect.bottom === 0 && rect.left === 0) {
                return;
            }
        }

        this._popoverMap = this._popoverMap || new WeakMap();
        const element = $el.get(0);

        if (!element) {
            return;
        }

        if (this._popoverMap.has(element)) {
            try {
                this._popoverMap.get(element).detach();
            } catch (e) {}
        }

        const popover = Ui.popover($el, {
            placement: 'bottom',
            container: 'body',
            content: this.getHelper().transformMarkdownText(message).toString(),
            trigger: 'manual',
            noToggleInit: true,
            noHideOnOutsideClick: true,
        }, (view ?? this) as View);

        popover.show();

        this._popoverMap.set(element, popover);

        $el.closest('.field').one('mousedown click', () => popover.destroy());

        this.once('render remove', () => popover.destroy());

        this._timeoutMap = this._timeoutMap || new WeakMap();

        if (this._timeoutMap.has(element)) {
            clearTimeout(this._timeoutMap.get(element));
        }

        const timeout = setTimeout(() => {
            popover.destroy();
        }, this.VALIDATION_POPOVER_TIMEOUT);

        this._timeoutMap.set(element, timeout);
    }

    /**
     * Validate field values.
     *
     * @return True if not valid.
     */
    validate(): boolean {
        this.lastValidationMessage = null;

        for (const item of this.validations) {
            let notValid = false;

            if (typeof item === 'function') {
                notValid = item();
            } else {
                const method = 'validate' + Espo.Utils.upperCaseFirst(item);

                const fn = (this as any)[method];

                if (typeof fn === 'function') {
                    notValid = fn.call(this);
                } else {
                    throw new Error(`No '${method}' method.`)
                }
            }

            if (notValid) {
                this.trigger('invalid');

                return true;
            }
        }

        return false;
    }

    /**
     * Get a label text.
     */
    getLabelText(): string {
        return this.labelText;
    }

    /**
     * Validate required.
     */
    validateRequired(): boolean {
        if (this.isRequired()) {
            if (this.model.get(this.name) === '' || this.model.get(this.name) === null) {
                const msg = this.translate('fieldIsRequired', 'messages')
                    .replace('{field}', this.getLabelText());

                this.showValidationMessage(msg);

                return true;
            }
        }

        return false;
    }

    /**
     * Defines whether the field should have a required-marker rendered.
     */
    protected hasRequiredMarker(): boolean {
        return this.isRequired();
    }

    /**
     * Fetch field values to the model.
     */
    fetchToModel() {
        this.model.setMultiple(this.fetch(), {silent: true});
    }

    /**
     * Fetch field values from DOM.
     */
    fetch(): Record<string, unknown> {
        if (!this.$element?.length) {
            return {};
        }

        const data = {} as Record<string, any>;

        data[this.name] = (this.$element?.val() as string).trim();

        return data;
    }

    /**
     * Fetch search data from DOM.
     */
    fetchSearch(): Record<string, unknown> | null {
        const value = this.$element?.val()?.toString()?.trim();

        if (value) {
            return {
                type: 'equals',
                value: value,
            };
        }

        return null;
    }

    /**
     * Fetch a search type from DOM.
     */
    protected fetchSearchType(): string {
        return this.$el.find('select.search-type').val();
    }

    /**
     * To skip re-render on change in edit mode.
     * @since 9.1.2
     */
    protected toSkipReRenderOnChange(): boolean {
        return false;
    }
}
