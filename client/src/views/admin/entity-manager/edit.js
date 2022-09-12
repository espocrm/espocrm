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

define('views/admin/entity-manager/edit', ['view', 'model'], function (Dep, Model) {

    return Dep.extend({

        template: 'admin/entity-manager/edit',

        data: function () {
            return {
                isNew: this.isNew,
                scope: this.scope,
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

            if (scope) {
                let fieldDefs = this.getMetadata().get('entityDefs.' + scope + '.fields') || {};

                this.orderableFieldList = Object.keys(fieldDefs)
                    .filter(item => {
                        if (!this.getFieldManager().isScopeFieldAvailable(scope, item)) {
                            return false;
                        }

                        if (fieldDefs[item].notStorable) {
                            return false;
                        }

                        return true;
                    })
                    .sort((v1, v2) => {
                        return this.translate(v1, 'fields', scope)
                            .localeCompare(this.translate(v2, 'fields', scope));
                    });

                this.sortByTranslation = {};

                this.orderableFieldList.forEach(item => {
                    this.sortByTranslation[item] = this.translate(item, 'fields', scope);
                });

                this.filtersOptionList = this.getTextFiltersOptionList(scope);

                this.textFilterFieldsTranslation = {};

                this.filtersOptionList.forEach(item => {
                    if (~item.indexOf('.')) {
                        var link = item.split('.')[0];
                        var foreignField = item.split('.')[1];

                        var foreignEntityType = this.getMetadata()
                            .get(['entityDefs', scope, 'links', link, 'entity']);

                        this.textFilterFieldsTranslation[item] =
                            this.translate(link, 'links', scope) + '.' +
                            this.translate(foreignField, 'fields', foreignEntityType);

                        return;
                    }

                    this.textFilterFieldsTranslation[item] = this.translate(item, 'fields', scope);
                });

                this.enumFieldList = Object.keys(fieldDefs)
                    .filter(item => {
                        if (fieldDefs[item].disabled) {
                            return;
                        }

                        if (fieldDefs[item].type === 'enum') {
                            return true;
                        }

                        return;
                    })
                    .sort((v1, v2) => {
                        return this.translate(v1, 'fields', scope)
                            .localeCompare(this.translate(v2, 'fields', scope));
                    });

                this.translatedStatusFields = {};

                this.enumFieldList.forEach(item => {
                    this.translatedStatusFields[item] = this.translate(item, 'fields', scope);
                });

                this.enumFieldList.unshift('');

                this.translatedStatusFields[''] = '-' + this.translate('None') + '-';

                this.statusOptionList = [];
                this.translatedStatusOptions = {};
            }

            this.detailLayout = [
                {
                    rows: [
                        [
                            {
                                name: 'name',
                            },
                            {
                                name: 'type',
                                options: {
                                    tooltipText: this.translate('entityType', 'tooltips', 'EntityManager'),
                                }
                            },
                        ],
                        [
                            {
                                name: 'labelSingular',
                            },
                            {
                                name: 'labelPlural',
                            },
                        ],
                        [
                            {
                                name: 'iconClass',
                            },
                            {
                                name: 'color',
                            },
                        ],
                        [
                            {
                                name: 'disabled',
                            },
                            {
                                name: 'stream',
                            },
                        ],
                        [
                            {
                                name: 'sortBy',
                                options: {
                                    translatedOptions: this.sortByTranslation,
                                },
                            },
                            {
                                name: 'sortDirection',
                            },
                        ],
                        [
                            {
                                name: 'textFilterFields',
                                options: {
                                    translatedOptions: this.textFilterFieldsTranslation,
                                },
                            },
                            {
                                name: 'statusField',
                                options: {
                                    translatedOptions: this.translatedStatusFields,
                                },
                            },
                        ],
                        [
                            {
                                name: 'fullTextSearch',
                            },
                            {
                                name: 'countDisabled',
                            },
                        ],
                        [
                            {
                                name: 'optimisticConcurrencyControl',
                            },
                            false,
                        ],
                        [
                            {
                                name: 'kanbanViewMode',
                            },
                            {
                                name: 'kanbanStatusIgnoreList',
                                options: {
                                    translatedOptions: this.translatedStatusOptions,
                                },
                            },
                        ],
                    ]
                }
            ];
        },

        setup: function () {
            var scope = this.scope = this.options.scope || false;

            this.isNew = !scope;

            var model = this.model = new Model();

            model.name = 'EntityManager';

            if (!this.isNew) {
                this.isCustom = this.getMetadata().get(['scopes', scope, 'isCustom'])
            }

            this.setupData();

            this.model.setDefs({
                fields: {
                    type: {
                        type: 'enum',
                        required: true,
                        options: this.getMetadata().get('app.entityTemplateList') || ['Base'],
                        readOnly: scope !== false,
                        tooltip: true,
                    },
                    stream: {
                        type: 'bool',
                        required: true,
                        tooltip: true,
                    },
                    disabled: {
                        type: 'bool',
                        tooltip: true,
                    },
                    name: {
                        type: 'varchar',
                        required: true,
                        trim: true,
                        maxLength: 100,
                        readOnly: scope !== false,
                    },
                    labelSingular: {
                        type: 'varchar',
                        required: true,
                        trim: true,
                    },
                    labelPlural: {
                        type: 'varchar',
                        required: true,
                        trim: true,
                    },
                    color: {
                        type: 'varchar',
                        view: 'views/fields/colorpicker',
                    },
                    iconClass: {
                        type: 'varchar',
                        view: 'views/admin/entity-manager/fields/icon-class',
                    },
                    sortBy: {
                        type: 'enum',
                        options: this.orderableFieldList,
                    },
                    sortDirection: {
                        type: 'enum',
                        options: ['asc', 'desc'],
                    },
                    fullTextSearch: {
                        type: 'bool',
                        tooltip: true,
                    },
                    countDisabled: {
                        type: 'bool',
                        tooltip: true,
                    },
                    kanbanViewMode: {
                        type: 'bool',
                    },
                    textFilterFields: {
                        type: 'multiEnum',
                        options: this.filtersOptionList,
                        tooltip: true,
                    },
                    statusField: {
                        type: 'enum',
                        options: this.enumFieldList,
                        tooltip: true,
                    },
                    kanbanStatusIgnoreList: {
                        type: 'multiEnum',
                        options: this.statusOptionList,
                    },
                    optimisticConcurrencyControl: {
                        type: 'bool',
                        tooltip: true,
                    },
                },
            });

            this.model.fetchedAttributes = this.model.getClonedAttributes();

            this.createRecordView();
        },

        createRecordView: function () {
            return this.createView('record', 'views/admin/entity-manager/record/edit', {
                el: this.getSelector() + ' .record',
                model: this.model,
                detailLayout: this.detailLayout,
                isNew: this.isNew,
                hasColorField: this.hasColorField,
                hasStreamField: this.hasStreamField,
                isCustom: this.isCustom,
                subjectEntityType: this.scope,
            }).then(view => {
                this.listenTo(view, 'save', () => this.actionSave());
                this.listenTo(view, 'cancel', () => this.actionCancel());
                this.listenTo(view, 'reset-to-default', () => this.actionResetToDefault());
            });
        },

        hideField: function (name) {
            this.getView('record').hideField(name);
        },

        showField: function (name) {
            this.getView('record').showField(name);
        },

        toPlural: function (string) {
            if (string.slice(-1) === 'y') {
                return string.substr(0, string.length - 1) + 'ies';
            }

            if (string.slice(-1) === 's') {
                return string + 'es';
            }

            return string + 's';
        },

        afterRender: function () {
            this.getFieldView('name').on('change', (m) => {
                var name = this.model.get('name');

                name = name.charAt(0).toUpperCase() + name.slice(1);

                this.model.set('labelSingular', name);

                this.model.set('labelPlural', this.toPlural(name)) ;

                if (name) {
                    name = name
                        .replace(/\-/g, ' ')
                        .replace(/_/g, ' ')
                        .replace(/[^\w\s]/gi, '')
                        .replace(/ (.)/g, (match, g) => {
                            return g.toUpperCase();
                        })
                        .replace(' ', '');

                    if (name.length) {
                        name = name.charAt(0).toUpperCase() + name.slice(1);
                    }
                }

                this.model.set('name', name);
            });
        },

        actionSave: function () {
            var fieldList = [
                'name',
                'type',
                'labelSingular',
                'labelPlural',
                'disabled',
                'statusField',
                'iconClass',
            ];

            if (this.hasStreamField) {
                fieldList.push('stream');
            }

            if (this.scope) {
                fieldList.push('sortBy');
                fieldList.push('sortDirection');
                fieldList.push('kanbanViewMode');
                fieldList.push('kanbanStatusIgnoreList');
                fieldList.push('optimisticConcurrencyControl');
            }

            if (this.hasColorField) {
                fieldList.push('color');
            }

            var fetchedAttributes = Espo.Utils.cloneDeep(this.model.fetchedAttributes) || {};

            var notValid = false;

            fieldList.forEach(item => {
                if (!this.getFieldView(item)) {
                    return;
                }

                if (this.getFieldView(item).mode !== 'edit') {
                    return;
                }

                this.getFieldView(item).fetchToModel();
            });

            fieldList.forEach(item => {
                if (!this.getFieldView(item)) {
                    return;
                }

                if (this.getFieldView(item).mode !== 'edit') {
                    return;
                }

                notValid = this.getFieldView(item).validate() || notValid;
            });

            if (notValid) {
                return;
            }

            this.disableButtons();

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

            Espo.Ui.notify(this.translate('pleaseWait', 'messages'));

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

                    this.broadcastUpdate();

                    if (rebuildRequired) {
                        this
                            .createView('dialog', 'views/modal', {
                                templateContent:
                                    "{{complexText viewObject.options.msg}}" +
                                    "{{complexText viewObject.options.msgRebuild}}",
                                headerText: this.translate('rebuildRequired', 'strings', 'Admin'),
                                backdrop: 'static',
                                msg: this.translate('rebuildRequired', 'messages', 'Admin'),
                                msgRebuild: '```php rebuild.php```',
                                buttonList: [
                                    {
                                        name: 'close',
                                        label: this.translate('Close'),
                                    },
                                ],
                            })
                            .then(view => view.render());
                    }

                    this.enableButtons();

                    this.getView('record').setIsNotChanged();

                    if (this.isNew) {
                        this.getRouter().navigate('#Admin/entityManager/scope=' + name, {trigger: true});
                    }
                });
            })
            .catch(() => {
                this.enableButtons();
            });
        },

        actionCancel: function () {
            this.getView('record').setConfirmLeaveOut(false);

            if (!this.isNew) {
                this.getRouter().navigate('#Admin/entityManager/scope=' + this.scope, {trigger: true});

                return;
            }

            this.getRouter().navigate('#Admin/entityManager', {trigger: true});
        },

        actionResetToDefault: function () {
            this.confirm(this.translate('confirmation', 'messages'), () => {
                Espo.Ui.notify(this.translate('pleaseWait', 'messages'));

                this.disableButtons();

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

                            this.enableButtons();
                            this.broadcastUpdate();

                            this.getView('record').setIsNotChanged();
                        });
                });
            });
        },

        getTextFiltersOptionList: function (scope) {
            var fieldDefs = this.getMetadata().get(['entityDefs', scope, 'fields']) || {};

            var filtersOptionList = Object.keys(fieldDefs).filter(item => {
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
            });

            var linkList = Object.keys(this.getMetadata().get(['entityDefs', scope, 'links']) || {});

            linkList.sort((v1, v2) => {
                return this.translate(v1, 'links', scope).localeCompare(this.translate(v2, 'links', scope));
            });

            linkList.forEach((link) => {
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

                fieldList.sort((v1, v2) => {
                    return this.translate(v1, 'fields', foreignEntityType)
                        .localeCompare(this.translate(v2, 'fields', foreignEntityType));
                });

                fieldList
                    .filter((item) => {
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
                                .get(['entityDefs', foreignEntityType, 'fields', item, 'foreignAccessDisabled'])
                        ) {
                            return false;
                        }

                        return true;
                    })
                    .forEach((item) => {
                        filtersOptionList.push(link + '.' + item);
                    });
            });

            return filtersOptionList;
        },

        getFieldView: function (name) {
            return this.getView('record').getFieldView(name);
        },

        disableButtons: function () {
            this.getView('record').disableButtons();
        },

        enableButtons: function () {
            this.getView('record').enableButtons();
        },

        broadcastUpdate: function () {
            this.getHelper().broadcastChannel.postMessage('update:metadata');
            this.getHelper().broadcastChannel.postMessage('update:language');
            this.getHelper().broadcastChannel.postMessage('update:config');
        },
    });
});
