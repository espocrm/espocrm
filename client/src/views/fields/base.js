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

/** @module views/fields/base */

import View from 'view';
import Select from 'ui/select';
import $ from 'jquery';

/**
 * A base field view. Can be in different modes. Each mode uses a separate template.
 *
 * @todo Document events.
 * @template TParams
 */
class BaseFieldView extends View {

    /**
     * @typedef {Object} module:views/fields/base~options
     * @property {string} name A field name.
     * @property {module:model} [model] A model.
     * @property {module:views/fields/base~params | Object.<string, *>} [params] Parameters.
     * @property {boolean} [inlineEditDisabled] Disable inline edit.
     * @property {boolean} [readOnly] Read-only.
     * @property {string} [labelText] A custom label text.
     * @property {'detail'|'edit'|'list'|'search'} [mode] A mode.
     * @property {import('view-record-helper').default} [recordHelper] A record helper.
     */

    /**
     * @typedef {Object} module:views/fields/base~params
     * @property {boolean} [inlineEditDisabled] Disable inline edit.
     * @property {boolean} [readOnly] Read-only.
     */

    /**
     * @param {module:views/fields/base~options | Object.<string, *>} options Options.
     */
    constructor(options) {
        super(options);

        this.name = options.name;
        this.labelText = options.labelText;
    }

    /**
     * A field type.
     *
     * @type {string}
     */
    type = 'base'

    /**
     * List mode template.
     *
     * @protected
     * @type {string}
     */
    listTemplate = 'fields/base/list'

    // noinspection JSUnusedGlobalSymbols
    /**
     * List-link mode template.
     *
     * @protected
     * @type {string}
     */
    listLinkTemplate = 'fields/base/list-link'

    /**
     * Detail mode template.
     *
     * @protected
     * @type {string}
     */
    detailTemplate = 'fields/base/detail'

    /**
     * Edit mode template.
     *
     * @protected
     * @type {string}
     */
    editTemplate = 'fields/base/edit'

    /**
     * Search mode template.
     *
     * @protected
     * @type {string}
     */
    searchTemplate = 'fields/base/search'

    // noinspection JSUnusedGlobalSymbols
    /**
     * @protected
     * @type {string}
     */
    listTemplateContent

    // noinspection JSUnusedGlobalSymbols
    /**
     * @protected
     * @type {string}
     */
    detailTemplateContent

    // noinspection JSUnusedGlobalSymbols
    /**
     * @protected
     * @type {string}
     */
    editTemplateContent

    /**
     * A validation list. A function returning true if non-valid, or a name.
     * For the latter, there should be a `validate{Name}` method in the class.
     *
     * Functions are supported as of v8.3.
     *
     * @type {Array<(function (): boolean)|string>}
     */
    validations = ['required']

    /**
     * @const
     */
    MODE_LIST = 'list'

    /**
     * @const
     */
    MODE_LIST_LINK = 'listLink'

    /**
     * @const
     */
    MODE_DETAIL = 'detail'

    /**
     * @const
     */
    MODE_EDIT = 'edit'

    /**
     * @const
     */
    MODE_SEARCH = 'search'

    /**
     * A field name.
     *
     * @type {string}
     */
    name

    /**
     * A field parameter list. To be used for custom fields not defined in metadata > fields.
     *
     * @type {string[]}
     * @since 9.0.0
     */
    paramList

    /**
     * Definitions.
     *
     * @type {Object}
     */
    defs = null

    /**
     * Field params.
     *
     * @type {TParams & module:views/fields/base~params & Object.<string, *>}
     */
    params = null

    /**
     * A mode.
     *
     * @type {'list'|'listLink'|'detail'|'edit'|'search'}
     */
    mode = 'detail'

    /**
     * Search params.
     *
     * @type {Object.<string,*>|null}
     */
    searchParams = null

    /**
     * Inline edit disabled.
     *
     * @type {boolean}
     */
    inlineEditDisabled = false

    /**
     * Field is disabled.
     *
     * @type {boolean}
     */
    disabled = false

    /**
     * Field is read-only.
     *
     * @type {boolean}
     */
    readOnly = false

    /**
     * A label text.
     *
     * @type {string}
     * @protected
     */
    labelText

    /**
     * @type {string[]|null}
     */
    attributeList = null

    /**
     * Attribute values before edit.
     *
     * @type {Object.<string, *>|{}}
     */
    initialAttributes = null

    /**
     * @const
     */
    VALIDATION_POPOVER_TIMEOUT = 3000

    /**
     * @type {(function():boolean)}
     * @private
     * @internal
     */
    validateCallback

    /**
     * An element selector to point validation popovers to.
     *
     * @type {string}
     * @protected
     */
    validationElementSelector

    /**
     * A view-record helper.
     *
     * @type {import('view-record-helper').default|null}
     */
    recordHelper = null

    /**
     * @type {JQuery|null}
     * @private
     * @internal
     */
    $label = null

    /**
     * A form element.
     *
     * @type {JQuery|null}
     * @protected
     */
    $element = null

    /**
     * Is searchable once a search filter is added (no need to type or selecting anything).
     * Actual for search mode.
     *
     * @public
     * @type {boolean}
     */
    initialSearchIsNotIdle = false

    /**
     * An entity type.
     *
     * @private
     * @type {string|null}
     */
    entityType = null

    /**
     * A last validation message;
     *
     * @type {?string}
     */
    lastValidationMessage = null

    /**
     * Additional data.
     *
     * @type {Object.<string, *>}
     */
    dataObject

    /**
     * Is the field required.
     *
     * @returns {boolean}
     */
    isRequired() {
        return this.params.required;
    }

    /**
     * Get a cell element. Available only after the view is  rendered.
     *
     * @private
     * @returns {JQuery}
     */
    get$cell() {
        return this.$el.parent();
    }

    /**
     * @protected
     * @returns {HTMLElement|null}
     */
    getCellElement() {
        return this.get$cell().get(0) ?? null;
    }

    /**
     * Is in inline-edit mode.
     *
     * @return {boolean}
     */
    isInlineEditMode() {
        return !!this._isInlineEditMode;
    }

    /**
     * Set disabled.
     *
     * @param {boolean} [locked] Won't be able to set back.
     */
    setDisabled(locked) {
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
     * @param {boolean} [locked] Won't be able to set back.
     * @return {Promise}
     */
    setReadOnly(locked) {
        if (this.readOnlyLocked) {
            return Promise.reject();
        }

        this.readOnly = true;

        if (locked) {
            this.readOnlyLocked = true;
        }

        if (!this.isReady) {
            this.mode = 'detail';

            return Promise.resolve();
        }

        if (this.isEditMode()) {
            if (this.isInlineEditMode()) {
                return this.inlineEditClose();
            }

            return this.setDetailMode()
                .then(() => this.reRender());
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
     * @return {JQuery}
     */
    getLabelElement() {
        if (this.$label && this.$label.get(0) && !document.contains(this.$label.get(0))) {
            this.$label = undefined;
        }

        if (!this.$label || !this.$label.length) {
            this.$label = this.$el.parent().children('label');
        }

        return this.$label;
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
     * @return {Record<string, *>}
     */
    data() {
        const data = {
            scope: this.model.entityType || this.model.name,
            name: this.name,
            defs: this.defs,
            params: this.params,
            value: this.getValueForDisplay(),
        };

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
     *
     * @return {*}
     */
    getValueForDisplay() {
        return this.model.get(this.name);
    }

    /**
     * Is in list, detail or list-link mode.
     *
     * @returns {boolean}
     */
    isReadMode() {
        return this.mode === this.MODE_LIST ||
            this.mode === this.MODE_DETAIL ||
            this.mode === this.MODE_LIST_LINK;
    }

    /**
     * Is in list or list-link mode.
     *
     * @returns {boolean}
     */
    isListMode() {
        return this.mode === this.MODE_LIST || this.mode === this.MODE_LIST_LINK;
    }

    /**
     * Is in detail mode.
     *
     * @returns {boolean}
     */
    isDetailMode() {
        return this.mode === this.MODE_DETAIL;
    }

    /**
     * Is in edit mode.
     *
     * @returns {boolean}
     */
    isEditMode() {
        return this.mode === this.MODE_EDIT;
    }

    /**
     * Is in search mode.
     *
     * @returns {boolean}
     */
    isSearchMode() {
        return this.mode === this.MODE_SEARCH;
    }

    /**
     * Set detail mode.
     *
     * @returns {Promise}
     */
    setDetailMode() {
        return this.setMode(this.MODE_DETAIL) || Promise.resolve();
    }

    /**
     * Set edit mode.
     *
     * @returns {Promise}
     */
    setEditMode() {
        return this.setMode(this.MODE_EDIT) || Promise.resolve();
    }

    /**
     * Set a mode.
     *
     * @internal
     * @returns {Promise}
     */
    setMode(mode) {
        const modeIsChanged = this.mode !== mode && this.mode;
        const modeBefore = this.mode;

        this.mode = mode;

        const property = mode + 'Template';

        if (!(property in this)) {
            this[property] = 'fields/' + Espo.Utils.camelCaseToHyphen(this.type) + '/' + this.mode;
        }

        if (!this._hasTemplateContent) {
            this.setTemplate(this[property]);
        }

        const contentProperty = mode + 'TemplateContent';

        if (!this._hasTemplateContent) {
            if (contentProperty in this && this[contentProperty] != null) {
                this.setTemplateContent(this[contentProperty]);
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
     *
     * @protected
     * @returns {Promise|undefined}
     */
    prepare() {}

    /**
     * @private
     * @returns {Promise}
     */
    _onModeSet() {
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
     *
     * @protected
     * @returns {Promise|undefined}
     */
    onDetailModeSet() {
        return this.prepare();
    }

    /**
     * Additional initialization for the edit mode.
     *
     * @protected
     * @returns {Promise|undefined}
     */
    onEditModeSet() {
        return this.prepare();
    }

    /**
     * Additional initialization for the list mode.
     *
     * @protected
     * @returns {Promise|undefined}
     */
    onListModeSet() {
        return this.prepare();
    }

    /** @inheritDoc */
    init() {
        this.validations = Espo.Utils.clone(this.validations);
        this.searchTypeList = Espo.Utils.clone(this.searchTypeList);

        this._hasTemplateContent = !!this.templateContent;

        this.defs = this.options.defs || {};
        this.name = this.options.name || this.defs.name;
        this.params = this.options.params || this.defs.params || {};
        this.validateCallback = this.options.validateCallback;

        this.fieldType = this.model.getFieldParam(this.name, 'type') || this.type;
        this.entityType = this.model.entityType || this.model.name;

        this.recordHelper = this.options.recordHelper ?? null;
        this.dataObject = Espo.Utils.clone(this.options.dataObject || {});

        if (!this.labelText) {
            this.labelText = this.translate(this.name, 'fields', this.entityType);
        }

        const paramList = this.paramList || this.getFieldManager().getParamList(this.type).map(it => it.name);

        paramList.forEach(name => {
            if (name in this.params) {
                return;
            }

            this.params[name] = this.model.getFieldParam(this.name, name);

            if (typeof this.params[name] === 'undefined') {
                this.params[name] = null;
            }
        });

        const additionalParamList = ['inlineEditDisabled'];

        additionalParamList.forEach((item) => {
            this.params[item] = this.model.getFieldParam(this.name, item) || null;
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

        this.wait(
            this.setMode(mode)
        );

        if (this.isSearchMode()) {
            this.searchParams = _.clone(this.options.searchParams || {});
            this.searchData = {};
            this.setupSearch();

            this.events['keydown.' + this.cid] = /** JQueryKeyEventObject */e => {
                if (Espo.Utils.getKeyFromKeyEvent(e) === 'Control+Enter') {
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

                this.model.set(attributes, {
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
    setupFinal() {
        this.wait(
            this._onModeSet()
        );
    }

    /**
     * @internal
     * @private
     */
    initTooltip() {
        let $a;

        this.once('after:render', () => {
            $a = $('<a>')
                .attr('role', 'button')
                .attr('tabindex', '-1')
                .addClass('text-muted field-info')
                .append(
                    $('<span>').addClass('fas fa-info-circle')
                );

            const $label = this.getLabelElement();

            $label.append(' ');

            this.getLabelElement().append($a);

            let tooltipText = this.options.tooltipText || this.tooltipText;

            if (!tooltipText && typeof this.tooltip === 'string') {
                const [scope, field] = this.tooltip.includes('.') ?
                    this.tooltip.split('.') :
                    [this.entityType, this.tooltip];

                tooltipText = this.translate(field, 'tooltips', scope);
            }

            tooltipText = tooltipText || this.translate(this.name, 'tooltips', this.entityType) || '';
            tooltipText = this.getHelper()
                .transformMarkdownText(tooltipText, {linksInNewTab: true}).toString();

            Espo.Ui.popover($a, {
                placement: 'bottom',
                content: tooltipText,
                preventDestroyOnRender: true,
            }, this);
        });
    }

    /**
     * Show a required-field sign.
     *
     * @private
     */
    showRequiredSign() {
        const $label = this.getLabelElement();
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
     *
     * @private
     */
    hideRequiredSign() {
        const $label = this.getLabelElement();
        const $sign = $label.find('span.required-sign');

        $sign.hide();
    }

    /**
     * Get search-params data.
     *
     * @protected
     * @return {Object.<string,*>}
     */
    getSearchParamsData() {
        return this.searchParams.data || {};
    }

    /**
     * Get search values.
     *
     * @protected
     * @return {Object.<string,*>}
     */
    getSearchValues() {
        return this.getSearchParamsData().values || {};
    }

    /**
     * Get a current search type.
     *
     * @protected
     * @return {string}
     */
    getSearchType() {
        return this.getSearchParamsData().type || this.searchParams.type;
    }

    /**
     * Get the search type list.
     *
     * @protected
     * @returns {string[]}
     */
    getSearchTypeList() {
        return this.searchTypeList;
    }



    /**
     * @private
     * @internal
     */
    initInlineEdit() {
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
     *
     * @protected
     */
    initElement() {
        this.$element = this.$el.find('[data-name="' + this.name + '"]');

        if (!this.$element.length) {
            this.$element = this.$el.find('[name="' + this.name + '"]');
        }

        if (!this.$element.length) {
            this.$element = this.$el.find('.main-element');
        }

        if (this.isEditMode()) {
            this.$element.on('change', () => {
                this.trigger('change');
            });
        }
    }

    /** @inheritDoc */
    afterRender() {
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
     *
     * @protected
     */
    afterRenderRead() {}

    /**
     * Called after the view is rendered in list mode.
     *
     * @protected
     */
    afterRenderList() {}

    /**
     * Called after the view is rendered in detail mode.
     *
     * @protected
     */
    afterRenderDetail() {}

    /**
     * Called after the view is rendered in edit mode.
     *
     * @protected
     */
    afterRenderEdit() {}

    /**
     * Called after the view is rendered in search mode.
     *
     * @protected
     */
    afterRenderSearch() {}

    /**
     * Initialization.
     */
    setup() {}

    /**
     * Initialization for search mode.
     *
     * @protected
     */
    setupSearch() {}

    /**
     * Get list of model attributes that relate to the field.
     * Changing of any attributes makes the field to re-render.
     *
     * @return {string[]}
     */
    getAttributeList() {
        return this.getFieldManager().getAttributeList(this.fieldType, this.name);
    }

    /**
     * Invoke inline-edit saving.
     *
     * @param {{[bypassClose]: boolean}} [options]
     */
    inlineEditSave(options) {
        options = options || {}

        if (this.recordHelper) {
            this.recordHelper.trigger('inline-edit-save', this.name, options);

            return;
        }

        // Code below supposed not to be executed.

        let data = this.fetch();

        const model = this.model;
        const prev = this.initialAttributes;

        model.set(data, {silent: true});
        data = model.attributes;

        let attrs = false;

        for (const attr in data) {
            if (_.isEqual(prev[attr], data[attr])) {
                continue;
            }

            (attrs || (attrs = {}))[attr] = data[attr];
        }

        if (!attrs) {
            this.inlineEditClose();
        }

        const isInvalid = this.validateCallback ? this.validateCallback() : this.validate();

        if (isInvalid) {
            Espo.Ui.error(this.translate('Not valid'));

            // @todo Revise.
            model.set(prev, {silent: true});

            return;
        }

        Espo.Ui.notify(this.translate('saving', 'messages'));

        model
            .save(/** @type Object */attrs, {patch: true})
            .then(() => {
                this.trigger('after:inline-save');
                this.trigger('after:save');

                model.trigger('after:save');

                Espo.Ui.success(this.translate('Saved'));
            })
            .catch(() => {
                Espo.Ui.error(this.translate('Error occurred'));

                // @todo Revise.
                model.set(prev, {silent: true});

                this.reRender();
            });

        if (!options.bypassClose) {
            this.inlineEditClose(true);
        }
    }

    /**
     * @public
     */
    removeInlineEditLinks() {
        const $cell = this.get$cell();

        $cell.find('.inline-save-link').remove();
        $cell.find('.inline-cancel-link').remove();
        $cell.find('.inline-edit-link').addClass('hidden');
    }

    /**
     * @private
     */
    addInlineEditLinks() {
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
     * @public
     * @param {boolean} value
     * @internal
     */
    setIsInlineEditMode(value) {
        this._isInlineEditMode = value;
    }

    /**
     * Exist inline-edit mode.
     *
     * @param {boolean} [noReset]
     * @return {Promise}
     */
    inlineEditClose(noReset) {
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
            this.model.set(this.initialAttributes, {
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
     *
     * @return {Promise}
     */
    async inlineEdit() {
        if (this.recordHelper && this.recordHelper.isChanged()) {
            await this.confirm({
                message: this.translate('changesLossConfirmation', 'messages'),
                cancelCallback: this.recordHelper.trigger('continue-inline-edit'),
            });
        }

        this.trigger('edit', this);

        this.initialAttributes = this.model.getClonedAttributes();

        this._isInlineEditMode = true;

        this.trigger('inline-edit-on');

        await this.setEditMode();
        await this.reRender(true);
        await this.addInlineEditLinks();

        if (this.recordHelper) {
            this.recordHelper.on('continue-inline-edit', () => this.focusOnInlineEdit())
        }

        this.$el.on('keydown.inline-edit', e => {
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
                    this.get$cell().focus();
                }, 100);

                return;
            }

            if (key === 'Escape') {
                e.stopPropagation();

                this.inlineEditClose()
                    .then(() => {
                        this.get$cell().focus();
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

    /**
     * @protected
     */
    focusOnInlineEdit() {
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
     * @param {number} [time=200]
     */
    suspendValidationMessage(time) {
        this.validationMessageSuspended = true;

        setTimeout(() => this.validationMessageSuspended = false, time || 200);
    }

    /**
     * Show a validation message.
     *
     * @param {string} message A message.
     * @param {string|JQuery|Element} [target] A target element or selector.
     * @param {module:view} [view] A child view that contains the target. The closest view should to passed.
     *   Should be omitted if there is no child views or the target is not rendered by a child view.
     */
    showValidationMessage(message, target, view) {
        if (this.validationMessageSuspended) {
            return;
        }

        let $el;

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
            }
            catch (e) {}
        }

        const popover = Espo.Ui.popover($el, {
            placement: 'bottom',
            container: 'body',
            content: this.getHelper().transformMarkdownText(message).toString(),
            trigger: 'manual',
            noToggleInit: true,
            noHideOnOutsideClick: true,
        }, view || this);

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
     * @return {boolean} True if not valid.
     */
    validate() {
        this.lastValidationMessage = null;

        for (const item of this.validations) {
            let notValid = false;

            if (typeof item === 'function') {
                notValid = item();
            } else {
                const method = 'validate' + Espo.Utils.upperCaseFirst(item);

                notValid = this[method].call(this);
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
     *
     * @returns {string}
     */
    getLabelText() {
        return this.labelText;
    }

    /**
     * Validate required.
     *
     * @return {boolean}
     */
    validateRequired() {
        if (this.isRequired()) {
            if (this.model.get(this.name) === '' || this.model.get(this.name) === null) {
                const msg = this.translate('fieldIsRequired', 'messages')
                    .replace('{field}', this.getLabelText());

                this.showValidationMessage(msg);

                return true;
            }
        }
    }

    /**
     * Defines whether the field should have a required-marker rendered.
     *
     * @protected
     * @return {boolean}
     */
    hasRequiredMarker() {
        return this.isRequired();
    }

    /**
     * Fetch field values to the model.
     */
    fetchToModel() {
        this.model.set(this.fetch(), {silent: true});
    }

    /**
     * Fetch field values from DOM.
     *
     * @return {Object.<string, *>}
     */
    fetch() {
        if (!this.$element.length) {
            return {};
        }

        const data = {};

        data[this.name] = this.$element.val().trim();

        return data;
    }

    /**
     * Fetch search data from DOM.
     *
     * @return {Object.<string, *>|null}
     */
    fetchSearch() {
        const value = this.$element.val().toString().trim();

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
     *
     * @return {string}
     */
    fetchSearchType() {
        return this.$el.find('select.search-type').val();
    }

    /**
     * To skip re-render on change in edit mode.
     *
     * @protected
     * @since 9.1.2
     */
    toSkipReRenderOnChange() {
        return false;
    }
}

export default BaseFieldView;
