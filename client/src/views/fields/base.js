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

define('views/fields/base', 'view', function (Dep) {

    /**
     * A base field view. Can be in different modes. Each mode uses a separate template.
     *
     * @todo Document all options.
     */
    return Dep.extend({

        /**
         * @property {string} A field type.
         */
        type: 'base',

        /**
         * @property {string} List mode template.
         */
        listTemplate: 'fields/base/list',

        /**
         * @property {string} List-link mode template.
         */
        listLinkTemplate: 'fields/base/list-link',

        /**
         * @property {string} Detail mode template.
         */
        detailTemplate: 'fields/base/detail',

        /**
         * @property {string} Edit mode template.
         */
        editTemplate: 'fields/base/edit',

        /**
         * @property {string} Search mode template.
         */
        searchTemplate: 'fields/base/search',

        /**
         * @property {Array.<string>} A validation list. There should be a validate{Name} method for each item.
         */
        validations: ['required'],

        MODE_LIST: 'list',

        MODE_LIST_LINK: 'listLink',

        MODE_DETAIL: 'detail',

        MODE_EDIT: 'edit',

        MODE_SEARCH: 'search',

        /**
         * @property A field name.
         */
        name: null,

        defs: null,

        /**
         * @property Field params.
         */
        params: null,

        /**
         * @property A mode.
         */
        mode: null,

        /**
         * @property Search params.
         */
        searchParams: null,

        _timeout: null,

        /**
         * @property Inlide edit disabled.
         */
        inlineEditDisabled: false,

        /**
         * @property Field is disabled.
         */
        disabled: false,

        /**
         * @property Field is read-only.
         */
        readOnly: false,

        attributeList: null,

        /**
         * @property Attribute values before edit.
         */
        initialAttributes: null,

        VALIDATION_POPOVER_TIMEOUT: 3000,

        validateCallback: null,

        recordHelper: null,

        /**
         * Is the field required.
         *
         * @returns {Boolean}
         */
        isRequired: function () {
            return this.params.required;
        },

        /**
         * Get cell element. Works only after rendered.
         * @retrun {$}
         */
        getCellElement: function () {
            return this.$el.parent();
        },

        /**
         * Is in inline-edit mode.
         *
         * @returns {Boolean}
         */
        isInlineEditMode: function () {
            return !!this._isInlineEditMode;
        },

        /**
         * Set disabled.
         *
         * @param {Boolean} locked Won't be able to set back.
         */
        setDisabled: function (locked) {
            this.disabled = true;

            if (locked) {
                this.disabledLocked = true;
            }
        },

        /**
         * Set not-disabled.
         */
        setNotDisabled: function () {
            if (this.disabledLocked) {
                return;
            }

            this.disabled = false;
        },

        /**
         * Set required.
         */
        setRequired: function () {
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
        },

        /**
         * Set not required.
         */
        setNotRequired: function () {
            this.params.required = false;
            this.getCellElement().removeClass('has-error');

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
        },

        /**
         * Set read-only.
         *
         * @param {Boolean} locked Won't be able to set back.
         *
         * @return {Promise}
         */
        setReadOnly: function (locked) {
            if (this.readOnlyLocked) {
                return Promise.reject();
            }

            this.readOnly = true;

            if (locked) {
                this.readOnlyLocked = true;
            }

            if (this.isEditMode()) {
                if (this.isInlineEditMode()) {
                    return this.inlineEditClose();
                }

                return this.setDetailMode()
                    .then(() => this.reRender());
            }

            return Promise.resolve();
        },

        /**
         * Set not read only.
         */
        setNotReadOnly: function () {
            if (this.readOnlyLocked) {
                return;
            }

            this.readOnly = false;
        },

        /**
         * Get a label element. Works only after rendered.
         *
         * @return {$}
         */
        getLabelElement: function () {
            if (!this.$label || !this.$label.length) {
                this.$label = this.$el.parent().children('label');
            }

            return this.$label;
        },

        /**
         * Hide field and label. Works only after rendered.
         */
        hide: function () {
            this.$el.addClass('hidden');
            var $cell = this.getCellElement();

            $cell.children('label').addClass('hidden');
            $cell.addClass('hidden-cell');
        },

        /**
         * Show field and label. Works only after rendered.
         */
        show: function () {
            this.$el.removeClass('hidden');

            var $cell = this.getCellElement();

            $cell.children('label').removeClass('hidden');
            $cell.removeClass('hidden-cell');
        },

        /**
         * Get data for a template.
         *
         * @returns {Object}
         */
        data: function () {
            var data = {
                scope: this.model.name,
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
        },

        getValueForDisplay: function () {
            return this.model.get(this.name);
        },

        /**
         * Is in list, detail or list-link mode.
         *
         * @returns {Boolean}
         */
        isReadMode: function () {
            return this.mode === this.MODE_LIST ||
                this.mode === this.MODE_DETAIL ||
                this.mode === this.MODE_LIST_LINK;
        },

        /**
         * Is in list or list-link mode.
         *
         * @returns {Boolean}
         */
        isListMode: function () {
            return this.mode === this.MODE_LIST || this.mode === this.MODE_LIST_LINK;
        },

        /**
         * Is in detail node.
         *
         * @returns {Boolean}
         */
        isDetailMode: function () {
            return this.mode === this.MODE_DETAIL;
        },

        /**
         * Is in edit node.
         *
         * @returns {Boolean}
         */
        isEditMode: function () {
            return this.mode === this.MODE_EDIT;
        },

        /**
         * Is in search node.
         *
         * @returns {Boolean}
         */
        isSearchMode: function () {
            return this.mode === this.MODE_SEARCH;
        },

        /**
         * Set detail mode.
         *
         * @returns {Promise}
         */
        setDetailMode: function () {
            return this.setMode(this.MODE_DETAIL);
        },

        /**
         * Set edit mode.
         *
         * @returns {Promise}
         */
        setEditMode: function () {
            return this.setMode(this.MODE_EDIT);
        },

        /**
         * @internal
         * @returns {Promise}
         */
        setMode: function (mode) {
            let modeIsChanged = this.mode !== mode;
            let modeBefore = this.mode;

            this.mode = mode;

            let property = mode + 'Template';

            if (!(property in this)) {
                this[property] = 'fields/' + Espo.Utils.camelCaseToHyphen(this.type) + '/' + this.mode;
            }

            this.template = this[property];

            let contentProperty = mode + 'TemplateContent';

            if (!this._template) {
                this._templateCompiled = null;

                if (contentProperty in this) {
                    this.compiledTemplatesCache = this.compiledTemplatesCache || {};

                    this._templateCompiled =
                        this.compiledTemplatesCache[contentProperty] =
                            this.compiledTemplatesCache[contentProperty] ||
                            this._templator.compileTemplate(this[contentProperty]);
                }
            }

            if (modeIsChanged) {
                if (modeBefore) {
                    this.trigger('mode-changed');
                }

                if (this.isListMode()) {
                    return this.onListModeSet() || Promise.resolve();
                }

                if (this.isDetailMode()) {
                    return this.onDetailModeSet() || Promise.resolve();
                }

                if (this.isEditMode()) {
                    return this.onEditModeSet() || Promise.resolve();
                }
            }

            return Promise.resolve();
        },

        /**
         * For additional initialization for the detail mode.
         *
         * @returns {Promise}
         */
        onDetailModeSet: function () {
            return Promise.resolve();
        },

        /**
         * For additional initialization for the edit mode.
         *
         * @returns {Promise}
         */
        onEditModeSet: function () {
            return Promise.resolve();
        },

        /**
         * For additional initialization for the list mode.
         *
         * @returns {Promise}
         */
        onListModeSet: function () {
            return Promise.resolve();
        },

        /**
         * @internal
         */
        init: function () {
            if (this.events) {
                this.events = _.clone(this.events);
            } else {
                this.events = {};
            }

            this.validations = Espo.Utils.clone(this.validations);

            this.defs = this.options.defs || {};
            this.name = this.options.name || this.defs.name;
            this.params = this.options.params || this.defs.params || {};

            this.validateCallback = this.options.validateCallback;

            this.fieldType = this.model.getFieldParam(this.name, 'type') || this.type;

            this.recordHelper = this.options.recordHelper;

            this.getFieldManager().getParamList(this.type).forEach(d => {
                var name = d.name;

                if (!(name in this.params)) {
                    this.params[name] = this.model.getFieldParam(this.name, name);

                    if (typeof this.params[name] === 'undefined') {
                        this.params[name] = null;
                    }
                }
            });

            var additionaParamList = ['inlineEditDisabled'];

            additionaParamList.forEach((item) => {
                this.params[item] = this.model.getFieldParam(this.name, item) || null;
            });

            this.readOnly = this.readOnly || this.params.readOnly ||
                this.model.getFieldParam(this.name, 'readOnly') ||
                this.model.getFieldParam(this.name, 'clientReadOnly');

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

            this.mode = null;

            this.wait(
                this.setMode(mode)
            );

            if (this.isSearchMode()) {
                this.searchParams = _.clone(this.options.searchParams || {});
                this.searchData = {};
                this.setupSearch();
            }

            this.on('highlight', () => {
                var $cell = this.getCellElement();

                $cell.addClass('highlighted');
                $cell.addClass('transition');

                setTimeout(() => {
                    $cell.removeClass('highlighted');
                }, this.highlightPeriod || 3000);

                setTimeout(() => {
                    $cell.removeClass('transition');
                }, (this.highlightPeriod || 3000) + 2000);
            });

            this.on('invalid', () => {
                var $cell = this.getCellElement();

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
            });

            if ((this.isDetailMode() || this.isEditMode()) && this.tooltip) {
                this.initTooltip();
            }

            if (this.isDetailMode()) {
                if (!this.inlineEditDisabled) {
                    this.listenToOnce(this, 'after:render', this.initInlineEdit, this);
                }
            }

            if (!this.isSearchMode()) {
                this.attributeList = this.getAttributeList(); // for backward compatibility, to be removed

                this.listenTo(this.model, 'change', (model, options) => {
                    if (this.isRendered() || this.isBeingRendered()) {
                        if (options.ui) {
                            return;
                        }

                        var changed = false;

                        this.getAttributeList().forEach((attribute) => {
                            if (model.hasChanged(attribute)) {
                                changed = true;
                            }
                        });

                        if (changed && !options.skipReRender) {
                            this.reRender();
                        }

                        if (changed && options.highlight) {
                            this.trigger('highlight');
                        }
                    }
                });

                this.listenTo(this, 'change', () => {
                    var attributes = this.fetch();

                    this.model.set(attributes, {ui: true});
                });
            }
        },

        /**
         * @internal
         */
        initTooltip: function () {
            var $a;

            this.once('after:render', () => {
                $a = $(
                    '<a href="javascript:" class="text-muted field-info"><span class="fas fa-info-circle"></span></a>'
                );

                var $label = this.getLabelElement();

                $label.append(' ');

                this.getLabelElement().append($a);

                var tooltipText = this.options.tooltipText || this.tooltipText;

                if (!tooltipText && typeof this.tooltip === 'string') {
                    tooltipText = this.translate(this.tooltip, 'tooltips', this.model.name);
                }

                tooltipText = tooltipText || this.translate(this.name, 'tooltips', this.model.name) || '';
                tooltipText = this.getHelper().transfromMarkdownText(tooltipText, {linksInNewTab: true}).toString();

                var hidePopover = () => {
                    $('body').off('click.popover-' + this.id);

                    this.stopListening(this, 'mode-changed', hidePopover);

                    $a.popover('hide');
                };

                $a.popover({
                    placement: 'bottom',
                    container: 'body',
                    html: true,
                    content: tooltipText,
                }).on('shown.bs.popover', () => {
                    $('body').off('click.popover-' + this.id);

                    this.stopListening(this, 'mode-changed', hidePopover);

                    $('body').on('click.popover-' + this.id , (e) => {
                        if ($(e.target).closest('.popover-content').get(0)) {
                            return;
                        }

                        if ($.contains($a.get(0), e.target)) {
                            return;
                        }

                        hidePopover();
                    });

                    this.listenToOnce(this, 'mode-changed', hidePopover);
                });

                $a.on('click', function () {
                    $(this).popover('toggle');
                });
            });

            this.on('remove', () => {
                if ($a) {
                    $a.popover('destroy')
                }

                $('body').off('click.popover-' + this.id);
            });
        },

        /**
         * Show a required-field sign.
         */
        showRequiredSign: function () {
            var $label = this.getLabelElement();
            var $sign = $label.find('span.required-sign');

            if ($label.length && !$sign.length) {
                $text = $label.find('span.label-text');
                $('<span class="required-sign"> *</span>').insertAfter($text);
                $sign = $label.find('span.required-sign');
            }

            $sign.show();
        },

        /**
         * Hide a required-field sign.
         */
        hideRequiredSign: function () {
            var $label = this.getLabelElement();
            var $sign = $label.find('span.required-sign');

            $sign.hide();
        },

        /**
         * Get search-params data.
         *
         * @returns {object}
         */
        getSearchParamsData: function () {
            return this.searchParams.data || {};
        },

        /**
         * Get search values.
         *
         * @returns {object}
         */
        getSearchValues: function () {
            return this.getSearchParamsData().values || {};
        },

        /**
         * Get a current search type.
         *
         * @return {string}
         */
        getSearchType: function () {
            return this.getSearchParamsData().type || this.searchParams.type;
        },

        /**
         * Get the search type list.
         *
         * @returns {Array.<string>}
         */
        getSearchTypeList: function () {
            return this.searchTypeList;
        },

        /**
         * @internal
         */
        initInlineEdit: function () {
            var $cell = this.getCellElement();

            var $editLink = $(
                '<a href="javascript:" class="pull-right inline-edit-link hidden">' +
                '<span class="fas fa-pencil-alt fa-sm"></span></a>'
            );

            if ($cell.length === 0) {
                this.listenToOnce(this, 'after:render', this.initInlineEdit, this);

                return;
            }

            $cell.prepend($editLink);

            $editLink.on('click', () => {
                this.inlineEdit();
            });

            $cell
                .on('mouseenter', (e) => {
                    e.stopPropagation();

                    if (this.disabled || this.readOnly) {
                        return;
                    }

                    if (this.isDetailMode()) {
                        $editLink.removeClass('hidden');
                    }
                })
                .on('mouseleave', (e) => {
                    e.stopPropagation();

                    if (this.isDetailMode()) {
                        $editLink.addClass('hidden');
                    }
                });
        },

        /**
         * @internal
         */
        initElement: function () {
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
        },

        /**
         * Called after the view is rendered.
         */
        afterRender: function () {
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
        },

        /**
         * Called after the view is rendered in list or read mode.
         */
        afterRenderRead: function () {},

        /**
         * Called after the view is rendered in list mode.
         */
        afterRenderList: function () {},

        /**
         * Called after the view is rendered in detail mode.
         */
        afterRenderDetail: function () {},

        /**
         * Called after the view is rendered in edit mode.
         */
        afterRenderEdit: function () {},

        /**
         * Called after the view is rendered in search mode.
         */
        afterRenderSearch: function () {},

        /**
         * Initialization.
         */
        setup: function () {},

        /**
         * Initialization for search mode.
         */
        setupSearch: function () {},

        /**
         * Get list of model attributes that relate to the field.
         * Changing of any of attributes makes the field to re-render.
         *
         * @returns {Array.<string>}
         */
        getAttributeList: function () {
            return this.getFieldManager().getAttributes(this.fieldType, this.name);
        },

        /**
         * Invoke inline-edit saving.
         */
        inlineEditSave: function () {
            if (this.recordHelper) {
                this.recordHelper.trigger('inline-edit-save', this.name);

                return;
            }

            var data = this.fetch();

            var model = this.model;
            var prev = this.initialAttributes;

            model.set(data, {silent: true});
            data = model.attributes;

            var attrs = false;

            for (var attr in data) {
                if (_.isEqual(prev[attr], data[attr])) {
                    continue;
                }

                (attrs || (attrs = {}))[attr] = data[attr];
            }

            if (!attrs) {
                this.inlineEditClose();

                return;
            }

            let isInvalid = this.validateCallback ? this.validateCallback() : this.validate();

            if (isInvalid) {
                this.notify('Not valid', 'error');

                model.set(prev, {silent: true});

                return;
            }

            this.notify('Saving...');

            model
                .save(attrs, {patch: true})
                .then(() => {
                    this.trigger('after:inline-save');
                    this.trigger('after:save');

                    model.trigger('after:save');

                    this.notify('Saved', 'success');
                })
                .catch(() => {
                    this.notify('Error occurred', 'error');

                    model.set(prev, {silent: true});

                    this.render();
                });

            this.inlineEditClose(true);
        },

        /**
         * @internal
         */
        removeInlineEditLinks: function () {
            var $cell = this.getCellElement();

            $cell.find('.inline-save-link').remove();
            $cell.find('.inline-cancel-link').remove();
            $cell.find('.inline-edit-link').addClass('hidden');
        },

        /**
         * @internal
         */
        addInlineEditLinks: function () {
            var $cell = this.getCellElement();

            var $saveLink = $(
                '<a href="javascript:" class="pull-right inline-save-link">' + this.translate('Update') + '</a>'
            );

            var $cancelLink = $(
                '<a href="javascript:" class="pull-right inline-cancel-link">' + this.translate('Cancel') + '</a>'
            );

            $cell.prepend($saveLink);
            $cell.prepend($cancelLink);

            $cell.find('.inline-edit-link').addClass('hidden');

            $saveLink.click(() => {
                this.inlineEditSave();
            });

            $cancelLink.click(() => {
                this.inlineEditClose();
            });
        },

        /**
         * @internal
         */
        setIsInlineEditMode: function (value) {
            this._isInlineEditMode = value;
        },

        /**
         * Exist inline-edit mode.
         *
         * @return {Promise}
         */
        inlineEditClose: function (noReset) {
            this.trigger('inline-edit-off', {noReset: noReset});

            this._isInlineEditMode = false;

            if (!this.isEditMode()) {
                return;
            }

            let promise = this.setDetailMode()
                .then(() => this.reRender(true))
                .then(() => this.removeInlineEditLinks());

            if (!noReset) {
                this.model.set(this.initialAttributes);
            }

            this.trigger('after:inline-edit-off', {noReset: noReset});

            return promise;
        },

        /**
         * Switch to inline-edit mode.
         *
         * @return {Promise}
         */
        inlineEdit: function () {
            this.trigger('edit', this);

            this.initialAttributes = this.model.getClonedAttributes();

            this._isInlineEditMode = true;

            let promise = this.setEditMode()
                .then(() => this.reRender(true))
                .then(() => this.addInlineEditLinks());

            this.trigger('inline-edit-on');

            return promise;
        },

        suspendValidatinMessage: function (time) {
            this.validationMessageSuspended = true;

            setTimeout(() => this.validationMessageSuspended = false, time || 200);
        },

        /**
         * Show validation message.
         *
         * @param {string} message
         * @param {string|$|Element} target
         */
        showValidationMessage: function (message, target) {
            if (this.validationMessageSuspended) {
                return;
            }

            var $el;

            target = target || this.validationElementSelector || '.main-element';

            if (typeof target === 'string' || target instanceof String) {
                $el = this.$el.find(target);
            } else {
                $el = $(target);
            }

            if (!$el.length && this.$element) {
                $el = this.$element;
            }

            $el
                .popover({
                    placement: 'bottom',
                    container: 'body',
                    content: message,
                    trigger: 'manual'
                })
                .popover('show');

            var isDestroyed = false;

            $el.closest('.field').one('mousedown click', () => {
                if (isDestroyed) {
                    return;
                }

                $el.popover('destroy');
                isDestroyed = true;
            });

            this.once('render remove', () => {
                if (isDestroyed) {
                    return;
                }

                if ($el) {
                    $el.popover('destroy');
                    isDestroyed = true;
                }
            });

            if (this._timeout) {
                clearTimeout(this._timeout);
            }

            this._timeout = setTimeout(() => {
                if (isDestroyed) {
                    return;
                }

                $el.popover('destroy');
                isDestroyed = true;

            }, this.VALIDATION_POPOVER_TIMEOUT);
        },

        /**
         * Validate field values.
         *
         * @returns {Boolean} True if not valid.
         */
        validate: function () {
            for (var i in this.validations) {
                var method = 'validate' + Espo.Utils.upperCaseFirst(this.validations[i]);

                if (this[method].call(this)) {
                    this.trigger('invalid');

                    return true;
                }
            }

            return false;
        },

        /**
         * Get a label text.
         *
         * @returns {string}
         */
        getLabelText: function () {
            return this.options.labelText || this.translate(this.name, 'fields', this.model.name);
        },

        /**
         * Validate required.
         *
         * @returns {Boolean}
         */
        validateRequired: function () {
            if (this.isRequired()) {
                if (this.model.get(this.name) === '' || this.model.get(this.name) === null) {
                    var msg = this.translate('fieldIsRequired', 'messages').replace('{field}', this.getLabelText());

                    this.showValidationMessage(msg);

                    return true;
                }
            }
        },

        /**
         * Has a required marked.
         *
         * @returns {Boolean}
         */
        hasRequiredMarker: function () {
            return this.isRequired();
        },

        /**
         * Fetch values to the model.
         */
        fetchToModel: function () {
            this.model.set(this.fetch(), {silent: true});
        },

        /**
         * Fetch field values from DOM.
         *
         * @return {Object}
         */
        fetch: function () {
            var data = {};

            data[this.name] = this.$element.val();

            return data;
        },

        /**
         * Fetch search data from DOM.
         *
         * @return {Object}
         */
        fetchSearch: function () {
            var value = this.$element.val().toString().trim();

            if (value) {
                var data = {
                    type: 'equals',
                    value: value,
                };

                return data;
            }

            return false;
        },

        /**
         * Fetch a search type from DOM.
         *
         * @returns {string}
         */
        fetchSearchType: function () {
            return this.$el.find('select.search-type').val();
        },
    });
});
