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

define('views/email/record/list', 'views/record/list', function (Dep) {

    return Dep.extend({

        rowActionsView: 'views/email/record/row-actions/default',

        massActionList: ['remove', 'massUpdate'],

        dropdownItemList: [
            {
                name: 'markAllAsRead',
                label: 'Mark all as read'
            }
        ],

        setup: function () {
            Dep.prototype.setup.call(this);

            this.addMassAction('retrieveFromTrash', false, true);
            this.addMassAction('moveToFolder', false, true);
            this.addMassAction('markAsNotImportant', false, true);
            this.addMassAction('markAsImportant', false, true);
            this.addMassAction('markAsNotRead', false, true);
            this.addMassAction('markAsRead', false, true);
            this.addMassAction('moveToTrash', false, true);

            this.listenTo(this.collection, 'moving-to-trash', (id) => {
                var model = this.collection.get(id);

                if (model) {
                    model.set('inTrash', true);
                }

                if (this.collection.selectedFolderId !== 'trash' && this.collection.selectedFolderId !== 'all') {
                    this.removeRecordFromList(id);
                }
            });

            this.listenTo(this.collection, 'retrieving-from-trash', (id) => {
                var model = this.collection.get(id);

                if (model) {
                    model.set('inTrash', false);
                }

                if (this.collection.selectedFolderId === 'trash') {
                    this.removeRecordFromList(id);
                }
            });
        },

        massActionMarkAsRead: function () {
            var ids = [];

            for (var i in this.checkedList) {
                ids.push(this.checkedList[i]);
            }

            Espo.Ajax
                .postRequest('Email/action/markAsRead', {
                    ids: ids,
                });

            ids.forEach(id => {
                var model = this.collection.get(id);

                if (model) {
                    model.set('isRead', true);
                }
            });
        },

        massActionMarkAsNotRead: function () {
            var ids = [];

            for (var i in this.checkedList) {
                ids.push(this.checkedList[i]);
            }

            Espo.Ajax
                .postRequest('Email/action/markAsNotRead', {
                    ids: ids,
                });

            ids.forEach(id => {
                var model = this.collection.get(id);

                if (model) {
                    model.set('isRead', false);
                }
            });
        },

        massActionMarkAsImportant: function () {
            var ids = [];

            for (var i in this.checkedList) {
                ids.push(this.checkedList[i]);
            }

            Espo.Ajax
                .postRequest('Email/action/markAsImportant', {
                    ids: ids,
                });

            ids.forEach(id => {
                var model = this.collection.get(id);

                if (model) {
                    model.set('isImportant', true);
                }
            });
        },

        massActionMarkAsNotImportant: function () {
            var ids = [];

            for (var i in this.checkedList) {
                ids.push(this.checkedList[i]);
            }

            Espo.Ajax
                .postRequest('Email/action/markAsNotImportant', {
                    ids: ids,
                });

            ids.forEach(id => {
                var model = this.collection.get(id);

                if (model) {
                    model.set('isImportant', false);
                }
            });
        },

        massActionMoveToTrash: function () {
            var ids = [];

            for (var i in this.checkedList) {
                ids.push(this.checkedList[i]);
            }

            Espo.Ajax
                .postRequest('Email/action/moveToTrash', {
                    ids: ids
                })
                .then(() => {
                    Espo.Ui.success(this.translate('Done'));
                });

            if (this.collection.selectedFolderId === 'trash') {
                return;
            }

            ids.forEach(id => {
                this.collection.trigger('moving-to-trash', id);
            });
        },

        massActionRetrieveFromTrash: function () {
            var ids = [];

            for (var i in this.checkedList) {
                ids.push(this.checkedList[i]);
            }

            Espo.Ajax
                .postRequest('Email/action/retrieveFromTrash', {
                    ids: ids
                })
                .then(() => {
                    Espo.Ui.success(this.translate('Done'));
                });

            if (this.collection.selectedFolderId !== 'trash') {
                return;
            }

            ids.forEach(id => {
                this.collection.trigger('retrieving-from-trash', id);
            });
        },

        massActionMoveToFolder: function () {
            var ids = [];

            for (var i in this.checkedList) {
                ids.push(this.checkedList[i]);
            }

            this.createView('dialog', 'views/email-folder/modals/select-folder', {}, view => {
                view.render();

                this.listenToOnce(view, 'select', folderId => {
                    this.clearView('dialog');

                    Espo.Ajax
                        .postRequest('Email/action/moveToFolder', {
                            ids: ids,
                            folderId: folderId,
                        })
                        .then(() => {
                            this.collection.fetch().then(() => {
                                Espo.Ui.success(this.translate('Done'));
                            });
                        });
                });
            });
        },

        actionMarkAsImportant: function (data) {
            data = data || {};

            var id = data.id;

            Espo.Ajax
                .postRequest('Email/action/markAsImportant', {
                    id: id,
                });

            var model = this.collection.get(id);

            if (model) {
                model.set('isImportant', true);
            }
        },

        actionMarkAsNotImportant: function (data) {
            data = data || {};

            var id = data.id;

            Espo.Ajax
                .postRequest('Email/action/markAsNotImportant', {
                    id: id,
                });

            var model = this.collection.get(id);

            if (model) {
                model.set('isImportant', false);
            }
        },

        actionMarkAllAsRead: function () {
            Espo.Ajax
                .postRequest('Email/action/markAllAsRead');

            this.collection.forEach(model => {
                model.set('isRead', true);
            });

            this.collection.trigger('all-marked-read');
        },

        actionMoveToTrash: function (data) {
            var id = data.id;

            Espo.Ajax
                .postRequest('Email/action/moveToTrash', {
                    id: id
                })
                .then(() => {
                    Espo.Ui.warning(this.translate('Moved to Trash', 'labels', 'Email'));

                    this.collection.trigger('moving-to-trash', id);
                });
        },

        actionRetrieveFromTrash: function (data) {
            var id = data.id;

            Espo.Ajax
                .postRequest('Email/action/retrieveFromTrash', {
                    id: id
                })
                .then(() => {
                    Espo.Ui.warning(this.translate('Retrieved from Trash', 'labels', 'Email'));

                    this.collection.trigger('retrieving-from-trash', id);
                });
        },

        actionMoveToFolder: function (data) {
            var id = data.id;

            this.createView('dialog', 'views/email-folder/modals/select-folder', {}, view => {
                view.render();

                this.listenToOnce(view, 'select', folderId => {
                    this.clearView('dialog');

                    Espo.Ajax
                        .postRequest('Email/action/moveToFolder', {
                            id: id,
                            folderId: folderId
                        })
                        .then(() => {
                            this.collection.fetch().then(() => {
                                Espo.Ui.success(this.translate('Done'));
                            });
                        });
                });
            });
        },

        actionSend: function (data) {
            var id = data.id;

            this.confirm({
                message: this.translate('sendConfirm', 'messages', 'Email'),
                confirmText: this.translate('Send', 'labels', 'Email'),
            }).then(() => {
                var model = this.collection.get(id);

                if (!model) {
                    return;
                }

                Espo.Ui.notify(this.translate('Sending...', 'labels', 'Email'));

                model
                    .save({
                        status: 'Sending',
                    })
                    .then(() => {
                        Espo.Ui.success(this.translate('emailSent', 'messages', 'Email'));

                        if (this.collection.selectedFolderId === 'drafts') {
                            this.removeRecordFromList(id);
                            this.uncheckRecord(id, null, true);
                            this.collection.trigger('draft-sent');
                        }
                    }
                );
            });
        },

    });
});
