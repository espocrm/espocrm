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

define('views/import/detail', 'views/detail', function (Dep) {

    return Dep.extend({

        getHeader: function () {
            var name = Handlebars.Utils.escapeExpression(
                this.getDateTime().toDisplay(this.model.get('createdAt'))
            );

            return this.buildHeaderHtml([
                '<a href="#' + this.model.name + '/list">' +
                    this.getLanguage().translate(this.model.name, 'scopeNamesPlural') + '</a>',
                name
            ]);
        },

        setup: function () {
            Dep.prototype.setup.call(this);

            this.setupMenu();

            this.listenTo(this.model, 'change', () => {
                this.setupMenu();

                if (this.isRendered()) {
                    this.getView('header').reRender();
                }
            });

            this.listenTo(this.model, 'sync', (m) => {
                this.controlButtons(m);
            });
        },

        setupMenu: function () {
            this.addMenuItem('buttons', {
                label: "Remove Import Log",
                action: "removeImportLog",
                name: 'removeImportLog',
                style: "default",
                acl: "delete",
                title: this.translate('removeImportLog', 'messages', 'Import'),
            }, true);

            this.addMenuItem('buttons', {
                label: "Revert Import",
                name: 'revert',
                action: "revert",
                style: "danger",
                acl: "edit",
                title: this.translate('revert', 'messages', 'Import'),
                hidden: !this.model.get('importedCount'),
            }, true);

            this.addMenuItem('buttons', {
                label: "Remove Duplicates",
                name: 'removeDuplicates',
                action: "removeDuplicates",
                style: "default",
                acl: "edit",
                title: this.translate('removeDuplicates', 'messages', 'Import'),
                hidden: !this.model.get('duplicateCount'),
            }, true);

            this.addMenuItem('dropdown', {
                label: 'New import with same params',
                name: 'createWithSameParams',
                action: 'createWithSameParams',
            });
        },

        controlButtons: function (model) {
            if (!model || model.hasChanged('importedCount')) {
                if (this.model.get('importedCount')) {
                    this.showHeaderActionItem('revert');
                } else {
                    this.hideHeaderActionItem('revert');
                }
            }

            if (!model || model.hasChanged('duplicateCount')) {
                if (this.model.get('duplicateCount')) {
                    this.showHeaderActionItem('removeDuplicates');
                } else {
                    this.hideHeaderActionItem('removeDuplicates');
                }
            }
        },

        actionRemoveImportLog: function () {
            this.confirm(this.translate('confirmRemoveImportLog', 'messages', 'Import'), () => {
                this.disableMenuItem('removeImportLog');

                Espo.Ui.notify(this.translate('pleaseWait', 'messages'));

                this.model.destroy({
                    wait: true,
                }).then(() => {
                    Espo.Ui.notify(false);

                    var collection = this.model.collection;

                    if (collection) {
                        if (collection.total > 0) {
                            collection.total--;
                        }
                    }

                    this.getRouter().navigate('#Import/list', {trigger: true});

                    this.removeMenuItem('removeImportLog', true);
                });
            });
        },

        actionRevert: function () {
            this.confirm(this.translate('confirmRevert', 'messages', 'Import'), () => {
                this.disableMenuItem('revert');

                Espo.Ui.notify(this.translate('pleaseWait', 'messages'));

                Espo.Ajax
                    .postRequest('Import/action/revert', {id: this.model.id})
                    .then(() => {
                        this.getRouter().navigate('#Import/list', {trigger: true});
                    });
            });
        },

        actionRemoveDuplicates: function () {
            this.confirm(this.translate('confirmRemoveDuplicates', 'messages', 'Import'), () => {
                this.disableMenuItem('removeDuplicates');

                Espo.Ui.notify(this.translate('pleaseWait', 'messages'));

                Espo.Ajax
                    .postRequest('Import/action/removeDuplicates', {id: this.model.id})
                    .then(() => {
                        this.removeMenuItem('removeDuplicates', true);

                        this.model.fetch();
                        this.model.trigger('update-all');

                        Espo.Ui.success(this.translate('duplicatesRemoved', 'messages', 'Import'));
                    });
                });
        },

        actionCreateWithSameParams: function () {
            var formData = this.model.get('params') || {};

            formData.entityType = this.model.get('entityType');
            formData.attributeList = this.model.get('attributeList') || [];

            formData = Espo.Utils.cloneDeep(formData);

            this.getRouter().navigate('#Import', {trigger: false});

            this.getRouter().dispatch('Import', 'index', {
                formData: formData,
            });
        },

    });
});
