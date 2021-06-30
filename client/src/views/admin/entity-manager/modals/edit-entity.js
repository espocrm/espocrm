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

define('views/admin/entity-manager/modals/edit-entity', ['views/modal', 'model'], function (Dep, Model) {

    return Dep.extend({

        cssName: 'edit-entity',

        template: 'admin/entity-manager/modals/edit-entity',

        data: function () {
            return {
                isNew: this.isNew
            };
        },

        setupData: function () {
            var scope = this.scope;

            this.hasStreamField = true;

            if (scope) {
                this.hasStreamField =
                    (
                        this.getMetadata().get('scopes.' + scope + '.customizable') &&
                        this.getMetadata().get('scopes.' + scope + '.object')
                    ) || false;
            }

            if (scope === 'User') {
                this.hasStreamField = false;
            }

            this.hasColorField = !this.getConfig().get('scopeColorsDisabled');

            if (scope) {
                this.model.set('name', scope);
                this.model.set('labelSingular', this.translate(scope, 'scopeNames'));
                this.model.set('labelPlural', this.translate(scope, 'scopeNamesPlural'));
                this.model.set('type', this.getMetadata().get('scopes.' + scope + '.type') || '');
                this.model.set('stream', this.getMetadata().get('scopes.' + scope + '.stream') || false);
                this.model.set('disabled', this.getMetadata().get('scopes.' + scope + '.disabled') || false);

                this.model.set('sortBy', this.getMetadata().get('entityDefs.' + scope + '.collection.orderBy'));
                this.model.set('sortDirection', this.getMetadata().get('entityDefs.' + scope + '.collection.order'));

                this.model.set(
                    'textFilterFields',
                    this.getMetadata().get(['entityDefs', scope, 'collection', 'textFilterFields']) || ['name']
                );

                this.model.set(
                    'fullTextSearch',
                    this.getMetadata().get(['entityDefs', scope, 'collection', 'fullTextSearch']) || false
                );
                this.model.set(
                    'countDisabled',
                    this.getMetadata().get(['entityDefs', scope, 'collection', 'countDisabled']) || false
                );

                this.model.set('statusField', this.getMetadata().get('scopes.' + scope + '.statusField') || null);

                if (this.hasColorField) {
                    this.model.set('color', this.getMetadata().get(['clientDefs', scope, 'color']) || null);
                }

                this.model.set('iconClass', this.getMetadata().get(['clientDefs', scope, 'iconClass']) || null);

                this.model.set(
                    'kanbanViewMode',
                    this.getMetadata().get(['clientDefs', scope, 'kanbanViewMode']) || false
                );

                this.model.set(
                    'kanbanStatusIgnoreList',
                    this.getMetadata().get(['scopes', scope, 'kanbanStatusIgnoreList']) || []
                );

                this.model.set(
                    'optimisticConcurrencyControl',
                    this.getMetadata().get(['entityDefs', scope, 'optimisticConcurrencyControl']) || false
                );
            }
        },

        setup: function () {
            this.buttonList = [
                {
                    name: 'save',
                    label: 'Save',
                    style: 'danger'
                },
                {
                    name: 'cancel',
                    label: 'Cancel'
                }
            ];

            var scope = this.scope = this.options.scope || false;

            var header = 'Create Entity';

            this.isNew = true;

            if (scope) {
                header = 'Edit Entity';

                this.isNew = false;
            }

            this.header = this.translate(header, 'labels', 'Admin');

            var model = this.model = new Model();

            model.name = 'EntityManager';

            if (!this.isNew) {
                this.isCustom = this.getMetadata().get(['scopes', scope, 'isCustom'])
            }

            if (!this.isNew && !this.isCustom) {
                this.buttonList.push({
                    name: 'resetToDefault',
                    text: this.translate('Reset to Default', 'labels', 'Admin'),
                });
            }

            this.setupData();

            this.createView('type', 'views/fields/enum', {
                model: model,
                mode: 'edit',
                el: this.options.el + ' .field[data-name="type"]',
                defs: {
                    name: 'type',
                    params: {
                        required: true,
                        options: this.getMetadata().get('app.entityTemplateList') || ['Base']
                    }
                },
                readOnly: scope !== false,
                tooltip: true,
                tooltipText: this.translate('entityType', 'tooltips', 'EntityManager'),
            });

            if (this.hasStreamField) {
                this.createView('stream', 'views/fields/bool', {
                    model: model,
                    mode: 'edit',
                    el: this.options.el + ' .field[data-name="stream"]',
                    defs: {
                        name: 'stream'
                    },
                    tooltip: true,
                    tooltipText: this.translate('stream', 'tooltips', 'EntityManager'),
                });
            }

            this.createView('disabled', 'views/fields/bool', {
                model: model,
                mode: 'edit',
                el: this.options.el + ' .field[data-name="disabled"]',
                defs: {
                    name: 'disabled'
                },
                tooltip: true,
                tooltipText: this.translate('disabled', 'tooltips', 'EntityManager'),
            });

            this.createView('name', 'views/fields/varchar', {
                model: model,
                mode: 'edit',
                el: this.options.el + ' .field[data-name="name"]',
                defs: {
                    name: 'name',
                    params: {
                        required: true,
                        trim: true,
                        maxLength: 100,
                    },
                },
                readOnly: scope !== false
            });
            this.createView('labelSingular', 'views/fields/varchar', {
                model: model,
                mode: 'edit',
                el: this.options.el + ' .field[data-name="labelSingular"]',
                defs: {
                    name: 'labelSingular',
                    params: {
                        required: true,
                        trim: true,
                    }
                }
            });
            this.createView('labelPlural', 'views/fields/varchar', {
                model: model,
                mode: 'edit',
                el: this.options.el + ' .field[data-name="labelPlural"]',
                defs: {
                    name: 'labelPlural',
                    params: {
                        required: true,
                        trim: true,
                    },
                }
            });

            if (this.hasColorField) {
                this.createView('color', 'views/fields/colorpicker', {
                    model: model,
                    mode: 'edit',
                    el: this.options.el + ' .field[data-name="color"]',
                    defs: {
                        name: 'color',
                    },
                });
            }

            this.createView('iconClass', 'views/admin/entity-manager/fields/icon-class', {
                model: model,
                mode: 'edit',
                el: this.options.el + ' .field[data-name="iconClass"]',
                defs: {
                    name: 'iconClass'
                }
            });

            if (scope) {
                var fieldDefs = this.getMetadata().get('entityDefs.' + scope + '.fields') || {};

                var orderableFieldList = Object.keys(fieldDefs)
                    .filter(function (item) {
                        if (!this.getFieldManager().isScopeFieldAvailable(scope, item)) {
                            return false;
                        }

                        if (fieldDefs[item].notStorable) {
                            return false;
                        }

                        return true;
                    }, this)
                    .sort(function (v1, v2) {
                        return this.translate(v1, 'fields', scope).localeCompare(this.translate(v2, 'fields', scope));
                    }.bind(this));

                var translatedOptions = {};

                orderableFieldList.forEach(function (item) {
                    translatedOptions[item] = this.translate(item, 'fields', scope);
                }, this);

                this.createView('sortBy', 'views/fields/enum', {
                    model: model,
                    mode: 'edit',
                    el: this.options.el + ' .field[data-name="sortBy"]',
                    defs: {
                        name: 'sortBy',
                        params: {
                            options: orderableFieldList
                        }
                    },
                    translatedOptions: translatedOptions,
                });

                this.createView('sortDirection', 'views/fields/enum', {
                    model: model,
                    mode: 'edit',
                    el: this.options.el + ' .field[data-name="sortDirection"]',
                    defs: {
                        name: 'sortDirection',
                        params: {
                            options: ['asc', 'desc']
                        }
                    }
                });

                this.createView('fullTextSearch', 'views/fields/bool', {
                    model: model,
                    mode: 'edit',
                    el: this.options.el + ' .field[data-name="fullTextSearch"]',
                    defs: {
                        name: 'fullTextSearch'
                    },
                    tooltip: true,
                });

                this.createView('countDisabled', 'views/fields/bool', {
                    model: model,
                    mode: 'edit',
                    el: this.options.el + ' .field[data-name="countDisabled"]',
                    defs: {
                        name: 'countDisabled'
                    },
                    tooltip: true,
                });

                this.createView('kanbanViewMode', 'views/fields/bool', {
                    model: model,
                    mode: 'edit',
                    el: this.options.el + ' .field[data-name="kanbanViewMode"]',
                    defs: {
                        name: 'kanbanViewMode'
                    }
                });

                var filtersOptionList = this.getTextFiltersOptionList(scope);

                var textFilterFieldsTranslation = {};

                filtersOptionList.forEach(function (item) {
                    if (~item.indexOf('.')) {
                        var link = item.split('.')[0];
                        var foreignField = item.split('.')[1];

                        var foreignEntityType = this.getMetadata().get(['entityDefs', scope, 'links', link, 'entity']);

                        textFilterFieldsTranslation[item] =
                            this.translate(link, 'links', scope) + '.' +
                            this.translate(foreignField, 'fields', foreignEntityType);

                        return;
                    }
                    textFilterFieldsTranslation[item] = this.translate(item, 'fields', scope);
                }, this);

                this.createView('textFilterFields', 'views/fields/multi-enum', {
                    model: model,
                    mode: 'edit',
                    el: this.options.el + ' .field[data-name="textFilterFields"]',
                    defs: {
                        name: 'textFilterFields',
                        params: {
                            options: filtersOptionList
                        }
                    },
                    tooltip: true,
                    tooltipText: this.translate('textFilterFields', 'tooltips', 'EntityManager'),
                    translatedOptions: textFilterFieldsTranslation,
                });


                var enumFieldList = Object.keys(fieldDefs)
                    .filter(function (item) {
                        if (fieldDefs[item].disabled) {
                            return;
                        }

                        if (fieldDefs[item].type === 'enum') {
                            return true;
                        }

                        return;

                    }, this)
                    .sort(function (v1, v2) {
                        return this.translate(v1, 'fields', scope)
                            .localeCompare(this.translate(v2, 'fields', scope));
                    }.bind(this));

                var translatedStatusFields = {};

                enumFieldList.forEach(function (item) {
                    translatedStatusFields[item] = this.translate(item, 'fields', scope);
                }, this);

                enumFieldList.unshift('');

                translatedStatusFields[''] = '-' + this.translate('None') + '-';

                this.createView('statusField', 'views/fields/enum', {
                    model: model,
                    mode: 'edit',
                    el: this.options.el + ' .field[data-name="statusField"]',
                    defs: {
                        name: 'statusField',
                        params: {
                            options: enumFieldList
                        }
                    },
                    tooltip: true,
                    tooltipText: this.translate('statusField', 'tooltips', 'EntityManager'),
                    translatedOptions: translatedStatusFields,
                });

                var statusOptionList = [];
                var translatedStatusOptions = {};

                this.createView('kanbanStatusIgnoreList', 'views/fields/multi-enum', {
                    model: model,
                    mode: 'edit',
                    el: this.options.el + ' .field[data-name="kanbanStatusIgnoreList"]',
                    defs: {
                        name: 'kanbanStatusIgnoreList',
                        params: {
                            options: statusOptionList
                        }
                    },
                    translatedOptions: translatedStatusOptions
                });

                this.createView('optimisticConcurrencyControl', 'views/fields/bool', {
                    model: model,
                    mode: 'edit',
                    el: this.options.el + ' .field[data-name="optimisticConcurrencyControl"]',
                    defs: {
                        name: 'optimisticConcurrencyControl',
                    },
                    tooltip: true,
                });
            }

            this.model.fetchedAttributes = this.model.getClonedAttributes();
        },

        hideField: function (name) {
            var view = this.getView(name);

            if (view) {
                view.disabled = true;
            }

            this.$el.find('.cell[data-name=' + name+']').css('visibility', 'hidden');
        },

        showField: function (name) {
            var view = this.getView(name);

            if (view) {
                view.disabled = false;
            }

            this.$el.find('.cell[data-name=' + name+']').css('visibility', 'visible');
        },

        toPlural: function (string) {
            if (string.slice(-1) === 'y') {
                return string.substr(0, string.length - 1) + 'ies';
            }
            else if (string.slice(-1) === 's') {
                return string + 'es';
            }
            else {
                return string + 's';
            }
        },

        afterRender: function () {
            this.getView('name').on('change', function (m) {
                var name = this.model.get('name');

                name = name.charAt(0).toUpperCase() + name.slice(1);

                this.model.set('labelSingular', name);

                this.model.set('labelPlural', this.toPlural(name)) ;

                if (name) {
                    name = name
                        .replace(/\-/g, ' ')
                        .replace(/_/g, ' ')
                        .replace(/[^\w\s]/gi, '')
                        .replace(/ (.)/g, function (match, g) {
                            return g.toUpperCase();
                        }).replace(' ', '');
                    if (name.length) {
                         name = name.charAt(0).toUpperCase() + name.slice(1);
                    }
                }

                this.model.set('name', name);
            }, this);

            if (!this.isNew) {
                this.manageKanbanFields({});

                this.listenTo(this.model, 'change:statusField', function (m, value, o) {
                    this.manageKanbanFields(o);
                }, this);

                this.manageKanbanViewModeField();

                this.listenTo(this.model, 'change:kanbanViewMode', function () {
                    this.manageKanbanViewModeField();
                }, this);
            }

            if (this.isNew) {
                this.hideField('disabled');
            }
        },

        manageKanbanFields: function (o) {
            if (o.ui) {
                this.model.set('kanbanStatusIgnoreList', []);
            }

            if (this.model.get('statusField')) {
                this.setKanbanStatusIgnoreListOptions();
                this.showField('kanbanViewMode');

                if (this.model.get('kanbanViewMode')) {
                    this.showField('kanbanStatusIgnoreList');
                } else {
                    this.hideField('kanbanStatusIgnoreList');
                }
            }
            else {
                this.hideField('kanbanViewMode');
                this.hideField('kanbanStatusIgnoreList');
            }
        },

        manageKanbanViewModeField: function () {
            if (this.model.get('kanbanViewMode')) {
                this.showField('kanbanStatusIgnoreList');
            } else {
                this.hideField('kanbanStatusIgnoreList');
            }
        },

        setKanbanStatusIgnoreListOptions: function () {
            var statusField = this.model.get('statusField');
            var fieldView = this.getView('kanbanStatusIgnoreList');

            var optionList = this.getMetadata()
                .get(['entityDefs', this.scope, 'fields', statusField, 'options']) || [];

            var translation = this.getMetadata()
                .get(['entityDefs', this.scope, 'fields', statusField, 'translation']) ||
                this.scope + '.options.' + statusField;

            fieldView.params.options = optionList;
            fieldView.params.translation = translation;

            fieldView.setupTranslation();

            fieldView.setOptionList(optionList);
        },

        actionSave: function () {
            var arr = [
                'name',
                'type',
                'labelSingular',
                'labelPlural',
                'stream',
                'disabled',
                'statusField',
                'iconClass',
            ];

            if (this.scope) {
                arr.push('sortBy');
                arr.push('sortDirection');
                arr.push('kanbanViewMode');
                arr.push('kanbanStatusIgnoreList');
                arr.push('optimisticConcurrencyControl');
            }

            if (this.hasColorField) {
                arr.push('color');
            }

            var fetchedAttributes = Espo.Utils.cloneDeep(this.model.fetchedAttributes) || {};

            var notValid = false;

            arr.forEach(function (item) {
                if (!this.hasView(item)) {
                    return;
                }

                if (this.getView(item).mode !== 'edit') {
                    return;
                }

                this.getView(item).fetchToModel();
            }, this);

            arr.forEach(function (item) {
                if (!this.hasView(item)) {
                    return;
                }

                if (this.getView(item).mode !== 'edit') {
                    return;
                }

                notValid = this.getView(item).validate() || notValid;
            }, this);

            if (notValid) {
                return;
            }

            this.disableButton('save');
            this.disableButton('resetToDefault');

            var url = 'EntityManager/action/createEntity';

            if (this.scope) {
                url = 'EntityManager/action/updateEntity';
            }

            var name = this.model.get('name');

            var data = {
                name: name,
                labelSingular: this.model.get('labelSingular'),
                labelPlural: this.model.get('labelPlural'),
                type: this.model.get('type'),
                stream: this.model.get('stream'),
                disabled: this.model.get('disabled'),
                textFilterFields: this.model.get('textFilterFields'),
                fullTextSearch: this.model.get('fullTextSearch'),
                countDisabled: this.model.get('countDisabled'),
                statusField: this.model.get('statusField'),
                iconClass: this.model.get('iconClass'),
            };

            if (this.hasColorField) {
                data.color = this.model.get('color') || null;
            }

            if (data.statusField === '') {
                data.statusField = null;
            }

            if (this.scope) {
                data.sortBy = this.model.get('sortBy');
                data.sortDirection = this.model.get('sortDirection');
                data.kanbanViewMode = this.model.get('kanbanViewMode');
                data.kanbanStatusIgnoreList = this.model.get('kanbanStatusIgnoreList');
                data.optimisticConcurrencyControl = this.model.get('optimisticConcurrencyControl');
            }

            if (!this.isNew) {
                if (this.model.fetchedAttributes.labelPlural === data.labelPlural) {
                    delete data.labelPlural;
                }

                if (this.model.fetchedAttributes.labelSingular === data.labelSingular) {
                    delete data.labelSingular;
                }
            }

            Espo.Ajax
            .postRequest(url, data)
            .then(() => {
                this.model.fetchedAttributes = this.model.getClonedAttributes();

                if (this.scope) {
                    Espo.Ui.success(this.translate('Saved'));
                }
                else {
                    Espo.Ui.success(this.translate('entityCreated', 'messages', 'EntityManager'));
                }

                var global = ((this.getLanguage().data || {}) || {}).Global;

                (global.scopeNames || {})[name] = this.model.get('labelSingular');
                (global.scopeNamesPlural || {})[name] = this.model.get('labelPlural');

                this.getMetadata().loadSkipCache()
                .then(
                    Promise.all([
                        this.getConfig().load(),
                        this.getLanguage().loadSkipCache(),
                    ])
                )
                .then(() => {
                    var rebuildRequired =
                        data.fullTextSearch && !fetchedAttributes.fullTextSearch;

                    var o = {
                        rebuildRequired: rebuildRequired,
                        scope: name,
                    };

                    this.broadcastUpdate();

                    this.trigger('after:save', o);
                });
            })
            .fail(() => {
                this.enableButton('save');
                this.enableButton('resetToDefault');
            });
        },

        actionResetToDefault: function () {
            this.confirm(this.translate('confirmation', 'messages'), () => {
                Espo.Ui.notify(this.translate('pleaseWait', 'messages'));

                this.ajaxPostRequest('EntityManager/action/resetToDefault', {
                    scope: this.scope,
                })
                .then(() => {
                    this.getMetadata()
                        .loadSkipCache()
                        .then(() => this.getLanguage().loadSkipCache())
                        .then(() => {
                            this.setupData();

                            this.model.fetchedAttributes = this.model.getClonedAttributes();

                            this.notify('Done', 'success');

                            this.broadcastUpdate();
                        });
                });
            });
        },

        getTextFiltersOptionList: function (scope) {
            var fieldDefs = this.getMetadata().get(['entityDefs', scope, 'fields']) || {};

            var filtersOptionList = Object.keys(fieldDefs).filter(function (item) {
                var fieldType = fieldDefs[item].type;

                if (!this.getMetadata().get(['fields', fieldType, 'textFilter'])) {
                    return false;
                }

                if (!this.getFieldManager().isScopeFieldAvailable(scope, item)) {
                    return false;
                }

                if (this.getMetadata().get(['entityDefs', scope, 'fields', item, 'textFilterDisabled'])) {
                    return false;
                }

                return true;
            }, this);

            var linkList = Object.keys(this.getMetadata().get(['entityDefs', scope, 'links']) || {});

            linkList.sort(function (v1, v2) {
                return this.translate(v1, 'links', scope).localeCompare(this.translate(v2, 'links', scope));
            }.bind(this));

            linkList.forEach(function (link) {
                var linkType = this.getMetadata().get(['entityDefs', scope, 'links', link, 'type']);

                if (linkType !== 'belongsTo') {
                    return;
                }

                var foreignEntityType = this.getMetadata().get(['entityDefs', scope, 'links', link, 'entity']);

                if (!foreignEntityType) {
                    return;
                }

                var fields = this.getMetadata().get(['entityDefs', foreignEntityType, 'fields']) || {};

                var fieldList = Object.keys(fields);

                fieldList.sort(function (v1, v2) {
                    return this.translate(v1, 'fields', foreignEntityType)
                        .localeCompare(this.translate(v2, 'fields', foreignEntityType));
                }.bind(this));

                fieldList
                    .filter(function (item) {
                        var fieldType = this.getMetadata()
                            .get(['entityDefs', foreignEntityType, 'fields', item, 'type']);

                        if (!this.getMetadata().get(['fields', fieldType, 'textFilter'])) {
                            return false;
                        }

                        if (!this.getMetadata().get(['fields', fieldType, 'textFilterForeign'])) {
                            return false;
                        }

                        if (!this.getFieldManager().isScopeFieldAvailable(foreignEntityType, item)) {
                            return false;
                        }

                        if (
                            this.getMetadata()
                                .get(['entityDefs', foreignEntityType, 'fields', item, 'textFilterDisabled'])
                        ) {
                            return false;
                        }

                        if (
                            this.getMetadata()
                            .get(['entityDefs', foreignEntityType, 'fields', item, 'foreingAccessDisabled'])
                        ) {
                            return false;
                        }

                        return true;

                    }, this)
                    .forEach(function (item) {
                        filtersOptionList.push(link + '.' + item);
                    }, this);
            }, this);

            return filtersOptionList;
        },

        broadcastUpdate: function () {
            this.getHelper().broadcastChannel.postMessage('update:metadata');
            this.getHelper().broadcastChannel.postMessage('update:language');
            this.getHelper().broadcastChannel.postMessage('update:config');
        },

    });
});
