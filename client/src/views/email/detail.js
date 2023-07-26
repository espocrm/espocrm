/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2023 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

import DetailView from 'views/detail';
import EmailHelper from 'email-helper';

class EmailDetailView extends DetailView {

    setup() {
        super.setup();

        let status = this.model.get('status');

        if (status === 'Draft') {
            this.menu = {
                'buttons': [],
                'dropdown': [],
                'actions': []
            };
        }
        else {
            this.addMenuItem('buttons', {
                name: 'reply',
                label: 'Reply',
                action: this.getPreferences().get('emailReplyToAllByDefault') ? 'replyToAll' : 'reply',
                style: 'danger',
                className: 'btn-s-wide',
            }, true);

            this.addMenuItem('dropdown', false);

            if (status === 'Archived') {
                if (!this.model.get('parentId')) {
                    this.addMenuItem('dropdown', {
                        label: 'Create Lead',
                        action: 'createLead',
                        acl: 'create',
                        aclScope: 'Lead',
                    });

                    this.addMenuItem('dropdown', {
                        label: 'Create Contact',
                        action: 'createContact',
                        acl: 'create',
                        aclScope: 'Contact',
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
                        text: this.translate('Create Document', 'labels', 'Document'),
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

            let headerView = this.getHeaderView();

            if (headerView) {
                headerView.reRender();
            }
        });

        this.shortcutKeys['Control+Delete'] = e => {
            if ($(e.target).hasClass('note-editable')) {
                return;
            }

            let recordView = /** @type {module:views/email/record/detail} */ this.getRecordView();

            if (!this.model.get('isUsers') || this.model.get('inTrash')) {
                return;
            }

            e.preventDefault();
            e.stopPropagation();

            recordView.actionMoveToTrash();
        };

        this.shortcutKeys['Control+KeyI'] = e => {
            if ($(e.target).hasClass('note-editable')) {
                return;
            }

            let recordView = /** @type {module:views/email/record/detail} */ this.getRecordView();

            if (!this.model.get('isUsers')) {
                return;
            }

            e.preventDefault();
            e.stopPropagation();

            this.model.get('isImportant') ?
                recordView.actionMarkAsNotImportant() :
                recordView.actionMarkAsImportant();
        };

        this.shortcutKeys['Control+KeyM'] = e => {
            if ($(e.target).hasClass('note-editable')) {
                return;
            }

            let recordView = /** @type {module:views/email/record/detail} */ this.getRecordView();

            if (!this.model.get('isUsers')) {
                return;
            }

            e.preventDefault();
            e.stopPropagation();

            recordView.actionMoveToFolder();
        };
    }

    // noinspection JSUnusedGlobalSymbols
    actionCreateLead() {
        let attributes = {};

        let emailHelper = new EmailHelper(
            this.getLanguage(),
            this.getUser(),
            this.getDateTime(),
            this.getAcl()
        );

        let fromString = this.model.get('fromString') || this.model.get('fromName');

        if (fromString) {
            let fromName = emailHelper.parseNameFromStringAddress(fromString);

            if (fromName) {
                let firstName = fromName.split(' ').slice(0, -1).join(' ');
                let lastName = fromName.split(' ').slice(-1).join(' ');

                attributes.firstName = firstName;
                attributes.lastName = lastName;
            }
        }

        if (this.model.get('replyToString')) {
            let str = this.model.get('replyToString');
            let p = (str.split(';'))[0];

            attributes.emailAddress = emailHelper.parseAddressFromStringAddress(p);

            let fromName = emailHelper.parseNameFromStringAddress(p);

            if (fromName) {
                let firstName = fromName.split(' ').slice(0, -1).join(' ');
                let lastName = fromName.split(' ').slice(-1).join(' ');

                attributes.firstName = firstName;
                attributes.lastName = lastName;
            }
        }

        if (!attributes.emailAddress) {
            attributes.emailAddress = this.model.get('from');
        }

        attributes.emailId = this.model.id;

        let viewName = this.getMetadata().get('clientDefs.Lead.modalViews.edit') || 'views/modals/edit';

        Espo.Ui.notify(' ... ');

        this.createView('quickCreate', viewName, {
            scope: 'Lead',
            attributes: attributes,
        }, view => {
            view.render();
            view.notify(false);

            this.listenTo(view, 'before:save', () => {
                this.getRecordView().blockUpdateWebSocket(true);
            });

            this.listenToOnce(view, 'after:save', () => {
                this.model.fetch();
                this.removeMenuItem('createContact');
                this.removeMenuItem('createLead');

                view.close();
            });
        });
    }

    // noinspection JSUnusedGlobalSymbols
    actionCreateCase() {
        let attributes = {};

        let parentId = this.model.get('parentId');
        let parentType = this.model.get('parentType');
        let parentName = this.model.get('parentName');

        let accountId = this.model.get('accountId');
        let accountName = this.model.get('accountName');

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
            else if (parentType === 'Lead') {
                attributes.leadId = parentId;
                attributes.leadName = parentName;
            }
        }

        attributes.emailsIds = [this.model.id];
        attributes.emailId = this.model.id;
        attributes.name = this.model.get('name');
        attributes.description = this.model.get('bodyPlain') || '';

        let viewName = this.getMetadata().get('clientDefs.Case.modalViews.edit') || 'views/modals/edit';

        Espo.Ui.notify(' ... ');

        (new Promise(resolve => {
            if (!(this.model.get('attachmentsIds') || []).length) {
                resolve();

                return;
            }

            Espo.Ajax.postRequest(`Email/${this.model.id}/attachments/copy`, {
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
                    this.getRecordView().blockUpdateWebSocket(true);
                });
            });
        });
    }

    // noinspection JSUnusedGlobalSymbols
    actionCreateTask() {
        let attributes = {};

        attributes.parentId = this.model.get('parentId');
        attributes.parentName = this.model.get('parentName');
        attributes.parentType = this.model.get('parentType');
        attributes.emailId = this.model.id;

        let subject = this.model.get('name');

        attributes.description = '[' + this.translate('Email', 'scopeNames') + ': ' + subject +']' +
            '(#Email/view/' + this.model.id + ')\n';

        let viewName = this.getMetadata().get('clientDefs.Task.modalViews.edit') || 'views/modals/edit';

        Espo.Ui.notify(' ... ');

        this.createView('quickCreate', viewName, {
            scope: 'Task',
            attributes: attributes,
        }, view => {
            let recordView = view.getRecordView();

            let nameFieldView = recordView.getFieldView('name');

            let nameOptionList = [];

            if (nameFieldView && nameFieldView.params.options) {
                nameOptionList = nameOptionList.concat(nameFieldView.params.options);
            }

            nameOptionList.push(this.translate('replyToEmail', 'nameOptions', 'Task'));

            recordView.setFieldOptionList('name', nameOptionList);

            view.render();

            view.notify(false);

            this.listenToOnce(view, 'after:save', () => {
                view.close();

                this.model.fetch();
            });
        });
    }

    // noinspection JSUnusedGlobalSymbols
    actionCreateContact() {
        let attributes = {};

        let emailHelper = new EmailHelper(
            this.getLanguage(),
            this.getUser(),
            this.getDateTime(),
            this.getAcl()
        );

        let fromString = this.model.get('fromString') || this.model.get('fromName');

        if (fromString) {
            let fromName = emailHelper.parseNameFromStringAddress(fromString);

            if (fromName) {
                let firstName = fromName.split(' ').slice(0, -1).join(' ');
                let lastName = fromName.split(' ').slice(-1).join(' ');

                attributes.firstName = firstName;
                attributes.lastName = lastName;
            }
        }

        if (this.model.get('replyToString')) {
            let str = this.model.get('replyToString');
            let p = (str.split(';'))[0];

            attributes.emailAddress = emailHelper.parseAddressFromStringAddress(p);

            let fromName = emailHelper.parseNameFromStringAddress(p);

            if (fromName) {
                let firstName = fromName.split(' ').slice(0, -1).join(' ');
                let lastName = fromName.split(' ').slice(-1).join(' ');

                attributes.firstName = firstName;
                attributes.lastName = lastName;
            }
        }

        if (!attributes.emailAddress) {
            attributes.emailAddress = this.model.get('from');
        }

        attributes.emailId = this.model.id;

        let viewName = this.getMetadata().get('clientDefs.Contact.modalViews.edit') || 'views/modals/edit';

        Espo.Ui.notify(' ... ');

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
                this.getRecordView().blockUpdateWebSocket(true);
            });
        });
    }

    actionReply(data, e, cc) {
        let emailHelper = new EmailHelper(
            this.getLanguage(),
            this.getUser(),
            this.getDateTime(),
            this.getAcl()
        );

        let attributes = emailHelper.getReplyAttributes(this.model, data, cc);

        Espo.Ui.notify(' ... ');

        let viewName = this.getMetadata().get('clientDefs.Email.modalViews.compose') ||
            'views/modals/compose-email';

        this.createView('quickCreate', viewName, {
            attributes: attributes,
            focusForCreate: true,
        }, view => {
            view.render();

            view.notify(false);

            this.listenTo(view, 'after:save', () => {
                this.model.fetch();
            });
        });
    }

    // noinspection JSUnusedGlobalSymbols
    actionReplyToAll(data, e) {
        this.actionReply(data, e, true);
    }

    // noinspection JSUnusedGlobalSymbols
    actionForward() {
        let emailHelper = new EmailHelper(
            this.getLanguage(),
            this.getUser(),
            this.getDateTime(),
            this.getAcl()
        );

        Espo.Ui.notify(' ... ');

        Espo.Ajax
            .postRequest('Email/action/getDuplicateAttributes', {
                id: this.model.id,
            })
            .then(duplicateAttributes => {
                let model = this.model.clone();

                model.set('body', duplicateAttributes.body);

                let attributes = emailHelper.getForwardAttributes(model);

                attributes.attachmentsIds = duplicateAttributes.attachmentsIds;
                attributes.attachmentsNames = duplicateAttributes.attachmentsNames;

                Espo.Ui.notify(' ... ');

                let viewName = this.getMetadata().get('clientDefs.Email.modalViews.compose') ||
                    'views/modals/compose-email';

                this.createView('quickCreate', viewName, {
                    attributes: attributes,
                }, view => {
                    view.render();

                    view.notify(false);
                });
            });
    }

    getHeader() {
        let name = this.model.get('name');

        let isImportant = this.model.get('isImportant');
        let inTrash = this.model.get('inTrash');

        let rootUrl = this.options.rootUrl || this.options.params.rootUrl || '#' + this.scope;

        let headerIconHtml = this.getHeaderIconHtml();

        let $root = $('<a>')
            .attr('href', rootUrl)
            .attr('data-action', 'navigateToRoot')
            .addClass('action')
            .text(
                this.getLanguage().translate(this.model.name, 'scopeNamesPlural')
            );

        if (headerIconHtml) {
            $root = $('<span>')
                .append(headerIconHtml, $root)
                .get(0).innerHTML;
        }

        return this.buildHeaderHtml([
            $root,
            $('<span>')
                .addClass('font-size-flexible title')
                .addClass(isImportant ? 'text-warning' : '')
                .addClass(inTrash ? 'text-muted' : '')
                .text(name),
        ]);
    }

    actionNavigateToRoot(data, event) {
        event.stopPropagation();

        this.getRouter().checkConfirmLeaveOut(() => {
            let rootUrl = this.options.rootUrl || this.options.params.rootUrl || '#' + this.scope;

            let options = {
                isReturn: true,
                isReturnThroughLink: true,
            };

            this.getRouter().navigate(rootUrl, {trigger: false});
            this.getRouter().dispatch(this.scope, null, options);
        });
    }

    // noinspection JSUnusedGlobalSymbols
    actionCreateDocument() {
        let attachmentIdList = this.model.getLinkMultipleIdList('attachments');

        if (!attachmentIdList.length) {
            return;
        }

        let names = this.model.get('attachmentsNames') || {};
        let types = this.model.get('attachmentsTypes') || {};

        let proceed = (id) => {
            let attributes = {};

            if (this.model.get('accountId')) {
                attributes.accountsIds = [this.model.get('accountId')];
                attributes.accountsNames = {};
                attributes.accountsNames[this.model.get('accountId')] = this.model.get('accountName');
            }

            Espo.Ui.notify(' ... ');

            Espo.Ajax.postRequest('Attachment/copy/' + id, {
                relatedType: 'Document',
                field: 'file',
            }).then((attachment) => {
                attributes.fileId = attachment.id;
                attributes.fileName = attachment.name;
                attributes.name = attachment.name;

                let viewName = this.getMetadata().get('clientDefs.Document.modalViews.edit') ||
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

        let dataList = [];

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
        }, view => {
            view.render();

            this.listenToOnce(view, 'select', proceed.bind(this));
        });
    }
}

export default EmailDetailView;
