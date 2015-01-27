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

Espo.define('Views.Admin.LinkManager.Modals.Edit', 'Views.Modal', function (Dep) {

    return Dep.extend({

        cssName: 'edit',

        template: 'admin.link-manager.modals.edit',

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

            var scope = this.scope = this.options.scope;
            var link = this.link = this.options.link || false;

            var isNew = this.isNew = (false == link);

            var header = 'Create Link';
            if (link) {
                header = 'Edit Entity';
            }

            this.header = this.translate(header, 'labels', 'Admin');

            var model = this.model = new Espo.Model();
            model.name = 'EntityManager';


            if (scope) {
                this.model.set('entity', scope);
            }

            var scopes = this.getMetadata().get('scopes') || null;
            var entityList = (Object.keys(scopes) || []).filter(function (item) {
                var d = scopes[item];
                return d.customizable && d.entity;
            }, this).sort(function (v1, v2) {
                var t1 = this.translate(v1, 'scopeNames');
                var t2 = this.translate(v2, 'scopeNames');
                return t1.localeCompare(t2);
            }.bind(this));


            this.createView('entity', 'Fields.Varchar', {
                model: model,
                mode: 'edit',
                el: this.options.el + ' .field-entity',
                defs: {
                    name: 'entity'
                },
                readOnly: true
            });
            this.createView('entityForeign', 'Fields.Enum', {
                model: model,
                mode: 'edit',
                el: this.options.el + ' .field-entityForeign',
                defs: {
                    name: 'entityForeign',
                    params: {
                        required: true,
                        options: entityList,
                        translation: 'Global.scopeNames'
                    }
                },
                readOnly: !isNew
            });
            this.createView('linkType', 'Fields.Enum', {
                model: model,
                mode: 'edit',
                el: this.options.el + ' .field-linkType',
                defs: {
                    name: 'linkType',
                    params: {
                        required: true,
                        options: ['', 'oneToMany', 'ManyToOne', 'ManyToMany']
                    }
                },
                readOnly: !isNew
            });

            this.createView('link', 'Fields.Varchar', {
                model: model,
                mode: 'edit',
                el: this.options.el + ' .field-link',
                defs: {
                    name: 'link',
                    params: {
                        required: true
                    }
                },
                readOnly: !isNew
            });
            this.createView('linkForeign', 'Fields.Varchar', {
                model: model,
                mode: 'edit',
                el: this.options.el + ' .field-linkForeign',
                defs: {
                    name: 'linkForeign',
                    params: {
                        required: true
                    }
                },
                readOnly: !isNew
            });
            this.createView('label', 'Fields.Varchar', {
                model: model,
                mode: 'edit',
                el: this.options.el + ' .field-label',
                defs: {
                    name: 'label',
                    params: {
                        required: true
                    }
                }
            });
            this.createView('labelForeign', 'Fields.Varchar', {
                model: model,
                mode: 'edit',
                el: this.options.el + ' .field-labelForeign',
                defs: {
                    name: 'labelForeign',
                    params: {
                        required: true
                    }
                }
            });
        },

        populateFields: function () {
            var entityForeign = this.model.get('entityForeign');

            name = entityForeign.charAt(0).toUpperCase() + name.slice(1);

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
        },

        afterRender: function () {
            this.getView('linkType').on('change', function (m) {
                this.populateFields();
            }, this);
            this.getView('entityForeign').on('change', function (m) {
                this.populateFields();
            }, this);
        },

        save: function () {
            var arr = [
                'link',
                'linkForeign',
                'label',
                'labelForeign',
                'linkType',
                'entityForeign'
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

            var url = 'EntityManager/action/createLink';
            if (this.scope) {
                url = 'EntityManager/action/updateLink';
            }

            $.ajax({
                url: url,
                type: 'POST',
                data: JSON.stringify({
                    scope: this.scope,
                    entityForeign: this.model.get('entityForeign'),
                    link: this.model.get('link'),
                    linkForeign: this.model.get('linkForeign'),
                    labelSingular: this.model.get('label'),
                    labelPlural: this.model.get('labelForeign'),
                    linkType: this.model.get('linkType'),
                }),
                error: function () {
                    this.$el.find('button[data-name="save"]').removeClass('disabled');
                }.bind(this)
            }).done(function () {
                if (!this.isNew) {
                    Espo.Ui.success(this.translate('Saved'));
                } else {
                    Espo.Ui.success(this.translate('Created'));
                }
                /*var global = ((this.getLanguage().data || {}) || {}).Global;
                (global.scopeNames || {})[name] = this.model.get('labelSingular');
                (global.scopeNamesPlural || {})[name] = this.model.get('labelPlural');*/

                this.getMetadata().load(function () {
                    this.trigger('after:save');
                    this.close();
                }.bind(this));
            }.bind(this));
        },

    });
});

