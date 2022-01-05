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

define('views/email/detail', ['views/detail', 'email-helper'], function (Dep, EmailHelper) {

    return Dep.extend({

        setup: function () {
            Dep.prototype.setup.call(this);

            var status = this.model.get('status');

            if (status === 'Draft') {
                this.backedMenu = this.menu;

                this.menu = {
                    'buttons': [
                        {
                           "label": "Send",
                           "action": "send",
                           "style": "danger",
                           "acl": "edit"
                        }
                    ],
                    'dropdown': [],
                    'actions': []
                };
            }
            else {
                this.addMenuItem('buttons', {
                    name: 'reply',
                    label: 'Reply',
                    action: this.getPreferences().get('emailReplyToAllByDefault') ? 'replyToAll' : 'reply',
                    style: 'danger'
                }, true);

                this.addMenuItem('dropdown', false);

                if (status === 'Archived') {
                    if (!this.model.get('parentId')) {
                        this.addMenuItem('dropdown', {
                            label: 'Create Lead',
                            action: 'createLead',
                            acl: 'create',
                            aclScope: 'Lead'
                        });

                        this.addMenuItem('dropdown', {
                            label: 'Create Contact',
                            action: 'createContact',
                            acl: 'create',
                            aclScope: 'Contact'
                        });
                    }
                }

                this.addMenuItem('dropdown', {
                    label: 'Create Task',
                    action: 'createTask',
                    acl: 'create',
                    aclScope: 'Task'
                });

                if (this.model.get('parentType') !== 'Case' || !this.model.get('parentId')) {
                    this.addMenuItem('dropdown', {
                        label: 'Create Case',
                        action: 'createCase',
                        acl: 'create',
                        aclScope: 'Case'
                    });
                }

                if (this.getAcl().checkScope('Document', 'create')) {
                    if (
                        this.model.get('attachmentsIds') === undefined ||
                        this.model.getLinkMultipleIdList('attachments').length
                    ) {
                        this.addMenuItem('dropdown', {
                            html: this.translate('Create Document', 'labels', 'Document'),
                            action: 'createDocument',
                            acl: 'create',
                            aclScope: 'Document',
                            hidden: this.model.get('attachmentsIds') === undefined,
                        });

                        if (this.model.get('attachmentsIds') === undefined) {
                            this.listenToOnce(this.model, 'sync', () => {
                                if (this.model.getLinkMultipleIdList('attachments').length) {
                                    this.showHeaderActionItem('createDocument');
                                }
                            });
                        }
                    }
                }
            }

            this.listenTo(this.model, 'change', () => {
                if (!this.isRendered()) {
                    return;
                }

                if (!this.model.hasChanged('isImportant') && !this.model.hasChanged('inTrash')) {
                    return;
                }

                var headerView = this.getView('header');

                if (headerView) {
                    headerView.reRender();
                }
            });
        },

        actionCreateLead: function () {
            var attributes = {};

            var emailHelper = new EmailHelper(
                this.getLanguage(),
                this.getUser(),
                this.getDateTime(),
                this.getAcl()
            );

            var fromString = this.model.get('fromString') || this.model.get('fromName');

            if (fromString) {
                var fromName = emailHelper.parseNameFromStringAddress(fromString);

                if (fromName) {
                    var firstName = fromName.split(' ').slice(0, -1).join(' ');
                    var lastName = fromName.split(' ').slice(-1).join(' ');

                    attributes.firstName = firstName;
                    attributes.lastName = lastName;
                }
            }

            if (this.model.get('replyToString')) {
                var str = this.model.get('replyToString');
                var p = (str.split(';'))[0];

                attributes.emailAddress = emailHelper.parseAddressFromStringAddress(p);

                var fromName = emailHelper.parseNameFromStringAddress(p);

                if (fromName) {
                    var firstName = fromName.split(' ').slice(0, -1).join(' ');
                    var lastName = fromName.split(' ').slice(-1).join(' ');

                    attributes.firstName = firstName;
                    attributes.lastName = lastName;
                }
            }

            if (!attributes.emailAddress) {
                attributes.emailAddress = this.model.get('from');
            }
            attributes.emailId = this.model.id;

            var viewName = this.getMetadata().get('clientDefs.Lead.modalViews.edit') || 'views/modals/edit';

            this.notify('Loading...');

            this.createView('quickCreate', viewName, {
                scope: 'Lead',
                attributes: attributes,
            }, (view) => {
                view.render();
                view.notify(false);

                this.listenTo(view, 'before:save', () => {
                    this.getView('record').blockUpdateWebSocket(true);
                });

                this.listenToOnce(view, 'after:save', () => {
                    this.model.fetch();
                    this.removeMenuItem('createContact');
                    this.removeMenuItem('createLead');

                    view.close();
                });
            });
        },

        actionCreateCase: function () {
            var attributes = {};

            var parentId = this.model.get('parentId');
            var parentType = this.model.get('parentType');
            var parentName = this.model.get('parentName');

            var accountId = this.model.get('accountId');
            var accountName = this.model.get('accountName');

            if (parentId) {
                if (parentType === 'Account') {
                    attributes.accountId = parentId;
                    attributes.accountName = parentName;
                }
                else if (parentType === 'Contact') {
                    attributes.contactId = parentId;
                    attributes.contactName = parentName;

                    attributes.contactsIds = [parentId];
                    attributes.contactsNames = {};
                    attributes.contactsNames[parentId] = parentName;

                    if (accountId) {
                        attributes.accountId = accountId;
                        attributes.accountName = accountName || accountId;
                    }
                }
            }

            attributes.emailsIds = [this.model.id];
            attributes.emailId = this.model.id;
            attributes.name = this.model.get('name');
            attributes.description = this.model.get('bodyPlain') || '';

            var viewName = this.getMetadata().get('clientDefs.Case.modalViews.edit') || 'views/modals/edit';

            Espo.Ui.notify(this.translate('loading', 'messsages'));

            (new Promise(resolve => {
                if (!(this.model.get('attachmentsIds') || []).length) {
                    resolve();

                    return;
                }

                this.ajaxPostRequest('Email/action/getCopiedAttachments', {
                    id: this.model.id,
                    parentType: 'Case',
                    field: 'attachments',
                }).then(data => {
                    attributes.attachmentsIds = data.ids;
                    attributes.attachmentsNames = data.names;

                    resolve();
                });
            })).then(() => {
                this.createView('quickCreate', viewName, {
                    scope: 'Case',
                    attributes: attributes,
                }, view => {
                    view.render();

                    Espo.Ui.notify(false);

                    this.listenToOnce(view, 'after:save', () => {
                        this.model.fetch();
                        this.removeMenuItem('createCase');

                        view.close();
                    });

                    this.listenTo(view, 'before:save', () => {
                        this.getView('record').blockUpdateWebSocket(true);
                    });
                });
            });
        },

        actionCreateTask: function () {
            var attributes = {};

            attributes.parentId = this.model.get('parentId');
            attributes.parentName = this.model.get('parentName');
            attributes.parentType = this.model.get('parentType');

            attributes.description = '[' + this.model.get('name') + '](#Email/view/' + this.model.id + ')';

            attributes.name = this.translate('Email', 'scopeNames') + ': ' + this.model.get('name');

            var viewName = this.getMetadata().get('clientDefs.Task.modalViews.edit') || 'views/modals/edit';

            this.notify('Loading...');
            this.createView('quickCreate', viewName, {
                scope: 'Task',
                attributes: attributes,
            }, (view) => {
                view.render();
                view.notify(false);

                this.listenToOnce(view, 'after:save', () => {
                    view.close();
                });
            });
        },

        actionCreateContact: function () {
            var attributes = {};

            var emailHelper = new EmailHelper(
                this.getLanguage(),
                this.getUser(),
                this.getDateTime(),
                this.getAcl()
            );

            var fromString = this.model.get('fromString') || this.model.get('fromName');

            if (fromString) {
                var fromName = emailHelper.parseNameFromStringAddress(fromString);

                if (fromName) {
                    var firstName = fromName.split(' ').slice(0, -1).join(' ');
                    var lastName = fromName.split(' ').slice(-1).join(' ');

                    attributes.firstName = firstName;
                    attributes.lastName = lastName;
                }
            }

            if (this.model.get('replyToString')) {
                var str = this.model.get('replyToString');
                var p = (str.split(';'))[0];

                attributes.emailAddress = emailHelper.parseAddressFromStringAddress(p);

                var fromName = emailHelper.parseNameFromStringAddress(p);

                if (fromName) {
                    var firstName = fromName.split(' ').slice(0, -1).join(' ');
                    var lastName = fromName.split(' ').slice(-1).join(' ');

                    attributes.firstName = firstName;
                    attributes.lastName = lastName;
                }
            }

            if (!attributes.emailAddress) {
                attributes.emailAddress = this.model.get('from');
            }

            attributes.emailId = this.model.id;

            var viewName = this.getMetadata().get('clientDefs.Contact.modalViews.edit') || 'views/modals/edit';

            this.notify('Loading...');

            this.createView('quickCreate', viewName, {
                scope: 'Contact',
                attributes: attributes,
            }, (view) => {
                view.render();

                view.notify(false);

                this.listenToOnce(view, 'after:save', () => {
                    this.model.fetch();
                    this.removeMenuItem('createContact');
                    this.removeMenuItem('createLead');

                    view.close();
                });

                this.listenTo(view, 'before:save', () => {
                    this.getView('record').blockUpdateWebSocket(true);
                });
            });
        },

        actionSend: function () {
            var recordView = this.getView('record');

            var $send = this.$el.find('.header-buttons [data-action="send"]');

            $send.addClass('disabled');

            this.listenToOnce(recordView, 'after:send', () => {
                this.model.set('status', 'Sent');

                $send.remove();

                this.menu = this.backedMenu;

                if (recordView.mode !== 'detail') {
                    recordView.setDetailMode();
                    recordView.setFieldReadOnly('dateSent');
                    recordView.setFieldReadOnly('name');
                    recordView.setFieldReadOnly('attachments');
                    recordView.setFieldReadOnly('isHtml');
                    recordView.setFieldReadOnly('from');
                    recordView.setFieldReadOnly('to');
                    recordView.setFieldReadOnly('cc');
                    recordView.setFieldReadOnly('bcc');
                }
            });

            this.listenToOnce(recordView, 'cancel:save', () => {
                $send.removeClass('disabled');
            });

            recordView.send();
        },

        actionReply: function (data, e, cc) {
            var emailHelper = new EmailHelper(
                this.getLanguage(),
                this.getUser(),
                this.getDateTime(),
                this.getAcl()
            );

            var attributes = emailHelper.getReplyAttributes(this.model, data, cc);

            this.notify('Loading...');

            var viewName = this.getMetadata().get('clientDefs.Email.modalViews.compose') ||
                'views/modals/compose-email';

            this.createView('quickCreate', viewName, {
                attributes: attributes,
            }, (view) => {
                view.render();

                view.notify(false);

                this.listenToOnce(view, 'after:save', () => {
                    this.model.trigger('reply');
                });
            });
        },

        actionReplyToAll: function (data, e) {
            this.actionReply(data, e, true);
        },

        actionForward: function (data, cc) {
            var emailHelper = new EmailHelper(
                this.getLanguage(),
                this.getUser(),
                this.getDateTime(),
                this.getAcl()
            );

            var attributes = emailHelper.getForwardAttributes(this.model, data, cc);

            this.notify('Loading...');

            Espo.Ajax
                .postRequest('Email/action/getCopiedAttachments', {
                    id: this.model.id,
                })
                .then(() => {
                    attributes['attachmentsIds'] = data.ids;
                    attributes['attachmentsNames'] = data.names;

                    this.notify('Loading...');

                    var viewName = this.getMetadata().get('clientDefs.Email.modalViews.compose') ||
                        'views/modals/compose-email';

                    this.createView('quickCreate', viewName, {
                        attributes: attributes,
                    }, view => {
                        view.render();

                        view.notify(false);
                    });
                });
        },

        getHeader: function () {
            var name = Handlebars.Utils.escapeExpression(this.model.get('name'));
            var nameHtml = '<span class="font-size-flexible title">' + name + '</span>';

            var classPart = '';

            if (this.model.get('isImportant')) {
                classPart += ' text-warning';
            }

            if (this.model.get('inTrash')) {
                classPart += ' text-muted';
            }

            nameHtml = '<span class="font-size-flexible title'+classPart+'">' + name + '</span>';

            var rootUrl = this.options.rootUrl || this.options.params.rootUrl || '#' + this.scope;

            var headerIconHtml = this.getHeaderIconHtml();

            return this.buildHeaderHtml([
                headerIconHtml + '<a href="' + rootUrl + '" class="action" data-action="navigateToRoot">' +
                    this.getLanguage().translate(this.model.name, 'scopeNamesPlural') + '</a>',
                nameHtml
            ]);
        },

        actionNavigateToRoot: function (data, e) {
            e.stopPropagation();

            this.getRouter().checkConfirmLeaveOut(() => {
                var rootUrl = this.options.rootUrl || this.options.params.rootUrl || '#' + this.scope;

                var options = {
                    isReturn: true,
                    isReturnThroughLink: true
                };

                this.getRouter().navigate(rootUrl, {trigger: false});

                this.getRouter().dispatch(this.scope, null, options);
            });
        },

        actionCreateDocument: function () {
            var attachmentIdList = this.model.getLinkMultipleIdList('attachments');

            if (!attachmentIdList.length) {
                return;
            }

            var names = this.model.get('attachmentsNames') || {};
            var types = this.model.get('attachmentsTypes') || {};

            var proceed = (id) => {
                var name = names[id] || id;
                var type = types[id];

                var attributes = {};

                if (this.model.get('accountId')) {
                    attributes.accountsIds = [this.model.get('accountId')];
                    attributes.accountsNames = {};
                    attributes.accountsNames[this.model.get('accountId')] = this.model.get('accountName');
                }

                Espo.Ui.notify(this.translate('loading', 'messages'))

                this.ajaxPostRequest('Attachment/action/getCopiedAttachment', {
                    id: id,
                    relatedType: 'Document',
                    field: 'file',
                }).then((attachment) => {
                    attributes.fileId = attachment.id;
                    attributes.fileName = attachment.name;
                    attributes.name = attachment.name;

                    var viewName = this.getMetadata().get('clientDefs.Document.modalViews.edit') ||
                        'views/modals/edit';

                    this.createView('quickCreate', viewName, {
                        scope: 'Document',
                        attributes: attributes,
                    }, (view) => {
                        view.render();

                        Espo.Ui.notify(false);

                        this.listenToOnce(view, 'after:save', () => {
                            view.close();
                        });
                    });
                });
            };

            if (attachmentIdList.length === 1) {
                proceed(attachmentIdList[0]);

                return;
            }

            var dataList = [];

            attachmentIdList.forEach((id) => {
                dataList.push({
                    id: id,
                    name: names[id] || id,
                    type: types[id],
                });
            });

            this.createView('dialog', 'views/attachment/modals/select-one', {
                dataList: dataList,
                fieldLabel: this.translate('attachments', 'fields', 'Email'),
            }, (view) => {
                view.render();

                this.listenToOnce(view, 'select', proceed.bind(this));
            });
        },
    });
});
