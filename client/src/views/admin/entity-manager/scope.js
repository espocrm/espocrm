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

define('views/admin/entity-manager/scope', 'view', function (Dep) {

    return Dep.extend({

        template: 'admin/entity-manager/scope',

        scope: null,

        data: function () {
            return {
                scope: this.scope,
                isRemovable: this.isRemovable,
                isCustomizable: this.isCustomizable,
                type: this.type,
                hasLayouts: this.hasLayouts,
                label: this.label,
            };
        },

        events: {
            'click [data-action="editEntity"]': function (e) {
                this.editEntity();
            },
            'click [data-action="editFormula"]': function (e) {
                this.editFormula();

            },
            'click [data-action="removeEntity"]': function (e) {
                this.removeEntity();
            },
        },

        setup: function () {
            this.scope = this.options.scope;

            this.setupScopeData();
        },

        setupScopeData: function () {
            var scopeData = this.getMetadata().get(['scopes', this.scope]);

            if (!scopeData) {
                throw new Espo.Exceptions.NotFound();
            }

            this.isRemovable = !!scopeData.isCustom;

            if (scopeData.isNotRemovable) {
                this.isRemovable = false;
            }

            this.isCustomizable = !!scopeData.customizable;

            this.type = scopeData.type;

            this.hasLayouts = scopeData.layouts;

            this.label = this.getLanguage().translate(this.scope, 'scopeNames');
        },

        editEntity: function () {
            this.createView('edit', 'views/admin/entity-manager/modals/edit-entity', {
                scope: this.scope,
            }, function (view) {
                view.render();

                this.listenTo(view, 'after:save', function (o) {
                    this.clearView('edit');

                    this.setupScopeData();

                    this.reRender();

                    if (o.rebuildRequired) {
                        this.createView('dialog', 'views/modal', {
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
                        }, function (view) {
                            view.render();
                        });
                    }
                }, this);

                this.listenTo(view, 'close', function () {
                    this.clearView('edit');
                }, this);

            }, this);
        },

        removeEntity: function () {
            var scope = this.scope;

            this.confirm(this.translate('confirmRemove', 'messages', 'EntityManager'), function () {

                Espo.Ui.notify(
                    this.translate('pleaseWait', 'messages')
                );

                this.disableButtons();

                Espo.Ajax.postRequest('EntityManager/action/removeEntity', {
                    name: scope,
                })
                .then(() => {
                    this.getMetadata()
                        .loadSkipCache()
                        .then(() => {
                            this.getConfig().load().then(() => {
                                Espo.Ui.notify(false);

                                this.broadcastUpdate();

                                this.getRouter().navigate('#Admin/entityManager', {trigger: true});
                            });
                        });
                })
                .fail(() => this.enableButtons());

            }.bind(this));
        },

        afterRender: function () {

        },

        editFormula: function () {
            var scope = this.scope;

            this.createView('edit', 'views/admin/entity-manager/modals/edit-formula', {
                scope: scope,
            }, function (view) {
                view.render();

                this.listenTo(view, 'after:save', function () {
                    this.clearView('edit');
                }, this);

                this.listenTo(view, 'close', function () {
                    this.clearView('edit');
                }, this);
            }, this);
        },

        updatePageTitle: function () {
            this.setPageTitle(
                this.getLanguage().translate('Entity Manager', 'labels', 'Admin')
            );
        },

        disableButtons: function () {
            this.$el.find('.btn.action').addClass('disabled').attr('disabled', 'disabled');
            this.$el.find('.item-dropdown-button').addClass('disabled').attr('disabled', 'disabled');
        },

        enableButtons: function () {
            this.$el.find('.btn.action').removeClass('disabled').removeAttr('disabled');
            this.$el.find('.item-dropdown-button"]').removeClass('disabled').removeAttr('disabled');
        },

        broadcastUpdate: function () {
            this.getHelper().broadcastChannel.postMessage('update:metadata');
            this.getHelper().broadcastChannel.postMessage('update:settings');
        },

    });
});
