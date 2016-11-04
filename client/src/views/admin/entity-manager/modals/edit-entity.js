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

Espo.define('views/admin/entity-manager/modals/edit-entity', ['views/modal', 'model'], function (Dep, Model) {

    return Dep.extend({

        cssName: 'edit-entity',

        template: 'admin/entity-manager/modals/edit-entity',

        data: function () {
            return {
                isNew: this.isNew
            };
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

            this.hasStreamField = true;
            if (scope) {
                this.hasStreamField = (this.getMetadata().get('scopes.' + scope + '.customizable') && this.getMetadata().get('scopes.' + scope + '.object')) || false;
            }
            if (scope === 'User') {
                this.hasStreamField = false;
            }

            if (scope) {
                this.model.set('name', scope);
                this.model.set('labelSingular', this.translate(scope, 'scopeNames'));
                this.model.set('labelPlural', this.translate(scope, 'scopeNamesPlural'));
                this.model.set('type', this.getMetadata().get('scopes.' + scope + '.type') || '');
                this.model.set('stream', this.getMetadata().get('scopes.' + scope + '.stream') || false);
                this.model.set('disabled', this.getMetadata().get('scopes.' + scope + '.disabled') || false);

                this.model.set('sortBy', this.getMetadata().get('entityDefs.' + scope + '.collection.sortBy'));
                this.model.set('sortDirection', this.getMetadata().get('entityDefs.' + scope + '.collection.asc') ? 'asc' : 'desc');

                this.model.set('textFilterFields', this.getMetadata().get('entityDefs.' + scope + '.collection.textFilterFields') || ['name']);

                this.model.set('statusField', this.getMetadata().get('scopes.' + scope + '.statusField') || null);
            }

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
                readOnly: scope != false,
                tooltip: true,
                tooltipText: this.translate('entityType', 'tooltips', 'EntityManager')
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
                    tooltipText: this.translate('stream', 'tooltips', 'EntityManager')
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
                tooltipText: this.translate('disabled', 'tooltips', 'EntityManager')
            });

            this.createView('name', 'views/fields/varchar', {
                model: model,
                mode: 'edit',
                el: this.options.el + ' .field[data-name="name"]',
                defs: {
                    name: 'name',
                    params: {
                        required: true,
                        trim: true
                    }
                },
                readOnly: scope != false
            });
            this.createView('labelSingular', 'views/fields/varchar', {
                model: model,
                mode: 'edit',
                el: this.options.el + ' .field[data-name="labelSingular"]',
                defs: {
                    name: 'labelSingular',
                    params: {
                        required: true,
                        trim: true
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
                        trim: true
                    }
                }
            });

            if (scope) {
                var fieldDefs = this.getMetadata().get('entityDefs.' + scope + '.fields') || {}

                var orderableFieldList = Object.keys(fieldDefs).filter(function (item) {
                    if (fieldDefs[item].notStorable) {
                        return false;
                    }
                    return true;
                }, this).sort(function (v1, v2) {
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
                    translatedOptions: translatedOptions
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
                var fieldDefs = this.getMetadata().get(['entityDefs', scope, 'fields']) || {};

                var optionList = Object.keys(fieldDefs).filter(function (item) {
                    if (!~['varchar', 'text', 'phone', 'email', 'personName'].indexOf(this.getMetadata().get(['entityDefs', scope, 'fields', item, 'type']))) {
                        return false;
                    }
                    if (this.getMetadata().get(['entityDefs', scope, 'fields', item, 'disabled'])) {
                        return false;
                    }
                    return true;
                }, this);

                var textFilterFieldsTranslation = {};
                optionList.forEach(function (item) {
                    textFilterFieldsTranslation[item] = this.translate(item, 'fields', scope);
                }, this);

                this.createView('textFilterFields', 'views/fields/multi-enum', {
                    model: model,
                    mode: 'edit',
                    el: this.options.el + ' .field[data-name="textFilterFields"]',
                    defs: {
                        name: 'textFilterFields',
                        params: {
                            options: optionList
                        }
                    },
                    tooltip: true,
                    tooltipText: this.translate('textFilterFields', 'tooltips', 'EntityManager'),
                    translatedOptions: textFilterFieldsTranslation
                });


                if (this.hasStreamField) {
                    var enumFieldList = Object.keys(fieldDefs).filter(function (item) {
                        if (fieldDefs[item].disabled) return;
                        if (fieldDefs[item].type == 'enum') {
                            return true;
                        }
                        return;
                    }, this).sort(function (v1, v2) {
                        return this.translate(v1, 'fields', scope).localeCompare(this.translate(v2, 'fields', scope));
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
                        translatedOptions: translatedStatusFields
                    });
                }
            }
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

        afterRender: function () {
            this.getView('name').on('change', function (m) {
                var name = this.model.get('name');

                name = name.charAt(0).toUpperCase() + name.slice(1);

                this.model.set('labelSingular', name);
                this.model.set('labelPlural', name + 's') ;
                if (name) {
                    name = name.replace(/\-/g, ' ').replace(/_/g, ' ').replace(/[^\w\s]/gi, '').replace(/ (.)/g, function (match, g) {
                        return g.toUpperCase();
                    }).replace(' ', '');
                    if (name.length) {
                         name = name.charAt(0).toUpperCase() + name.slice(1);
                    }
                }
                this.model.set('name', name);
            }, this);

            this.manageStreamField();
            this.listenTo(this.model, 'change:stream', function () {
                this.manageStreamField();
            }, this);

            if (this.isNew) {
                this.hideField('disabled');
            }
        },

        manageStreamField: function () {
            if (this.model.get('stream')) {
                this.showField('statusField');
            } else {
                this.hideField('statusField');
            }
        },

        actionSave: function () {
            var arr = [
                'name',
                'type',
                'labelSingular',
                'labelPlural',
                'stream',
                'disabled',
                'statusField'
            ];

            if (this.scope) {
                arr.push('sortBy');
                arr.push('sortDirection');
            }

            var notValid = false;

            arr.forEach(function (item) {
                if (!this.hasView(item)) return;
                if (this.getView(item).mode != 'edit') return;
                this.getView(item).fetchToModel();
            }, this);

            arr.forEach(function (item) {
                if (!this.hasView(item)) return;
                if (this.getView(item).mode != 'edit') return;
                notValid = this.getView(item).validate() || notValid;
            }, this);

            if (notValid) {
                return;
            }

            this.$el.find('button[data-name="save"]').addClass('disabled');

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
                statusField: this.model.get('statusField')
            };

            if (data.statusField === '') {
                data.statusField = null;
            }

            if (this.scope) {
                data.sortBy = this.model.get('sortBy');
                data.sortDirection = this.model.get('sortDirection');
            }

            $.ajax({
                url: url,
                type: 'POST',
                data: JSON.stringify(data),
                error: function () {
                    this.$el.find('button[data-name="save"]').removeClass('disabled');
                }.bind(this)
            }).done(function () {
                if (this.scope) {
                    Espo.Ui.success(this.translate('Saved'));
                } else {
                    Espo.Ui.success(this.translate('entityCreated', 'messages', 'EntityManager'));
                }
                var global = ((this.getLanguage().data || {}) || {}).Global;
                (global.scopeNames || {})[name] = this.model.get('labelSingular');
                (global.scopeNamesPlural || {})[name] = this.model.get('labelPlural');

                Promise.all([
                    new Promise(function (resolve) {
                        this.getMetadata().load(function () {
                            resolve();
                        }, true);
                    }.bind(this)),
                    new Promise(function (resolve) {
                        this.getConfig().load(function () {
                            resolve();
                        }, true);
                    }.bind(this)),
                    new Promise(function (resolve) {
                        this.getLanguage().load(function () {
                            resolve();
                        }, true);
                    }.bind(this))
                ]).then(function () {
                    this.trigger('after:save');
                }.bind(this));

            }.bind(this));
        },

    });
});

