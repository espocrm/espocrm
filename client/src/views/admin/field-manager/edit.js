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
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

Espo.define('views/admin/field-manager/edit', ['view', 'model'], function (Dep, Model) {

    return Dep.extend({

        template: 'admin/field-manager/edit',

        entityTypeWithTranslatedOptionsList: ['enum', 'multiEnum', 'array', 'phone'],

        paramWithTooltipList: ['audited', 'required', 'default', 'min', 'max', 'maxLength', 'after', 'before'],

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
                hasDynamicLogicPanel: this.hasDynamicLogicPanel
            };
        },

        events: {
            'click button[data-action="close"]': function () {
                this.getRouter().navigate('#Admin/fieldManager/scope=' + this.scope, {trigger: true});
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
                    name: {required: true},
                    label: {required: true},
                    tooltipText: {}
                }
            };

            if (!this.isNew) {
                this.model.id = this.field;
                this.model.scope = this.scope;
                this.model.set('name', this.field);
                this.model.set('label', this.getLanguage().translate(this.field, 'fields', this.scope));

                if (this.getMetadata().get(['entityDefs', this.scope, 'fields', this.field, 'tooltip'])) {
                    this.model.set('tooltipText', this.getLanguage().translate(this.field, 'tooltips', this.scope));
                }
            } else {
                this.model.set('type', this.type);
            }

            this.getModelFactory().create(this.scope, function (model) {
                if (!this.isNew) {
                    this.type = model.getFieldType(this.field);
                }

                Promise.race([
                    new Promise(function (resolve) {
                        if (this.isNew) {
                            resolve();
                        };
                    }.bind(this)),
                    new Promise(function (resolve) {
                        if (this.isNew) return;
                        this.ajaxGetRequest('Admin/fieldManager/' + this.scope + '/' + this.field).then(function (data) {
                            this.defs = data;
                            resolve();
                        }.bind(this));
                    }.bind(this))
                ]).then(function () {
                    this.paramList = [];
                    var paramList = this.getFieldManager().getParams(this.type) || [];
                    paramList.forEach(function (o) {
                        var item = o.name;
                        var disableParamName = 'customization' + Espo.Utils.upperCaseFirst(item) + 'Disabled';
                        if (this.getMetadata().get('entityDefs.' + this.scope + '.fields.' + this.field + '.' + disableParamName)) {
                            return;
                        }
                        this.paramList.push(o);
                    }, this);

                    this.paramList.forEach(function (o) {
                        this.model.defs.fields[o.name] = o;
                    }, this);

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

                    this.createFieldView('text', 'tooltipText', null, {
                        trim: true,
                        rows: 1
                    });

                    this.paramList.forEach(function (o) {
                        if (o.hidden) {
                            return;
                        }
                        var options = {};
                        if (o.tooltip ||  ~this.paramWithTooltipList.indexOf(o.name)) {
                            options.tooltip = true;
                            options.tooltipText = this.translate(o.name, 'tooltips', 'FieldManager');
                        }
                        this.createFieldView(o.type, o.name, null, o, options);
                    }, this);

                    this.hasDynamicLogicPanel = false;
                    if (
                        !this.getMetadata().get(['entityDefs', this.scope, 'fields', this.field, 'disabled'])
                        &&
                        !this.getMetadata().get(['entityDefs', this.scope, 'fields', this.field, 'dynamicLogicDisabled'])
                        &&
                        !this.getMetadata().get(['entityDefs', this.scope, 'fields', this.field, 'layoutDetailDisabled'])
                    ) {
                        if (!this.getMetadata().get(['entityDefs', this.scope, 'fields', this.field, 'dynamicLogicVisibleDisabled'])) {
                            this.model.set('dynamicLogicVisible', this.getMetadata().get(['clientDefs', this.scope, 'dynamicLogic', 'fields', this.field, 'visible']));
                            this.createFieldView(null, 'dynamicLogicVisible', null, {
                                view: 'views/admin/field-manager/fields/dynamic-logic-conditions',
                                scope: this.scope
                            });
                            this.hasDynamicLogicPanel = true;
                        }
                        if (
                            !this.getMetadata().get(['entityDefs', this.scope, 'fields', this.field, 'dynamicLogicRequiredDisabled'])
                            &&
                            !this.getMetadata().get(['entityDefs', this.scope, 'fields', this.field, 'readOnly'])&&
                            !this.getMetadata().get(['fields', this.type, 'readOnly'])
                        ) {
                            this.model.set('dynamicLogicRequired', this.getMetadata().get(['clientDefs', this.scope, 'dynamicLogic', 'fields', this.field, 'required']));
                            this.createFieldView(null, 'dynamicLogicRequired', null, {
                                view: 'views/admin/field-manager/fields/dynamic-logic-conditions',
                                scope: this.scope
                            });
                            this.hasDynamicLogicPanel = true;
                        }
                        if (
                            !this.getMetadata().get(['entityDefs', this.scope, 'fields', this.field, 'dynamicLogicReadOnlyDisabled'])
                            &&
                            !this.getMetadata().get(['entityDefs', this.scope, 'fields', this.field, 'readOnly'])
                            &&
                            !this.getMetadata().get(['fields', this.type, 'readOnly'])
                        ) {
                            this.model.set('dynamicLogicReadOnly', this.getMetadata().get(['clientDefs', this.scope, 'dynamicLogic', 'fields', this.field, 'readOnly']));
                            this.createFieldView(null, 'dynamicLogicReadOnly', null, {
                                view: 'views/admin/field-manager/fields/dynamic-logic-conditions',
                                scope: this.scope
                            });
                            this.hasDynamicLogicPanel = true;
                        }

                        if (
                            ~['enum', 'array', 'multiEnum'].indexOf(this.type)
                            &&
                            !this.getMetadata().get(['entityDefs', this.scope, 'fields', this.field, 'dynamicLogicOptionsDisabled'])
                            &&
                            !this.getMetadata().get(['entityDefs', this.scope, 'fields', this.field, 'readOnly'])
                        ) {
                            this.model.set('dynamicLogicOptions', this.getMetadata().get(['clientDefs', this.scope, 'dynamicLogic', 'options', this.field]));
                            this.createFieldView(null, 'dynamicLogicOptions', null, {
                                view: 'views/admin/field-manager/fields/dynamic-logic-options',
                                scope: this.scope
                            });
                            this.hasDynamicLogicPanel = true;
                        };
                    }

                    callback();

                }.bind(this));
            }.bind(this));
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
            this.setupFieldData(function () {
                this.wait(false);
            }.bind(this));
        },

        afterRender: function () {
            this.getView('name').on('change', function (m) {
                var name = this.model.get('name');
                this.model.set('label', name);
                if (name) {
                    name = name.replace('-', '').replace(/[^\w\s]/gi, '').replace(/ (.)/g, function(match, g) {
                        return g.toUpperCase();
                    }).replace(' ', '');
                    if (name.length) {
                         name = name.charAt(0).toLowerCase() + name.slice(1);
                    }
                }
                this.model.set('name', name);
            }, this);
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

        save: function () {
            this.fieldList.forEach(function (field) {
                var view = this.getView(field);
                if (!view.readOnly) {
                    view.fetchToModel();
                }
            }, this);

            var notValid = false;
            this.fieldList.forEach(function (field) {
                notValid = this.getView(field).validate() || notValid;
            }, this);

            if (notValid) {
                this.notify('Not valid', 'error');
                return;
            }

            if (this.model.get('tooltipText') && this.model.get('tooltipText') !== '') {
                this.model.set('tooltip', true);
            } else {
                this.model.set('tooltip', false);
            }

            this.listenToOnce(this.model, 'sync', function () {
                Espo.Ui.notify(false);

                this.getMetadata().load(function () {
                    this.getMetadata().storeToCache();
                    this.trigger('after:save');
                }.bind(this), true);

                this.updateLanguage();

            }.bind(this));

            this.notify('Saving...');
            this.model.save();
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

                if (this.getMetadata().get(['fields', this.model.get('type'), 'translatedOptions']) && this.model.get('translatedOptions')) {
                    langData[this.scope].options = langData[this.scope].options || {};
                    langData[this.scope]['options'][this.model.get('name')] = this.model.get('translatedOptions') || {};
                }
            }
        },

        resetToDefault: function () {
            if (!confirm(this.translate('confirmation', 'messages'))) return;

            Espo.Ui.notify(this.translate('pleaseWait', 'messages'));

            this.ajaxPostRequest('FieldManager/action/resetToDefault', {
                scope: this.scope,
                name: this.field
            }).then(function () {
                Promise.all([
                    new Promise(function (resolve) {
                        this.getMetadata().load(function () {
                            this.getMetadata().storeToCache();
                            resolve();
                        }.bind(this), true);
                    }.bind(this)),
                    new Promise(function (resolve) {
                        this.getLanguage().load(function () {
                            resolve();
                        }.bind(this), true);
                    }.bind(this))
                ]).then(function () {
                    this.setupFieldData(function () {
                        this.notify('Done', 'success');
                        this.reRender();
                    }.bind(this));
                }.bind(this));

            }.bind(this));
        }

    });

});
