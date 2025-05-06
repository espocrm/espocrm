/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
 * Website: https://www.espocrm.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU Affero General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU Affero General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

import DetailView from 'views/detail';
import EmailHelper from 'email-helper';
import RecordModal from 'helpers/record-modal';
import SelectOneAttachmentModalView from 'views/attachment/modals/select-one';

class EmailDetailView extends DetailView {

    setup() {
        super.setup();

        const status = this.model.get('status');

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

        this.listenTo(this.model, 'change:isImportant change:inTrash change:inArchive change:groupStatusFolder', () => {
            if (!this.isRendered()) {
                return;
            }

            const headerView = this.getHeaderView();

            if (headerView) {
                headerView.reRender();
            }
        });

        this.shortcutKeys['Control+Backspace'] = e => {
            if ($(e.target).hasClass('note-editable')) {
                return;
            }

            const recordView = /** @type {module:views/email/record/detail} */ this.getRecordView();

            if (!this.model.get('isUsers') || this.model.get('inArchive')) {
                return;
            }

            e.preventDefault();
            e.stopPropagation();

            recordView.actionMoveToArchive();
        };

        this.shortcutKeys['Control+Delete'] = e => {
            if ($(e.target).hasClass('note-editable')) {
                return;
            }

            const recordView = /** @type {module:views/email/record/detail} */ this.getRecordView();

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

            const recordView = /** @type {module:views/email/record/detail} */ this.getRecordView();

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

            const recordView = /** @type {module:views/email/record/detail} */ this.getRecordView();

            if (!this.model.get('isUsers')) {
                return;
            }

            e.preventDefault();
            e.stopPropagation();

            recordView.actionMoveToFolder();
        };
    }

    // noinspection JSUnusedGlobalSymbols
    async actionCreateLead() {
        const attributes = {};

        const emailHelper = new EmailHelper();

        const fromString = this.model.get('fromString') || this.model.get('fromName');

        if (fromString) {
            const fromName = emailHelper.parseNameFromStringAddress(fromString);

            if (fromName) {
                const firstName = fromName.split(' ').slice(0, -1).join(' ');
                const lastName = fromName.split(' ').slice(-1).join(' ');

                attributes.firstName = firstName;
                attributes.lastName = lastName;
            }
        }

        if (this.model.get('replyToString')) {
            const str = this.model.get('replyToString');
            const p = (str.split(';'))[0];

            attributes.emailAddress = emailHelper.parseAddressFromStringAddress(p);

            const fromName = emailHelper.parseNameFromStringAddress(p);

            if (fromName) {
                const firstName = fromName.split(' ').slice(0, -1).join(' ');
                const lastName = fromName.split(' ').slice(-1).join(' ');

                attributes.firstName = firstName;
                attributes.lastName = lastName;
            }
        }

        if (!attributes.emailAddress) {
            attributes.emailAddress = this.model.get('from');
        }

        attributes.originalEmailId = this.model.id;

        const helper = new RecordModal();

        const modalView = await helper.showCreate(this, {
            entityType: 'Lead',
            attributes: attributes,
            afterSave: () => {
                this.model.fetch();

                this.removeMenuItem('createContact');
                this.removeMenuItem('createLead');
            },
        })

        this.listenTo(modalView, 'before:save', () => {
            this.getRecordView().blockUpdateWebSocket(true);
        });
    }

    // noinspection JSUnusedGlobalSymbols
    async actionCreateCase() {
        const attributes = {};

        const parentId = this.model.get('parentId');
        const parentType = this.model.get('parentType');
        const parentName = this.model.get('parentName');

        const accountId = this.model.get('accountId');
        const accountName = this.model.get('accountName');

        if (parentId) {
            if (parentType === 'Account') {
                attributes.accountId = parentId;
                attributes.accountName = parentName;
            } else if (parentType === 'Contact') {
                attributes.contactId = parentId;
                attributes.contactName = parentName;

                attributes.contactsIds = [parentId];
                attributes.contactsNames = {};
                attributes.contactsNames[parentId] = parentName;

                if (accountId) {
                    attributes.accountId = accountId;
                    attributes.accountName = accountName || accountId;
                }
            } else if (parentType === 'Lead') {
                attributes.leadId = parentId;
                attributes.leadName = parentName;
            }
        }

        attributes.originalEmailId = this.model.id;
        attributes.name = this.model.get('name');
        attributes.description = this.model.get('bodyPlain') || '';

        const attachmentIds = this.model.get('attachmentsIds') || [];

        Espo.Ui.notifyWait();

        if (attachmentIds.length) {
            /** @type {Record} data */
            const data = await Espo.Ajax.postRequest(`Email/${this.model.id}/attachments/copy`, {
                parentType: 'Case',
                field: 'attachments',
            });

            attributes.attachmentsIds = data.ids;
            attributes.attachmentsNames = data.names;
        }

        const helper = new RecordModal();

        const modalView = await helper.showCreate(this, {
            entityType: 'Case',
            attributes: attributes,
            afterSave: () => {
                this.model.fetch();

                this.removeMenuItem('createCase');
            },
        });

        this.listenTo(modalView, 'before:save', () => this.getRecordView().blockUpdateWebSocket(true));
    }

    // noinspection JSUnusedGlobalSymbols
    actionCreateTask() {
        const attributes = {};

        attributes.parentId = this.model.get('parentId');
        attributes.parentName = this.model.get('parentName');
        attributes.parentType = this.model.get('parentType');
        attributes.originalEmailId = this.model.id;

        const subject = this.model.attributes.name;

        attributes.description =
            `[${this.translate('Email', 'scopeNames')}: ${subject}](#Email/view/${this.model.id})\n`;

        const fullFormUrl = `#Task/create?emailId=${attributes.originalEmailId}`;

        const helper = new RecordModal();

        helper.showCreate(this, {
            entityType: 'Task',
            attributes: attributes,
            fullFormUrl: fullFormUrl,
            afterSave: () => {
                this.model.fetch();
            },
            beforeRender: view => {
                const nameFieldView = view.getRecordView().getFieldView('name');

                const nameOptionList = [];

                if (nameFieldView && nameFieldView.params.options) {
                    nameOptionList.push(...nameFieldView.params.options);
                }

                nameOptionList.push(this.translate('replyToEmail', 'nameOptions', 'Task'));

                view.getRecordView().setFieldOptionList('name', nameOptionList);
            },
        });
    }

    // noinspection JSUnusedGlobalSymbols
    async actionCreateContact() {
        const attributes = {};

        const emailHelper = new EmailHelper();

        const fromString = this.model.get('fromString') || this.model.get('fromName');

        if (fromString) {
            const fromName = emailHelper.parseNameFromStringAddress(fromString);

            if (fromName) {
                const firstName = fromName.split(' ').slice(0, -1).join(' ');
                const lastName = fromName.split(' ').slice(-1).join(' ');

                attributes.firstName = firstName;
                attributes.lastName = lastName;
            }
        }

        if (this.model.get('replyToString')) {
            const str = this.model.get('replyToString');
            const p = (str.split(';'))[0];

            attributes.emailAddress = emailHelper.parseAddressFromStringAddress(p);

            const fromName = emailHelper.parseNameFromStringAddress(p);

            if (fromName) {
                const firstName = fromName.split(' ').slice(0, -1).join(' ');
                const lastName = fromName.split(' ').slice(-1).join(' ');

                attributes.firstName = firstName;
                attributes.lastName = lastName;
            }
        }

        if (!attributes.emailAddress) {
            attributes.emailAddress = this.model.get('from');
        }

        attributes.originalEmailId = this.model.id;

        const helper = new RecordModal();

        const modalView = await helper.showCreate(this, {
            entityType: 'Contact',
            attributes: attributes,
            afterSave: () => {
                this.model.fetch();

                this.removeMenuItem('createContact');
                this.removeMenuItem('createLead');
            },
        })

        this.listenTo(modalView, 'before:save', () => {
            this.getRecordView().blockUpdateWebSocket(true);
        });
    }

    actionReply(data, e, cc) {
        const emailHelper = new EmailHelper();

        const attributes = emailHelper.getReplyAttributes(this.model, data, cc);

        Espo.Ui.notifyWait();

        const viewName = this.getMetadata().get('clientDefs.Email.modalViews.compose') ||
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
        const emailHelper = new EmailHelper();

        Espo.Ui.notifyWait();

        Espo.Ajax
            .postRequest('Email/action/getDuplicateAttributes', {
                id: this.model.id,
            })
            .then(duplicateAttributes => {
                const model = this.model.clone();

                model.set('body', duplicateAttributes.body);

                const attributes = emailHelper.getForwardAttributes(model);

                attributes.attachmentsIds = duplicateAttributes.attachmentsIds;
                attributes.attachmentsNames = duplicateAttributes.attachmentsNames;

                Espo.Ui.notifyWait();

                const viewName = this.getMetadata().get('clientDefs.Email.modalViews.compose') ||
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
        const name = this.model.attributes.name;

        const isImportant = this.model.attributes.isImportant;

        const inTrash = this.model.attributes.groupFolderId ?
            this.model.attributes.groupStatusFolder === 'Trash' :
            this.model.attributes.inTrash;

        const inArchive = this.model.attributes.groupFolderId ?
            this.model.attributes.groupStatusFolder === 'Archive' :
            this.model.attributes.inArchive;

        const scopeLabel = this.getLanguage().translate(this.scope, 'scopeNamesPlural');

        let root = document.createElement('span');
        root.text = scopeLabel;
        root.style.userSelect = 'none';

        if (!this.rootLinkDisabled) {
            const a = document.createElement('a');
            a.href = this.rootUrl;
            a.classList.add('action');
            a.dataset.action = 'navigateToRoot';
            a.text = scopeLabel;

            root = document.createElement('span');
            root.style.userSelect = 'none';
            root.append(a);
        }

        const iconHtml = this.getHeaderIconHtml();

        if (iconHtml) {
            root.insertAdjacentHTML('afterbegin', iconHtml);
        }

        let styleClass = null;

        if (isImportant) {
            styleClass = 'text-warning'
        } else if (inTrash) {
            styleClass = 'text-muted';
        } else if (inArchive) {
            styleClass = 'text-info';
        }

        const title = document.createElement('span');
        title.classList.add('font-size-flexible', 'title')
        title.textContent = name;

        if (styleClass) {
            title.classList.add(styleClass);
        }

        if (this.getRecordMode() === 'detail') {
            title.title = this.translate('clickToRefresh', 'messages');
            title.dataset.action = 'fullRefresh';
            title.style.cursor = 'pointer';
        }

        return this.buildHeaderHtml([
            root,
            title,
        ]);
    }

    actionNavigateToRoot(data, event) {
        event.stopPropagation();

        this.getRouter().checkConfirmLeaveOut(() => {
            const rootUrl = this.options.rootUrl || this.options.params.rootUrl || '#' + this.scope;

            const options = {
                isReturn: true,
                isReturnThroughLink: true,
            };

            this.getRouter().navigate(rootUrl, {trigger: false});
            this.getRouter().dispatch(this.scope, null, options);
        });
    }

    // noinspection JSUnusedGlobalSymbols
    actionCreateDocument() {
        const attachmentIdList = this.model.getLinkMultipleIdList('attachments');

        if (!attachmentIdList.length) {
            return;
        }

        const names = this.model.get('attachmentsNames') || {};
        const types = this.model.get('attachmentsTypes') || {};

        const proceed = async id => {
            const attributes = {};

            if (this.model.get('accountId')) {
                attributes.accountsIds = [this.model.get('accountId')];
                attributes.accountsNames = {};
                attributes.accountsNames[this.model.get('accountId')] = this.model.get('accountName');
            }

            Espo.Ui.notifyWait();

            const attachment = await Espo.Ajax.postRequest(`Attachment/copy/${id}`, {
                relatedType: 'Document',
                field: 'file',
            })

            attributes.fileId = attachment.id;
            attributes.fileName = attachment.name;
            attributes.name = attachment.name;

            const helper = new RecordModal();

            await helper.showCreate(this, {
                entityType: 'Document',
                attributes: attributes,
            });
        };

        if (attachmentIdList.length === 1) {
            proceed(attachmentIdList[0]);

            return;
        }

        const dataList = [];

        attachmentIdList.forEach((id) => {
            dataList.push({
                id: id,
                name: names[id] || id,
                type: types[id],
            });
        });

        const modalView = new SelectOneAttachmentModalView({
            dataList: dataList,
            fieldLabel: this.translate('attachments', 'fields', 'Email'),
            onSelect: id => proceed(id),
        });

        this.assignView('selectModal', modalView);

        modalView.render();
    }
}

export default EmailDetailView;
