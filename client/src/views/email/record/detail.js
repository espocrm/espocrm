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

define('views/email/record/detail', 'views/record/detail', function (Dep) {

    return Dep.extend({

        sideView: 'views/email/record/detail-side',

        duplicateAction: false,

        layoutNameConfigure: function () {
            if (!this.model.isNew()) {
                var isRestricted = false;

                if (this.model.get('status') == 'Sent') {
                    isRestricted = true;
                }

                if (this.model.get('status') == 'Archived') {
                    if (this.model.get('createdById') == 'system' || !this.model.get('createdById') || this.model.get('isImported')) {
                        isRestricted = true;
                    }
                }
                if (isRestricted) {
                    this.layoutName += 'Restricted';
                }
                this.isRestricted = isRestricted;
            }
        },

        init: function () {
            Dep.prototype.init.call(this);

            this.layoutNameConfigure();
        },

        setup: function () {
            Dep.prototype.setup.call(this);

            if (this.model.has('isRead') && !this.model.get('isRead')) {
                this.model.set('isRead', true);
            }
            this.listenTo(this.model, 'sync', function () {
                if (!this.model.get('isRead')) {
                    this.model.set('isRead', true);
                }
            }, this);

            if (!(this.model.get('isHtml') && this.model.get('bodyPlain'))) {
                this.listenToOnce(this.model, 'sync', function () {
                    if (this.model.get('isHtml') && this.model.get('bodyPlain')) {
                        this.showActionItem('showBodyPlain');
                    }
                }, this);
            }

            if (this.model.get('isUsers')) {
                this.addDropdownItem({
                    'label': 'Mark as Important',
                    'name': 'markAsImportant',
                    'hidden': this.model.get('isImportant')
                });
                this.addDropdownItem({
                    'label': 'Unmark Importance',
                    'name': 'markAsNotImportant',
                    'hidden': !this.model.get('isImportant')
                });
                this.addDropdownItem({
                    'label': 'Move to Trash',
                    'name': 'moveToTrash',
                    'hidden': this.model.get('inTrash')
                });
                this.addDropdownItem({
                    'label': 'Retrieve from Trash',
                    'name': 'retrieveFromTrash',
                    'hidden': !this.model.get('inTrash')
                });
                this.addDropdownItem({
                    'label': 'Move to Folder',
                    'name': 'moveToFolder'
                });
            }

            this.addDropdownItem({
                label: 'Show Plain Text',
                name: 'showBodyPlain',
                hidden: !(this.model.get('isHtml') && this.model.get('bodyPlain'))
            });

            this.listenTo(this.model, 'change:isImportant', function () {
                if (this.model.get('isImportant')) {
                    this.hideActionItem('markAsImportant');
                    this.showActionItem('markAsNotImportant');
                } else {
                    this.hideActionItem('markAsNotImportant');
                    this.showActionItem('markAsImportant');
                }
            }, this);

            this.listenTo(this.model, 'change:inTrash', function () {
                if (this.model.get('inTrash')) {
                    this.hideActionItem('moveToTrash');
                    this.showActionItem('retrieveFromTrash');
                } else {
                    this.hideActionItem('retrieveFromTrash');
                    this.showActionItem('moveToTrash');
                }
            }, this);

            this.listenTo(this.model, 'reply', function () {
                this.showField('replies');
                this.model.fetch();
            }, this);

            if (this.getUser().isAdmin()) {
                this.addDropdownItem({
                    label: 'View Users',
                    name: 'viewUsers'
                });
            }
        },

        actionMarkAsImportant: function () {
            Espo.Ajax.postRequest('Email/action/markAsImportant', {
                id: this.model.id
            });
            this.model.set('isImportant', true);
        },

        actionMarkAsNotImportant: function () {
            Espo.Ajax.postRequest('Email/action/markAsNotImportant', {
                id: this.model.id
            });
            this.model.set('isImportant', false);
        },

        actionMoveToTrash: function () {
            Espo.Ajax.postRequest('Email/action/moveToTrash', {
                id: this.model.id
            }).then(function () {
                Espo.Ui.warning(this.translate('Moved to Trash', 'labels', 'Email'));
            }.bind(this));
            this.model.set('inTrash', true);
            if (this.model.collection) {
                this.model.collection.trigger('moving-to-trash', this.model.id);
            }
        },

        actionRetrieveFromTrash: function () {
            Espo.Ajax.postRequest('Email/action/retrieveFromTrash', {
                id: this.model.id
            }).then(function () {
                Espo.Ui.warning(this.translate('Retrieved from Trash', 'labels', 'Email'));
            }.bind(this));
            this.model.set('inTrash', false);
            this.model.set('inTrash', true);
            if (this.model.collection) {
                this.model.collection.trigger('retrieving-from-trash', this.model.id);
            }
        },

        actionMoveToFolder: function () {
            this.createView('dialog', 'views/email-folder/modals/select-folder', {}, function (view) {
                view.render();
                this.listenToOnce(view, 'select', function (folderId, folderName) {
                    this.clearView('dialog');
                    this.ajaxPostRequest('Email/action/moveToFolder', {
                        id: this.model.id,
                        folderId: folderId
                    }).then(function () {
                        if (folderId === 'inbox') {
                            folderId = null;
                        }
                        this.model.set('folderId', folderId);
                        Espo.Ui.success(this.translate('Done'));
                    }.bind(this));
                }, this);
            }, this);
        },

        actionShowBodyPlain: function () {
            this.createView('bodyPlain', 'views/email/modals/body-plain', {
                model: this.model
            }, function (view) {
                view.render();
            }.bind(this));
        },

        handleAttachmentField: function () {
            if ((this.model.get('attachmentsIds') || []).length == 0) {
                this.hideField('attachments');
            } else {
                this.showField('attachments');
            }
        },

        handleCcField: function () {
            if (!this.model.get('cc')) {
                this.hideField('cc');
            } else {
                this.showField('cc');
            }
        },

        handleBccField: function () {
            if (!this.model.get('bcc')) {
                this.hideField('bcc');
            } else {
                this.showField('bcc');
            }
        },

        handleRepliesField: function () {
            if ((this.model.get('repliesIds') || []).length == 0) {
                this.hideField('replies');
            } else {
                this.showField('replies');
            }
            if (!this.model.get('repliedId')) {
                this.hideField('replied');
            } else {
                this.showField('replied');
            }
        },

        afterRender: function () {
            Dep.prototype.afterRender.call(this);

            if (this.model.get('status') === 'Draft') {
                this.setFieldReadOnly('dateSent');
            }

            if (this.isRestricted) {
                this.handleAttachmentField();
                this.listenTo(this.model, 'change:attachmentsIds', function () {
                    this.handleAttachmentField();
                }, this);

                this.handleCcField();
                this.listenTo(this.model, 'change:cc', function () {
                    this.handleCcField();
                }, this);
                this.handleBccField();
                this.listenTo(this.model, 'change:bcc', function () {
                    this.handleBccField();
                }, this);

                this.handleRepliesField();
                this.listenTo(this.model, 'change:repliesIds', function () {
                    this.handleRepliesField();
                }, this);
            }
        },

        send: function () {
            var model = this.model;
            model.set('status', 'Sending');

            var afterSend = function () {
                Espo.Ui.success(this.translate('emailSent', 'messages', 'Email'));
                model.trigger('after:send');
                this.trigger('after:send');
            };

            this.once('after:save', afterSend, this);
            this.once('cancel:save', function () {
                this.off('after:save', afterSend);
            }, this);

            this.once('before:save', function () {
                Espo.Ui.notify(this.translate('Sending...', 'labels', 'Email'));
            }, this);

            this.save();
        },

        exitAfterDelete: function () {
            var folderId = ((this.collection || {}).data || {}).folderId || null;

            if (folderId === 'inbox') {
                folderId = null;
            }

            var options = {
                isReturn: true,
                isReturnThroughLink: false,
                folder: folderId
            };
            var url = '#' + this.scope;
            var action = null;
            if (folderId) {
                action = 'list';
                url += '/list/folder=' + folderId;
            }
            this.getRouter().dispatch(this.scope, action, options);
            this.getRouter().navigate(url, {trigger: false});

            return true;
        },

        actionViewUsers: function (data) {
            var viewName =
                this.getMetadata().get(['clientDefs', this.model.name, 'relationshipPanels', 'users', 'viewModalView']) ||
                this.getMetadata().get(['clientDefs', 'User', 'modalViews', 'relatedList']) ||
                'views/modals/related-list';

            var options = {
                model: this.model,
                link: 'users',
                scope: 'User',
                filtersDisabled: true,
                url: this.model.entityType + '/' + this.model.id + '/users',
                createDisabled: true,
                selectDisabled: !this.getUser().isAdmin(),
                rowActionsView: 'views/record/row-actions/relationship-view-and-unlink',
            };

            if (data.viewOptions) {
                for (var item in data.viewOptions) {
                    options[item] = data.viewOptions[item];
                }
            }

            Espo.Ui.notify(this.translate('loading', 'messages'));
            this.createView('modalRelatedList', viewName, options, function (view) {
                Espo.Ui.notify(false);
                view.render();

                this.listenTo(view, 'action', function (action, data, e) {
                    var method = 'action' + Espo.Utils.upperCaseFirst(action);
                    if (typeof this[method] == 'function') {
                        this[method](data, e);
                        e.preventDefault();
                    }
                }, this);

                this.listenToOnce(view, 'close', function () {
                    this.clearView('modalRelatedList');
                }, this);
            });
        },

    });
});
