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

Espo.define('Views.Fields.Base', 'View', function (Dep) {

    return Dep.extend({

        type: 'base',

        listTemplate: 'fields.base.list',

        listLinkTemplate: 'fields.base.list-link',

        detailTemplate: 'fields.base.detail',

        editTemplate: 'fields.base.edit',

        searchTemplate: 'fields.base.search',

        validations: ['required'],

        name: null,

        defs: null,

        params: null,

        mode: null,

        searchParams: null,

        _timeout: null,

        inlineEditDisabled: false,

        enabled: true,

        readOnly: false,

        attributeList: null,

        initialAttributes: null,

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

        setRequired: function () {
            this.params.required = true;
        },

        setNotRequired: function () {
            this.params.required = false;
            this.getCellElement().removeClass('has-error');
        },

        /**
         * Get label element. Works only after rendered.
         * {jQuery}
         */
        getLabelElement: function () {
            return this.$el.parent().children('label');
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
            return {
                scope: this.model.name,
                name: this.name,
                defs: this.defs,
                params: this.params,
                value: this.getValueForDisplay(),
                searchParams: this.searchParams,
            };
        },

        getValueForDisplay: function () {
            return this.model.get(this.name);
        },

        setMode: function (mode) {
            this.mode = mode;
            var property = mode + 'Template';
            if (!(property in this)) {
                this[property] = 'fields.' + Espo.Utils.camelCaseToHyphen(this.type) + '.' + this.mode;
            }
            this.template = this[property];
        },

        init: function () {
            if (!('defs' in this.options)) {
                throw new Error('Defs has not been defined for Field View.');
            }

            if (this.events) {
                this.events = _.clone(this.events);
            } else {
                this.events = {};
            }

            this.defs = this.options.defs;
            this.name = this.options.defs.name;
            this.params = this.options.defs.params || {};

            this.fieldType = this.model.getFieldParam(this.name, 'type') || this.type;

            this.getFieldManager().getParams(this.type).forEach(function (d) {
                var name = d.name;
                if (!(name in this.params)) {
                    this.params[name] = this.model.getFieldParam(this.name, name) || null;
                }
            }.bind(this));

            this.mode = this.options.mode || this.mode;

            if (this.mode == 'edit' || this.mode == 'detail') {
                var isReadOnlyField = this.model.getFieldParam(this.name, 'readOnly');
                if (isReadOnlyField) {
                    this.readOnly = true;
                } else {
                    if ('readOnly' in this.params) {
                        this.readOnly = this.params.readOnly;
                    } else {
                        this.readOnly = ('readOnly' in this.options) ? this.options.readOnly : this.readOnly;
                    }
                }
            }

            if (this.mode == 'edit' && this.readOnly) {
                this.mode = 'detail';
            }

            if ('inlineEditDisabled' in this.options) {
                this.inlineEditDisabled = this.options.inlineEditDisabled;
            } else {
                if ('inlineEditDisabled' in this.params) {
                    this.inlineEditDisabled = this.params.inlineEditDisabled;
                }
            }

            this.setMode(this.mode || 'detail');

            if (this.mode == 'search') {
                this.searchParams = _.clone(this.options.searchParams || {});
                this.setupSearch();
            }

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

            if (this.mode == 'edit' && this.isRequired()) {
                this.once('after:render', function () {
                    this.getLabelElement().append(' *');
                }, this);
            }

            if ((this.mode == 'detail' || this.mode == 'edit') && this.model.getFieldParam(this.name, 'tooltip')) {
                this.once('after:render', function () {
                    var $a = $('<a href="javascript:" class="text-muted"><span class="glyphicon glyphicon-info-sign"></span></a>');
                    var $label = this.getLabelElement();
                    $label.append(' ');
                    this.getLabelElement().append($a);
                    $a.popover({
                        placement: 'bottom',
                        container: 'body',
                        html: true,
                        content: this.translate(this.name, 'tooltips', this.model.name).replace(/\n/g, "<br />"),
                        trigger: 'click',
                    }).on('shown.bs.popover', function () {
                        $('body').one('click', function () {
                            $a.popover('hide');
                        });
                    });
                }, this);
            }

            if (this.mode == 'detail') {
                var self = this;

                if (!this.readOnly && !this.inlineEditDisabled) {

                    var initInlineEdit = function () {

                        var $cell = this.getCellElement();
                        var $editLink = $('<a href="javascript:" class="pull-right inline-edit-link hidden"><span class="glyphicon glyphicon-pencil"></span></a>');

                        // sometimes field is being re-rendered in this time so need to init once again
                        if ($cell.size() == 0) {
                            this.listenToOnce(this, 'after:render', initInlineEdit);
                            return;
                        }

                        $cell.prepend($editLink);

                        $editLink.on('click', function () {
                            this.inlineEdit();
                        }.bind(this));

                        $cell.on('mouseenter', function (e) {
                            e.stopPropagation();
                            if (!this.enabled || this.readOnly) {
                                return;
                            }
                            if (this.mode == 'detail') {
                                $editLink.removeClass('hidden');
                            }
                        }.bind(this)).on('mouseleave', function (e) {
                            e.stopPropagation();
                            if (this.mode == 'detail') {
                                $editLink.addClass('hidden');
                            }
                        }.bind(this));
                    }.bind(this);

                    this.listenToOnce(this, 'after:render', initInlineEdit);
                }
            }

            if (this.mode == 'edit' || this.mode == 'detail') {
                this.attributeList = this.getAttributeList();

                this.listenTo(this.model, 'change', function (model, options) {
                    if (this.isRendered() || this.isBeingRendered()) {
                        if (options.ui) {
                            return;
                        }

                        var changed = false;
                        this.attributeList.forEach(function (attribute) {
                            if (model.hasChanged(attribute)) {
                                changed = true;
                            }
                        });

                        if (changed) {
                            this.reRender();
                        }
                    }
                }.bind(this));

                this.listenTo(this, 'change', function () {
                    var attributes = this.fetch();
                    this.model.set(attributes, {ui: true});
                });
            }
        },

        initElement: function () {
            this.$element = this.$el.find('[name="' + this.name + '"]');
            if (this.mode == 'edit') {
                this.$element.on('change', function () {
                    this.trigger('change');
                }.bind(this));
            }
        },

        afterRender: function () {
            if (this.mode == 'edit' || this.mode == 'search') {
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

            var self = this;
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
            model.save(attrs, {
                success: function () {
                    self.trigger('after:save');
                    model.trigger('after:save');
                    self.notify('Saved', 'success');
                },
                error: function () {
                    self.notify('Error occured', 'error');
                    model.set(prev, {silent: true});
                    self.render()
                },
                patch: true
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
            var $saveLink = $('<a href="javascript:" class="pull-right inline-save-link">' + this.translate('Update') + '</a>');
            var $cancelLink = $('<a href="javascript:" class="pull-right inline-cancel-link">' + this.translate('Cancel') + '</a>').css('margin-left', '8px');
            $cell.prepend($saveLink);
            $cell.prepend($cancelLink);
            $cell.find('.inline-edit-link').addClass('hidden');
            $saveLink.click(function () {
                this.inlineEditSave();
            }.bind(this));
            $cancelLink.click(function () {
                this.inlineEditClose();
            }.bind(this));
        },

        inlineEditClose: function (dontReset) {
            if (this.mode != 'edit') {
                return;
            }

            this.setMode('detail');
            this.once('after:render', function () {
                this.removeInlineEditLinks();
            }, this);

            if (!dontReset) {
                this.model.set(this.initialAttributes);
            }

            this.render();
        },

        inlineEdit: function () {
            var self = this;

            this.trigger('edit', this);
            this.setMode('edit');

            this.initialAttributes = this.model.getClonedAttributes();

            this.once('after:render', function () {
                this.addInlineEditLinks();
            }, this);

            this.render();
        },

        showValidationMessage: function (message, selector) {
            selector = selector || '.main-element';

            var $el = this.$el.find(selector);
            $el.popover({
                placement: 'bottom',
                container: 'body',
                content: message,
                trigger: 'manual',
            }).popover('show');

            $el.closest('.field').one('mousedown click', function () {
                $el.popover('destroy');
            });

            this.once('render remove', function () {
                $el.popover('destroy');
            });

            if (this._timeout) {
                clearTimeout(this._timeout);
            }

            this._timeout = setTimeout(function () {
                $el.popover('destroy');
            }, 3000);
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

        validateRequired: function () {
            if (this.isRequired()) {
                if (this.model.get(this.name) === '') {
                    var msg = this.translate('fieldIsRequired', 'messages').replace('{field}', this.translate(this.name, 'fields', this.model.name));
                    this.showValidationMessage(msg);
                    return true;
                }
            }
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
            var value = this.$element.val();
            if (value) {
                var data = {
                    type: 'equals',
                    value: value
                };
                return data;
            }
            return false;
        },
    });
});

