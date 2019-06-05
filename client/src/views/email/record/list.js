/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

Espo.define('views/email/record/list', 'views/record/list', function (Dep) {

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

            this.listenTo(this.collection, 'moving-to-trash', function (id) {
                var model = this.collection.get(id);
                if (model) {
                    model.set('inTrash', true);
                }

                if (this.collection.data.folderId !== 'trash' && this.collection.data.folderId !== 'all') {
                    this.removeRecordFromList(id);
                }
            }, this);

            this.listenTo(this.collection, 'retrieving-from-trash', function (id) {
                var model = this.collection.get(id);
                if (model) {
                    model.set('inTrash', false);
                }

                if (this.collection.data.folderId === 'trash') {
                    this.removeRecordFromList(id);
                }
            }, this);
        },

        massActionMarkAsRead: function () {
            var ids = [];
            for (var i in this.checkedList) {
                ids.push(this.checkedList[i]);
            }
            $.ajax({
                url: 'Email/action/markAsRead',
                type: 'POST',
                data: JSON.stringify({
                    ids: ids
                })
            });

            ids.forEach(function (id) {
                var model = this.collection.get(id);
                if (model) {
                    model.set('isRead', true);
                }
            }, this);
        },

        massActionMarkAsNotRead: function () {
            var ids = [];
            for (var i in this.checkedList) {
                ids.push(this.checkedList[i]);
            }
            $.ajax({
                url: 'Email/action/markAsNotRead',
                type: 'POST',
                data: JSON.stringify({
                    ids: ids
                })
            });

            ids.forEach(function (id) {
                var model = this.collection.get(id);
                if (model) {
                    model.set('isRead', false);
                }
            }, this);
        },

        massActionMarkAsImportant: function () {
            var ids = [];
            for (var i in this.checkedList) {
                ids.push(this.checkedList[i]);
            }
            $.ajax({
                url: 'Email/action/markAsImportant',
                type: 'POST',
                data: JSON.stringify({
                    ids: ids
                })
            });
            ids.forEach(function (id) {
                var model = this.collection.get(id);
                if (model) {
                    model.set('isImportant', true);
                }
            }, this);
        },

        massActionMarkAsNotImportant: function () {
            var ids = [];
            for (var i in this.checkedList) {
                ids.push(this.checkedList[i]);
            }
            $.ajax({
                url: 'Email/action/markAsNotImportant',
                type: 'POST',
                data: JSON.stringify({
                    ids: ids
                })
            });
            ids.forEach(function (id) {
                var model = this.collection.get(id);
                if (model) {
                    model.set('isImportant', false);
                }
            }, this);
        },

        massActionMoveToTrash: function () {
            var ids = [];
            for (var i in this.checkedList) {
                ids.push(this.checkedList[i]);
            }

            this.ajaxPostRequest('Email/action/moveToTrash', {
                ids: ids
            }).then(function () {
                Espo.Ui.success(this.translate('Done'));
            }.bind(this));

            if (this.collection.data.folderId === 'trash') {
                return;
            }

            ids.forEach(function (id) {
                this.collection.trigger('moving-to-trash', id);
            }, this);
        },

        massActionRetrieveFromTrash: function () {
            var ids = [];
            for (var i in this.checkedList) {
                ids.push(this.checkedList[i]);
            }

            this.ajaxPostRequest('Email/action/retrieveFromTrash', {
                ids: ids
            }).then(function () {
                Espo.Ui.success(this.translate('Done'));
            }.bind(this));

            if (this.collection.data.folderId !== 'trash') {
                return;
            }

            ids.forEach(function (id) {
                this.collection.trigger('retrieving-from-trash', id);
            }, this);
        },

        massActionMoveToFolder: function () {
            var ids = [];
            for (var i in this.checkedList) {
                ids.push(this.checkedList[i]);
            }

            this.createView('dialog', 'views/email-folder/modals/select-folder', {}, function (view) {
                view.render();
                this.listenToOnce(view, 'select', function (folderId) {
                    this.clearView('dialog');
                    this.ajaxPostRequest('Email/action/moveToFolder', {
                        ids: ids,
                        folderId: folderId
                    }).then(function () {
                        this.collection.fetch().then(function () {
                            Espo.Ui.success(this.translate('Done'));
                        }.bind(this));
                    }.bind(this));
                }, this);
            }, this);
        },

        actionMarkAsImportant: function (data) {
            data = data || {};
            var id = data.id;
            $.ajax({
                url: 'Email/action/markAsImportant',
                type: 'POST',
                data: JSON.stringify({
                    id: id
                })
            });

            var model = this.collection.get(id);
            if (model) {
                model.set('isImportant', true);
            }
        },

        actionMarkAsNotImportant: function (data) {
            data = data || {};
            var id = data.id;
            $.ajax({
                url: 'Email/action/markAsNotImportant',
                type: 'POST',
                data: JSON.stringify({
                    id: id
                })
            });


            var model = this.collection.get(id);
            if (model) {
                model.set('isImportant', false);
            }
        },

        actionMarkAllAsRead: function () {
            $.ajax({
                url: 'Email/action/markAllAsRead',
                type: 'POST'
            });

            this.collection.forEach(function (model) {
                model.set('isRead', true);
            }, this);

            this.collection.trigger('all-marked-read');
        },

        actionMoveToTrash: function (data) {
            var id = data.id;
            this.ajaxPostRequest('Email/action/moveToTrash', {
                id: id
            }).then(function () {
                Espo.Ui.warning(this.translate('Moved to Trash', 'labels', 'Email'));
                this.collection.trigger('moving-to-trash', id);
            }.bind(this));
        },

        actionRetrieveFromTrash: function (data) {
            var id = data.id;
            this.ajaxPostRequest('Email/action/retrieveFromTrash', {
                id: id
            }).then(function () {
                Espo.Ui.warning(this.translate('Retrieved from Trash', 'labels', 'Email'));
                this.collection.trigger('retrieving-from-trash', id);
            }.bind(this));
        },

        actionMoveToFolder: function (data) {
            var id = data.id;

            this.createView('dialog', 'views/email-folder/modals/select-folder', {}, function (view) {
                view.render();
                this.listenToOnce(view, 'select', function (folderId) {
                    this.clearView('dialog');
                    this.ajaxPostRequest('Email/action/moveToFolder', {
                        id: id,
                        folderId: folderId
                    }).then(function () {
                        this.collection.fetch().then(function () {
                            Espo.Ui.success(this.translate('Done'));
                        }.bind(this));
                    }.bind(this));
                }, this);
            }, this);
        }

    });
});

