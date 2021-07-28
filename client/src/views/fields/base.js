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

define('views/fields/base', 'view', function (Dep) {

    return Dep.extend({

        type: 'base',

        listTemplate: 'fields/base/list',

        listLinkTemplate: 'fields/base/list-link',

        detailTemplate: 'fields/base/detail',

        editTemplate: 'fields/base/edit',

        searchTemplate: 'fields/base/search',

        validations: ['required'],

        name: null,

        defs: null,

        params: null,

        mode: null,

        searchParams: null,

        _timeout: null,

        inlineEditDisabled: false,

        disabled: false,

        readOnly: false,

        attributeList: null,

        initialAttributes: null,

        VALIDATION_POPOVER_TIMEOUT: 3000,

        isRequired: function () {
            return this.params.required;
        },

        /**
         * Get cell element. Works only after rendered.
         * {jQuery}
         */
        getCellElement: function () {
            return this.$el.parent();
        },

        isInlineEditMode: function () {
            return !!this._isInlineEditMode;
        },

        setDisabled: function (locked) {
            this.disabled = true;
            if (locked) {
                this.disabledLocked = true;
            }
        },

        setNotDisabled: function () {
            if (this.disabledLocked) return;
            this.disabled = false;
        },

        setRequired: function () {
            this.params.required = true;

            if (this.mode === 'edit') {
                if (this.isRendered()) {
                    this.showRequiredSign();
                }
                else {
                    this.once('after:render', function () {
                        this.showRequiredSign();
                    }, this);
                }
            }
        },

        setNotRequired: function () {
            this.params.required = false;
            this.getCellElement().removeClass('has-error');

            if (this.mode === 'edit') {
                if (this.isRendered()) {
                    this.hideRequiredSign();
                }
                else {
                    this.once('after:render', function () {
                        this.hideRequiredSign();
                    }, this);
                }
            }
        },

        setReadOnly: function (locked) {
            if (this.readOnlyLocked) {
                return;
            }

            this.readOnly = true;

            if (locked) {
                this.readOnlyLocked = true;
            }

            if (this.mode == 'edit') {
                if (this.isInlineEditMode()) {
                    this.inlineEditClose();
                    return;
                }
                this.setMode('detail');
                if (this.isRendered()) {
                    this.reRender();
                }
            }
        },

        setNotReadOnly: function () {
            if (this.readOnlyLocked) {
                return;
            }

            this.readOnly = false;
        },

        /**
         * Get label element. Works only after rendered.
         * {jQuery}
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

        data: function () {
            var data = {
                scope: this.model.name,
                name: this.name,
                defs: this.defs,
                params: this.params,
                value: this.getValueForDisplay(),
            };

            if (this.mode === 'search') {
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

        isReadMode: function () {
            return this.mode === 'list' || this.mode === 'detail' || this.mode === 'listLink';
        },

        isListMode: function () {
            return this.mode === 'list' || this.mode === 'listLink';
        },

        isDetailMode: function () {
            return this.mode === 'detail';
        },

        isEditMode: function () {
            return this.mode === 'edit';
        },

        isSearchMode: function () {
            return this.mode === 'search';
        },


        setMode: function (mode) {
            var modeIsChanged = this.mode !== mode;

            this.mode = mode;

            var property = mode + 'Template';

            if (!(property in this)) {
                this[property] = 'fields/' + Espo.Utils.camelCaseToHyphen(this.type) + '/' + this.mode;
            }

            this.template = this[property];

            var contentProperty = mode + 'TemplateContent';

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
                this.trigger('mode-changed');
            }
        },

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

            this.fieldType = this.model.getFieldParam(this.name, 'type') || this.type;

            this.getFieldManager().getParamList(this.type).forEach(function (d) {
                var name = d.name;
                if (!(name in this.params)) {
                    this.params[name] = this.model.getFieldParam(this.name, name);
                    if (typeof this.params[name] === 'undefined') {
                        this.params[name] = null;
                    }
                }
            }, this);

            var additionaParamList = ['inlineEditDisabled'];

            additionaParamList.forEach(function (item) {
                this.params[item] = this.model.getFieldParam(this.name, item) || null;
            }, this);

            this.mode = this.options.mode || this.mode;

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

            if (this.mode === 'edit' && this.readOnly) {
                this.mode = 'detail';
            }

            this.setMode(this.mode || 'detail');

            if (this.mode === 'search') {
                this.searchParams = _.clone(this.options.searchParams || {});
                this.searchData = {};
                this.setupSearch();
            }

            this.on('highlight', function () {
                var $cell = this.getCellElement();
                $cell.addClass('highlighted');
                $cell.addClass('transition');

                setTimeout(function () {
                    $cell.removeClass('highlighted');
                }, this.highlightPeriod || 3000);

                setTimeout(function () {
                    $cell.removeClass('transition');
                }, (this.highlightPeriod || 3000) + 2000);

            }, this);

            this.on('invalid', function () {
                var $cell = this.getCellElement();

                $cell.addClass('has-error');

                this.$el.one('click', function () {
                    $cell.removeClass('has-error');
                });

                this.once('render', function () {
                    $cell.removeClass('has-error');
                });
            }, this);

            this.on('after:render', function () {
                if (this.mode === 'edit') {
                    if (this.hasRequiredMarker()) {
                        this.showRequiredSign();
                    } else {
                        this.hideRequiredSign();
                    }
                } else {
                    if (this.hasRequiredMarker()) {
                        this.hideRequiredSign();
                    }
                }

            }, this);

            if ((this.isDetailMode() || this.isEditMode()) && this.tooltip) {
                this.initTooltip();
            }

            if (this.mode === 'detail') {
                if (!this.inlineEditDisabled) {
                    this.listenToOnce(this, 'after:render', this.initInlineEdit, this);
                }
            }

            if (this.mode !== 'search') {
                this.attributeList = this.getAttributeList(); // for backward compatibility, to be removed

                this.listenTo(this.model, 'change', function (model, options) {
                    if (this.isRendered() || this.isBeingRendered()) {
                        if (options.ui) {
                            return;
                        }
                        var changed = false;
                        this.getAttributeList().forEach(function (attribute) {
                            if (model.hasChanged(attribute)) {
                                changed = true;
                            }
                        }, this);

                        if (changed && !options.skipReRender) {
                            this.reRender();
                        }

                        if (changed && options.highlight) {
                            this.trigger('highlight');
                        }
                    }
                }.bind(this));

                this.listenTo(this, 'change', function () {
                    var attributes = this.fetch();
                    this.model.set(attributes, {ui: true});
                });
            }
        },

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

        hideRequiredSign: function () {
            var $label = this.getLabelElement();
            var $sign = $label.find('span.required-sign');

            $sign.hide();
        },

        getSearchParamsData: function () {
            return this.searchParams.data || {};
        },

        getSearchValues: function () {
            return this.getSearchParamsData().values || {};
        },

        getSearchType: function () {
            return this.getSearchParamsData().type || this.searchParams.type;
        },

        getSearchTypeList: function () {
            return this.searchTypeList;
        },

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

            $cell.on('mouseenter', (e) => {
                e.stopPropagation();

                if (this.disabled || this.readOnly) {
                    return;
                }

                if (this.mode === 'detail') {
                    $editLink.removeClass('hidden');
                }
            }).on('mouseleave', (e) => {
                e.stopPropagation();

                if (this.mode === 'detail') {
                    $editLink.addClass('hidden');
                }
            });
        },

        initElement: function () {
            this.$element = this.$el.find('[data-name="' + this.name + '"]');

            if (!this.$element.length) {
                this.$element = this.$el.find('[name="' + this.name + '"]');
            }

            if (!this.$element.length) {
                this.$element = this.$el.find('.main-element');
            }

            if (this.mode === 'edit') {
                this.$element.on('change', () => {
                    this.trigger('change');
                });
            }
        },

        afterRender: function () {
            if (this.mode === 'edit' || this.mode === 'search') {
                this.initElement();
            }
        },

        setup: function () {},

        setupSearch: function () {},

        getAttributeList: function () {
            return this.getFieldManager().getAttributes(this.fieldType, this.name);
        },

        inlineEditSave: function () {
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

                (attrs || (attrs = {}))[attr] =    data[attr];
            }

            if (!attrs) {
                this.inlineEditClose();

                return;
            }

            if (this.validate()) {
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

        removeInlineEditLinks: function () {
            var $cell = this.getCellElement();

            $cell.find('.inline-save-link').remove();
            $cell.find('.inline-cancel-link').remove();
            $cell.find('.inline-edit-link').addClass('hidden');
        },

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

        setIsInlineEditMode: function (value) {
            this._isInlineEditMode = value;
        },

        inlineEditClose: function (dontReset) {
            this.trigger('inline-edit-off');

            this._isInlineEditMode = false;

            if (this.mode !== 'edit') {
                return;
            }

            this.setMode('detail');

            this.once('after:render', function () {
                this.removeInlineEditLinks();
            }, this);

            if (!dontReset) {
                this.model.set(this.initialAttributes);
            }

            this.reRender(true);

            this.trigger('after:inline-edit-off');
        },

        inlineEdit: function () {
            var self = this;

            this.trigger('edit', this);
            this.setMode('edit');

            this.initialAttributes = this.model.getClonedAttributes();

            this.once('after:render', () => {
                this.addInlineEditLinks();
            });

            this._isInlineEditMode = true;

            this.reRender(true);
            this.trigger('inline-edit-on');
        },

        suspendValidatinMessage: function (time) {
            this.validationMessageSuspended = true;

            setTimeout(() => this.validationMessageSuspended = false, time || 200);
        },

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

            $el.popover({
                placement: 'bottom',
                container: 'body',
                content: message,
                trigger: 'manual'
            }).popover('show');

            var isDestroyed = false;

            $el.closest('.field').one('mousedown click', function () {
                if (isDestroyed) {
                    return;
                }

                $el.popover('destroy');
                isDestroyed = true;
            });

            this.once('render remove', function () {
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

            this._timeout = setTimeout(function () {
                if (isDestroyed) {
                    return;
                }

                $el.popover('destroy');
                isDestroyed = true;

            }, this.VALIDATION_POPOVER_TIMEOUT);
        },

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

        getLabelText: function () {
            return this.options.labelText || this.translate(this.name, 'fields', this.model.name);
        },

        validateRequired: function () {
            if (this.isRequired()) {
                if (this.model.get(this.name) === '' || this.model.get(this.name) === null) {
                    var msg = this.translate('fieldIsRequired', 'messages').replace('{field}', this.getLabelText());

                    this.showValidationMessage(msg);

                    return true;
                }
            }
        },

        hasRequiredMarker: function () {
            return this.isRequired();
        },

        fetchToModel: function () {
            this.model.set(this.fetch(), {silent: true});
        },

        fetch: function () {
            var data = {};

            data[this.name] = this.$element.val();

            return data;
        },

        fetchSearch: function () {
            var value = this.$element.val().toString().trim();

            if (value) {
                var data = {
                    type: 'equals',
                    value: value
                };

                return data;
            }

            return false;
        },

        fetchSearchType: function () {
            return this.$el.find('select.search-type').val();
        },

    });
});
