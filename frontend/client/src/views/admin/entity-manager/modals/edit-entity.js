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

Espo.define('Views.Admin.EntityManager.Modals.EditEntity', 'Views.Modal', function (Dep) {

    return Dep.extend({

        cssName: 'edit-entity',

        template: 'admin.entity-manager.modals.edit-entity',

        setup: function () {

            this.buttons = [
                {
                    name: 'save',
                    label: 'Save',
                    style: 'danger',
                    onClick: function (dialog) {
                        this.save();
                    }.bind(this)
                },
                {
                    name: 'cancel',
                    label: 'Cancel',
                    onClick: function (dialog) {
                        dialog.close();
                    }
                }
            ];

            var scope = this.scope = this.options.scope || false;

            var header = 'Create Entity';
            if (scope) {
                header = 'Edit Entity';
            }

            this.header = this.translate(header, 'labels', 'Admin');

            var model = this.model = new Espo.Model();
            model.name = 'EntityManager';

            this.hasStreamField = true;
            if (scope) {
                this.hasStreamField = this.getMetadata().get('scopes.' + scope + '.customizable') || false;
            }

            if (scope) {
                this.model.set('name', scope);
                this.model.set('labelSingular', this.translate(scope, 'scopeNames'));
                this.model.set('labelPlural', this.translate(scope, 'scopeNamesPlural'));
                this.model.set('type', this.getMetadata().get('scopes.' + scope + '.type') || '');
                this.model.set('stream', this.getMetadata().get('scopes.' + scope + '.stream') || false);
            }

            this.createView('type', 'Fields.Enum', {
                model: model,
                mode: 'edit',
                el: this.options.el + ' .field-type',
                defs: {
                    name: 'type',
                    params: {
                        required: true,
                        options: ['Base', 'Person']
                    }
                },
                readOnly: scope != false
            });

            if (this.hasStreamField) {
                this.createView('stream', 'Fields.Bool', {
                    model: model,
                    mode: 'edit',
                    el: this.options.el + ' .field-stream',
                    defs: {
                        name: 'stream'
                    }
                });
            }

            this.createView('name', 'Fields.Varchar', {
                model: model,
                mode: 'edit',
                el: this.options.el + ' .field-name',
                defs: {
                    name: 'name',
                    params: {
                        required: true
                    }
                },
                readOnly: scope != false
            });
            this.createView('labelSingular', 'Fields.Varchar', {
                model: model,
                mode: 'edit',
                el: this.options.el + ' .field-labelSingular',
                defs: {
                    name: 'labelSingular',
                    params: {
                        required: true
                    }
                }
            });
            this.createView('labelPlural', 'Fields.Varchar', {
                model: model,
                mode: 'edit',
                el: this.options.el + ' .field-labelPlural',
                defs: {
                    name: 'labelPlural',
                    params: {
                        required: true
                    }
                }
            });
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
        },

        save: function () {
            var arr = [
                'name',
                'type',
                'labelSingular',
                'labelPlural',
                'stream'
            ];

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

            $.ajax({
                url: url,
                type: 'POST',
                data: JSON.stringify({
                    name: name,
                    labelSingular: this.model.get('labelSingular'),
                    labelPlural: this.model.get('labelPlural'),
                    type: this.model.get('type'),
                    stream: this.model.get('stream')
                }),
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

                this.getMetadata().load(function () {
                    this.getConfig().load(function () {
                        this.trigger('after:save');
                    }.bind(this), true);
                }.bind(this), true);
            }.bind(this));
        },

    });
});

