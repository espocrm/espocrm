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

define('views/admin/field-manager/edit', ['view', 'model'], function (Dep, Model) {

    return Dep.extend({

        template: 'admin/field-manager/edit',

        entityTypeWithTranslatedOptionsList: ['enum', 'multiEnum', 'array', 'phone'],

        paramWithTooltipList: [
            'audited',
            'required',
            'default',
            'min',
            'max',
            'maxLength',
            'after',
            'before',
            'readOnly',
        ],

        data: function () {
            return {
                scope: this.scope,
                field: this.field,
                defs: this.defs,
                paramList: this.paramList,
                type: this.type,
                fieldList: this.fieldList,
                isCustom: this.defs.isCustom,
                isNew: this.isNew,
                hasDynamicLogicPanel: this.hasDynamicLogicPanel,
                hasResetToDefault: !this.defs.isCustom && !this.entityTypeIsCustom && !this.isNew,
            };
        },

        events: {
            'click button[data-action="close"]': function () {
                this.actionClose();
            },
            'click button[data-action="save"]': function () {
                this.save();
            },
            'click button[data-action="resetToDefault"]': function () {
                this.resetToDefault();
            }
        },

        setupFieldData: function (callback) {
            this.defs = {};
            this.fieldList = [];

            this.model = new Model();
            this.model.name = 'Admin';
            this.model.urlRoot = 'Admin/fieldManager/' + this.scope;

            this.model.defs = {
                fields: {
                    name: {required: true, maxLength: 100},
                    label: {required: true},
                    tooltipText: {},
                }
            };

            this.entityTypeIsCustom = !!this.getMetadata().get(['scopes', this.scope, 'isCustom']);

            if (!this.isNew) {
                this.model.id = this.field;
                this.model.scope = this.scope;

                this.model.set('name', this.field);
                this.model.set(
                    'label',
                    this.getLanguage().translate(this.field, 'fields', this.scope)
                );

                if (this.getMetadata().get(['entityDefs', this.scope, 'fields', this.field, 'tooltip'])) {
                    this.model.set(
                        'tooltipText',
                        this.getLanguage().translate(this.field, 'tooltips', this.scope)
                    );
                }
            }
            else {
                this.model.scope = this.scope;
                this.model.set('type', this.type);
            }

            this.listenTo(this.model, 'change:readOnly', () => {
                this.readOnlyControl();
            });

            var hasRequired = false;

            this.getModelFactory().create(this.scope, (model) => {
                if (!this.isNew) {
                    this.type = model.getFieldType(this.field);
                }

                if (
                    this.getMetadata().get(['scopes', this.scope, 'hasPersonalData']) &&
                    this.getMetadata().get(['fields', this.type, 'personalData'])
                ) {
                    this.hasPersonalData = true;
                }

                this.hasInlineEditDisabled = this.type !== 'foreign';

                new Promise((resolve) => {
                    if (this.isNew) {
                        resolve();

                        return;
                    }

                    this.ajaxGetRequest('Admin/fieldManager/' + this.scope + '/' + this.field)
                        .then((data) => {
                            this.defs = data;

                            resolve();
                        });
                })
                .then(() => {
                    this.paramList = [];

                    var paramList = Espo.Utils.clone(this.getFieldManager().getParams(this.type) || []);

                    if (!this.isNew) {
                        var fieldManagerAdditionalParamList =
                            this.getMetadata()
                                .get([
                                    'entityDefs', this.scope, 'fields',
                                    this.field, 'fieldManagerAdditionalParamList'
                                ]) || [];

                        fieldManagerAdditionalParamList.forEach((item) =>  {
                            paramList.push(item);
                        });
                    }

                    paramList.forEach((o) => {
                        var item = o.name;

                        if (item === 'required') {
                            hasRequired = true;
                        }

                        var disableParamName = 'customization' + Espo.Utils.upperCaseFirst(item) + 'Disabled';

                        var isDisabled =
                            this.getMetadata()
                                .get('entityDefs.' + this.scope + '.fields.' + this.field + '.' + disableParamName);

                        if (isDisabled) {
                            return;
                        }

                        var viewParamName = 'customization' + Espo.Utils.upperCaseFirst(item) + 'View';

                        var view = this.getMetadata()
                            .get(['entityDefs', this.scope, 'fields', this.field, viewParamName]);

                        if (view) {
                            o.view = view;
                        }

                        this.paramList.push(o);
                    });

                    if (this.hasPersonalData) {
                        this.paramList.push({
                            name: 'isPersonalData',
                            type: 'bool'
                        });
                    }

                    if (this.hasInlineEditDisabled) {
                        this.paramList.push({
                            name: 'inlineEditDisabled',
                            type: 'bool'
                        });
                    }

                    this.paramList.forEach((o) => {
                        this.model.defs.fields[o.name] = o;
                    });

                    this.model.set(this.defs);

                    if (this.isNew) {
                        this.model.populateDefaults();
                    }

                    this.createFieldView('varchar', 'name', !this.isNew, {
                        trim: true
                    });

                    this.createFieldView('varchar', 'label', null, {
                        trim: true
                    });

                    if (this.hasPersonalData) {
                        this.createFieldView('bool', 'isPersonalData', null, {});
                    }

                    if (this.hasInlineEditDisabled) {
                        this.createFieldView('bool', 'inlineEditDisabled', null, {});
                    }

                    this.createFieldView('text', 'tooltipText', null, {
                        trim: true,
                        rowsMin: 1,
                    });

                    this.hasDynamicLogicPanel = false;

                    this.setupDynamicLogicFields(hasRequired);

                    this.model.fetchedAttributes = this.model.getClonedAttributes();

                    this.paramList.forEach((o) => {
                        if (o.hidden) {
                            return;
                        }

                        var options = {};

                        if (o.tooltip ||  ~this.paramWithTooltipList.indexOf(o.name)) {
                            options.tooltip = true;

                            var tooltip = o.name;

                            if (typeof o.tooltip === 'string') {
                                tooltip = o.tooltip;
                            }

                            options.tooltipText = this.translate(tooltip, 'tooltips', 'FieldManager');
                        }

                        this.createFieldView(o.type, o.name, null, o, options);
                    });

                    callback();
                });
            });

            this.listenTo(this.model, 'change', (m, o) => {
                if (!o.ui) {
                    return;
                }

                this.setIsChanged();
            });
        },

        setup: function () {
            this.scope = this.options.scope;
            this.field = this.options.field;
            this.type = this.options.type;

            this.isNew = false;

            if (!this.field) {
                this.isNew = true;
            }

            this.wait(true);

            this.setupFieldData(() => {
                this.wait(false);
            });
        },

        setupDynamicLogicFields: function (hasRequired) {
            if (
                this.getMetadata()
                    .get(['entityDefs', this.scope, 'fields', this.field, 'disabled']) ||
                this.getMetadata()
                    .get(['entityDefs', this.scope, 'fields', this.field, 'dynamicLogicDisabled']) ||
                this.getMetadata()
                    .get(['entityDefs', this.scope, 'fields', this.field, 'layoutDetailDisabled'])
            ) {
                return;
            }

            var dynamicLogicVisibleDisabled = this.getMetadata()
                    .get(['entityDefs', this.scope, 'fields', this.field, 'dynamicLogicVisibleDisabled']);

            if (!dynamicLogicVisibleDisabled) {
                var isVisible = this.getMetadata()
                    .get(['clientDefs', this.scope, 'dynamicLogic', 'fields', this.field, 'visible']);

                this.model.set(
                    'dynamicLogicVisible',
                    isVisible
                );

                this.createFieldView(null, 'dynamicLogicVisible', null, {
                    view: 'views/admin/field-manager/fields/dynamic-logic-conditions',
                    scope: this.scope
                });

                this.hasDynamicLogicPanel = true;
            }

            var readOnly = this.getMetadata().get(['fields', this.type, 'readOnly']);

            var dynamicLogicRequiredDisabled = this.getMetadata()
                .get(['entityDefs', this.scope, 'fields', this.field, 'dynamicLogicRequiredDisabled']);

            if (!dynamicLogicRequiredDisabled && !readOnly && hasRequired) {
                var dynamicLogicRequired = this.getMetadata()
                    .get(['clientDefs', this.scope, 'dynamicLogic', 'fields', this.field, 'required']);

                this.model.set('dynamicLogicRequired', dynamicLogicRequired);

                this.createFieldView(null, 'dynamicLogicRequired', null, {
                    view: 'views/admin/field-manager/fields/dynamic-logic-conditions',
                    scope: this.scope,
                });

                this.hasDynamicLogicPanel = true;
            }

            var dynamicLogicReadOnlyDisabled = this.getMetadata()
                .get(['entityDefs', this.scope, 'fields', this.field, 'dynamicLogicReadOnlyDisabled']);

            if (!dynamicLogicReadOnlyDisabled && !readOnly) {
                var dynamicLogicReadOnly = this.getMetadata()
                    .get(['clientDefs', this.scope, 'dynamicLogic', 'fields', this.field, 'readOnly']);

                this.model.set('dynamicLogicReadOnly', dynamicLogicReadOnly);

                this.createFieldView(null, 'dynamicLogicReadOnly', null, {
                    view: 'views/admin/field-manager/fields/dynamic-logic-conditions',
                    scope: this.scope,
                });

                this.hasDynamicLogicPanel = true;
            }

            var typeDynamicLogicOptions = this.getMetadata()
                .get(['fields', this.type, 'dynamicLogicOptions']);

            var dynamicLogicOptionsDisabled = this.getMetadata()
                .get(['entityDefs', this.scope, 'fields', this.field, 'dynamicLogicOptionsDisabled']);


            if (typeDynamicLogicOptions && !dynamicLogicOptionsDisabled) {
                var dynamicLogicOptions =  this.getMetadata()
                    .get(['clientDefs', this.scope, 'dynamicLogic', 'options', this.field]);

                this.model.set('dynamicLogicOptions', dynamicLogicOptions);

                this.createFieldView(null, 'dynamicLogicOptions', null, {
                    view: 'views/admin/field-manager/fields/dynamic-logic-options',
                    scope: this.scope,
                });

                this.hasDynamicLogicPanel = true;
            }

            var dynamicLogicInvalidDisabled = this.getMetadata()
                    .get(['entityDefs', this.scope, 'fields', this.field, 'dynamicLogicInvalidDisabled']);

            if (!dynamicLogicInvalidDisabled && !readOnly) {
                var dynamicLogicInvalid = this.getMetadata()
                    .get(['clientDefs', this.scope, 'dynamicLogic', 'fields', this.field, 'invalid']);

                this.model.set('dynamicLogicInvalid', dynamicLogicInvalid);

                this.createFieldView(null, 'dynamicLogicInvalid', null, {
                    view: 'views/admin/field-manager/fields/dynamic-logic-conditions',
                    scope: this.scope,
                });

                this.hasDynamicLogicPanel = true;
            }
        },

        afterRender: function () {
            this.getView('name').on('change', (m) => {
                var name = this.model.get('name');

                var label = name;

                if (label.length) {
                     label = label.charAt(0).toUpperCase() + label.slice(1);
                }

                this.model.set('label', label);

                if (name) {
                    name = name
                        .replace(/-/g, '')
                        .replace(/_/g, '')
                        .replace(/[^\w\s]/gi, '')
                        .replace(/ (.)/g, (match, g) => {
                            return g.toUpperCase();
                        })
                        .replace(' ', '');

                    if (name.length) {
                         name = name.charAt(0).toLowerCase() + name.slice(1);
                    }
                }

                this.model.set('name', name);
            });
        },

        readOnlyControl: function () {
            if (this.model.get('readOnly')) {
                this.hideField('dynamicLogicReadOnly');
                this.hideField('dynamicLogicRequired');
                this.hideField('dynamicLogicOptions');
                this.hideField('dynamicLogicInvalid');
            }
            else {
                this.showField('dynamicLogicReadOnly');
                this.showField('dynamicLogicRequired');
                this.showField('dynamicLogicOptions');
                this.showField('dynamicLogicInvalid');
            }
        },

        hideField: function (name) {
            var f = () => {
                var view = this.getView(name);

                if (view) {
                    this.$el.find('.cell[data-name="'+name+'"]').addClass('hidden');

                    view.setDisabled();
                }
            };

            if (this.isRendered()) {
                f();
            }
            else {
                this.once('after:render', f);
            }
        },

        showField: function (name) {
            var f = () => {
                var view = this.getView(name);

                if (view) {
                    this.$el.find('.cell[data-name="'+name+'"]').removeClass('hidden');

                    view.setNotDisabled();
                }
            };

            if (this.isRendered()) {
                f();
            }
            else {
                this.once('after:render', f);
            }
        },

        createFieldView: function (type, name, readOnly, params, options, callback) {
            var viewName = (params || {}).view || this.getFieldManager().getViewName(type);

            var o = {
                model: this.model,
                el: this.options.el + ' .field[data-name="' + name + '"]',
                defs: {
                    name: name,
                    params: params
                },
                mode: readOnly ? 'detail' : 'edit',
                readOnly: readOnly,
                scope: this.scope,
                field: this.field,
            };

            _.extend(o, options || {});

            this.createView(name, viewName, o, callback);

            this.fieldList.push(name);
        },

        disableButtons: function () {
            this.$el.find('[data-action="save"]').attr('disabled', 'disabled').addClass('disabled');
            this.$el.find('[data-action="resetToDefault"]').attr('disabled', 'disabled').addClass('disabled');
        },

        enableButtons: function () {
            this.$el.find('[data-action="save"]').removeAttr('disabled').removeClass('disabled');
            this.$el.find('[data-action="resetToDefault"]').removeAttr('disabled').removeClass('disabled');
        },

        save: function () {
            this.disableButtons();

            this.fieldList.forEach((field) => {
                var view = this.getView(field);

                if (!view.readOnly) {
                    view.fetchToModel();
                }
            });

            var notValid = false;

            this.fieldList.forEach((field) => {
                notValid = this.getView(field).validate() || notValid;
            });

            if (notValid) {
                this.notify('Not valid', 'error');

                this.enableButtons();

                return;
            }

            if (this.model.get('tooltipText') && this.model.get('tooltipText') !== '') {
                this.model.set('tooltip', true);
            }
            else {
                this.model.set('tooltip', false);
            }

            this.listenToOnce(this.model, 'sync', () => {
                Espo.Ui.notify(false);

                this.setIsNotChanged();

                this.enableButtons();

                this.updateLanguage();

                Promise.all([
                    this.getMetadata().loadSkipCache(),
                    this.getLanguage().loadSkipCache(),
                ])
                .then(() => this.trigger('after:save'));

                this.model.fetchedAttributes = this.model.getClonedAttributes();

                this.broadcastUpdate();
            });

            this.notify('Saving...');

            if (this.isNew) {
                this.model
                    .save()
                    .catch(() => this.enableButtons());

                return;
            }

            var attributes = this.model.getClonedAttributes();

            if (this.model.fetchedAttributes.label === attributes.label) {
                delete attributes.label;
            }

            if (
                this.model.fetchedAttributes.tooltipText === attributes.tooltipText ||
                !this.model.fetchedAttributes.tooltipText && !attributes.tooltipText
            ) {
                delete attributes.tooltipText;
            }

            if ('translatedOptions' in attributes) {
                if (_.isEqual(this.model.fetchedAttributes.translatedOptions, attributes.translatedOptions)) {
                    delete attributes.translatedOptions;
                }
            }

            this.model
                .save(attributes, {patch: true})
                .catch(() => this.enableButtons());
        },

        updateLanguage: function () {
            var langData = this.getLanguage().data;

            if (this.scope in langData) {
                if (!('fields' in langData[this.scope])) {
                    langData[this.scope]['fields'] = {};
                }

                langData[this.scope]['fields'][this.model.get('name')] = this.model.get('label');

                if (!('tooltips' in langData[this.scope])) {
                    langData[this.scope]['tooltips'] = {};
                }

                langData[this.scope]['tooltips'][this.model.get('name')] = this.model.get('tooltipText');

                if (
                    this.getMetadata().get(['fields', this.model.get('type'), 'translatedOptions']) &&
                    this.model.get('translatedOptions')
                ) {
                    langData[this.scope].options = langData[this.scope].options || {};

                    langData[this.scope]['options'][this.model.get('name')] =
                        this.model.get('translatedOptions') || {};
                }
            }
        },

        resetToDefault: function () {
            this.confirm(this.translate('confirmation', 'messages'), () => {
                Espo.Ui.notify(this.translate('pleaseWait', 'messages'));

                this.ajaxPostRequest('FieldManager/action/resetToDefault', {
                    scope: this.scope,
                    name: this.field,
                }).then(() => {
                    Promise
                    .all([
                        this.getMetadata().loadSkipCache(),
                        this.getLanguage().loadSkipCache(),
                    ])
                    .then(() => {
                        this.setIsNotChanged();

                        this.setupFieldData(() => {
                            this.notify('Done', 'success');

                            this.reRender();

                            this.broadcastUpdate();
                        });
                    });
                });
            });
        },

        broadcastUpdate: function () {
            this.getHelper().broadcastChannel.postMessage('update:metadata');
            this.getHelper().broadcastChannel.postMessage('update:language');
            this.getHelper().broadcastChannel.postMessage('update:settings');
        },

        actionClose: function () {
            this.setIsNotChanged();

            this.getRouter().navigate('#Admin/fieldManager/scope=' + this.scope, {trigger: true});
        },

        setConfirmLeaveOut: function (value) {
            this.getRouter().confirmLeaveOut = value;
        },

        setIsChanged: function () {
            this.isChanged = true;
            this.setConfirmLeaveOut(true);
        },

        setIsNotChanged: function () {
            this.isChanged = false;
            this.setConfirmLeaveOut(false);
        },
    });
});
