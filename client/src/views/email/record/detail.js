/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2024 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

/** @module views/email/record/detail */

import DetailRecordView from 'views/record/detail';

class EmailDetailRecordView extends DetailRecordView {

    sideView = 'views/email/record/detail-side'
    duplicateAction = false
    shortcutKeyCtrlEnterAction = 'send'

    layoutNameConfigure() {
        if (this.model.isNew()) {
            return;
        }

        const status = this.model.get('status');

        if (status === 'Draft') {
            this.layoutName = 'composeSmall';

            return;
        }

        let isRestricted = false;

        if (status === 'Sent') {
            isRestricted = true;
        }

        if (status === 'Archived') {
            if (
                this.model.get('createdById') === this.getHelper().getAppParam('systemUserId') ||
                !this.model.get('createdById') || this.model.get('isImported')
            ) {
                isRestricted = true;
            }
        }

        if (isRestricted) {
            this.layoutName += 'Restricted';
        }

        this.isRestricted = isRestricted;
    }

    init() {
        super.init();

        this.layoutNameConfigure();
    }

    setup() {
        super.setup();

        if (['Archived', 'Sent'].includes(this.model.get('status'))) {
            this.shortcutKeyCtrlEnterAction = 'save';
        }

        this.addButtonEdit({
            name: 'send',
            action: 'send',
            label: 'Send',
            style: 'primary',
            title: 'Ctrl+Enter',
        }, true);

        this.addButtonEdit({
            name: 'saveDraft',
            action: 'save',
            label: 'Save Draft',
            title: 'Ctrl+S',
        }, true);

        this.addButton({
            name: 'sendFromDetail',
            label: 'Send',
            hidden: true,
        });

        this.controlSendButton();

        this.listenTo(this.model, 'change:status', () => this.controlSendButton());

        if (this.model.get('status') !== 'Draft' && this.model.has('isRead') && !this.model.get('isRead')) {
            this.model.set('isRead', true);
        }

        this.listenTo(this.model, 'sync', () => {
            if (!this.model.get('isRead') && this.model.get('status') !== 'Draft') {
                this.model.set('isRead', true);
            }
        });

        if (!(this.model.get('isHtml') && this.model.get('bodyPlain'))) {
            this.listenToOnce(this.model, 'sync', () => {
                if (this.model.get('isHtml') && this.model.get('bodyPlain')) {
                    this.showActionItem('showBodyPlain');
                }
            });
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

        this.addDropdownItem({
            label: 'Print',
            name: 'print',
        });

        this.listenTo(this.model, 'change:isImportant', () => {
            if (this.model.get('isImportant')) {
                this.hideActionItem('markAsImportant');
                this.showActionItem('markAsNotImportant');
            } else {
                this.hideActionItem('markAsNotImportant');
                this.showActionItem('markAsImportant');
            }
        });

        this.listenTo(this.model, 'change:inTrash', () => {
            if (this.model.get('inTrash')) {
                this.hideActionItem('moveToTrash');
                this.showActionItem('retrieveFromTrash');
            } else {
                this.hideActionItem('retrieveFromTrash');
                this.showActionItem('moveToTrash');
            }
        });

        this.handleTasksField();
        this.listenTo(this.model, 'change:tasksIds', () => this.handleTasksField());

        if (this.getUser().isAdmin()) {
            this.addDropdownItem({
                label: 'View Users',
                name: 'viewUsers'
            });
        }

        this.setFieldReadOnly('replied');

        if (this.model.get('status') === 'Draft') {
            this.setFieldReadOnly('dateSent');

            this.controlSelectTemplateField();

            this.on('after:mode-change', () => this.controlSelectTemplateField());
        }

        if (this.isRestricted) {
            this.handleAttachmentField();
            this.listenTo(this.model, 'change:attachmentsIds', () => this.handleAttachmentField());

            this.handleCcField();
            this.listenTo(this.model, 'change:cc', () => this.handleCcField());

            this.handleBccField();
            this.listenTo(this.model, 'change:bcc', () => this.handleBccField());
        }
    }

    controlSelectTemplateField() {
        if (this.mode === this.MODE_EDIT) {
            // Not implemented for detail view yet.
            this.hideField('selectTemplate');

            return;
        }

        this.hideField('selectTemplate');
    }

    controlSendButton()  {
        const status = this.model.get('status');

        if (status === 'Draft') {
            this.showActionItem('send');
            this.showActionItem('saveDraft');
            this.showActionItem('sendFromDetail');
            this.hideActionItem('save');
            this.hideActionItem('saveAndContinueEditing');

            return;
        }

        this.hideActionItem('sendFromDetail');
        this.hideActionItem('send');
        this.hideActionItem('saveDraft');
        this.showActionItem('save');
        this.showActionItem('saveAndContinueEditing');
    }

    // noinspection JSUnusedGlobalSymbols
    actionSaveDraft() {
        this.actionSaveAndContinueEditing();
    }

    actionMarkAsImportant() {
        Espo.Ajax.postRequest('Email/inbox/important', {id: this.model.id});

        this.model.set('isImportant', true);
    }

    actionMarkAsNotImportant() {
        Espo.Ajax.deleteRequest('Email/inbox/important', {id: this.model.id});

        this.model.set('isImportant', false);
    }

    actionMoveToTrash() {
        Espo.Ajax.postRequest('Email/inbox/inTrash', {id: this.model.id}).then(() => {
            Espo.Ui.warning(this.translate('Moved to Trash', 'labels', 'Email'));
        });

        this.model.set('inTrash', true);

        if (this.model.collection) {
            this.model.collection.trigger('moving-to-trash', this.model.id);
        }
    }

    // noinspection JSUnusedGlobalSymbols
    actionRetrieveFromTrash() {
        Espo.Ajax.deleteRequest('Email/inbox/inTrash', {id: this.model.id}).then(() => {
            Espo.Ui.warning(this.translate('Retrieved from Trash', 'labels', 'Email'));
        });

        this.model.set('inTrash', false);

        if (this.model.collection) {
            this.model.collection.trigger('retrieving-from-trash', this.model.id);
        }
    }

    actionMoveToFolder() {
        this.createView('dialog', 'views/email-folder/modals/select-folder', {}, (view) => {
            view.render();

            this.listenToOnce(view, 'select', folderId => {
                this.clearView('dialog');

                Espo.Ajax.postRequest(`Email/inbox/folders/${folderId}`, {id: this.model.id})
                    .then(() => {
                        if (folderId === 'inbox') {
                            folderId = null;
                        }

                        this.model.set('folderId', folderId);

                        Espo.Ui.success(this.translate('Done'));
                    });
            });
        });
    }

    // noinspection JSUnusedGlobalSymbols
    actionShowBodyPlain() {
        this.createView('bodyPlain', 'views/email/modals/body-plain', {
            model: this.model
        }, view => {
            view.render();
        });
    }

    handleAttachmentField() {
        if ((this.model.get('attachmentsIds') || []).length === 0) {
            this.hideField('attachments');
        } else {
            this.showField('attachments');
        }
    }

    handleCcField() {
        if (!this.model.get('cc')) {
            this.hideField('cc');
        } else {
            this.showField('cc');
        }
    }

    handleBccField() {
        if (!this.model.get('bcc')) {
            this.hideField('bcc');
        } else {
            this.showField('bcc');
        }
    }

    send() {
        const model = this.model;

        const status = model.get('status');

        model.set('status', 'Sending');

        this.isSending = true;

        const afterSend = () => {
            model.trigger('after:send');

            this.trigger('after:send');
            this.isSending = false;
        };

        this.once('after:save', afterSend, this);

        this.once('cancel:save', () => {
            this.off('after:save', afterSend);
            this.isSending = false;

            model.set('status', status);
        });

        this.once('before:save', () => {
            Espo.Ui.notify(this.translate('Sending...', 'labels', 'Email'));
        });

        return this.save();
    }

    // noinspection JSUnusedGlobalSymbols
    actionSendFromDetail() {
        this.setEditMode()
            .then(() => {
                return this.send();
            })
            .then(() => {
                this.setDetailMode();
            });
    }

    // noinspection JSUnusedGlobalSymbols
    exitAfterDelete() {
        let folderId = ((this.collection || {}).data || {}).folderId || null;

        if (folderId === 'inbox') {
            folderId = null;
        }

        const options = {
            isReturn: true,
            isReturnThroughLink: false,
            folder: folderId,
        };

        let url = '#' + this.scope;
        let action = null;

        if (folderId) {
            action = 'list';
            url += '/list/folder=' + folderId;
        }

        this.getRouter().dispatch(this.scope, action, options);
        this.getRouter().navigate(url, {trigger: false});

        return true;
    }

    // noinspection JSUnusedGlobalSymbols
    actionViewUsers(data) {
        const viewName =
            this.getMetadata()
                .get(['clientDefs', this.model.entityType, 'relationshipPanels', 'users', 'viewModalView']) ||
            this.getMetadata().get(['clientDefs', 'User', 'modalViews', 'relatedList']) ||
            'views/modals/related-list';

        const options = {
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
            for (const item in data.viewOptions) {
                options[item] = data.viewOptions[item];
            }
        }

        Espo.Ui.notify(' ... ');

        this.createView('modalRelatedList', viewName, options, (view) => {
            Espo.Ui.notify(false);

            view.render();

            this.listenTo(view, 'action', (event, element) => {
                Espo.Utils.handleAction(this, event, element);
            });

            this.listenToOnce(view, 'close', () => {
                this.clearView('modalRelatedList');
            });
        });
    }

    // noinspection JSUnusedGlobalSymbols
    actionSend() {
        this.send()
            .then(() => {
                this.model.set('status', 'Sent');

                if (this.mode !== this.MODE_DETAIL) {
                    this.setDetailMode();
                    this.setFieldReadOnly('dateSent');
                    this.setFieldReadOnly('name');
                    this.setFieldReadOnly('attachments');
                    this.setFieldReadOnly('isHtml');
                    this.setFieldReadOnly('from');
                    this.setFieldReadOnly('to');
                    this.setFieldReadOnly('cc');
                    this.setFieldReadOnly('bcc');
                }
            });
    }

    // noinspection JSUnusedGlobalSymbols
    actionPrint() {
        /** @type {module:views/fields/wysiwyg} */
        const bodyView = this.getFieldView('body');

        if (!bodyView) {
            return;
        }

        let iframe = /** @type HTMLIFrameElement */bodyView.$el.find('iframe').get(0);

        if (iframe) {
            iframe.contentWindow.print();

            return;
        }

        const el = bodyView.$el.get(0);
        /** @type {Element} */
        const recordElement = this.$el.get(0);

        iframe = document.createElement('iframe');
        iframe.style.display = 'none';

        recordElement.append(iframe);

        const contentWindow = iframe.contentWindow;

        contentWindow.document.open();
        contentWindow.document.write(el.innerHTML);
        contentWindow.document.close();
        contentWindow.focus();
        contentWindow.print();
        contentWindow.onafterprint = () => {
            recordElement.removeChild(iframe);
        }
    }

    errorHandlerSendingFail(data) {
        if (!this.model.id) {
            this.model.id = data.id;
        }

        let msg = this.translate('sendingFailed', 'strings', 'Email');

        if (data.message) {
            let part = data.message;

            if (this.getLanguage().has(part, 'messages', 'Email')) {
                part = this.translate(part, 'messages', 'Email');
            }

            msg += ': ' + part;
        }

        Espo.Ui.error(msg, true);
        console.error(msg);
    }

    handleTasksField() {
        if ((this.model.get('tasksIds') || []).length === 0) {
            this.hideField('tasks');

            return;
        }

        this.showField('tasks');
    }

    /**
     * @protected
     * @param {JQueryKeyEventObject} e
     */
    handleShortcutKeyCtrlS(e) {
        if (this.inlineEditModeIsOn || this.buttonsDisabled) {
            return;
        }

        e.preventDefault();
        e.stopPropagation();

        if (this.mode !== this.MODE_EDIT) {
            return;
        }

        if (!this.saveAndContinueEditingAction) {
            return;
        }

        if (!this.hasAvailableActionItem('saveDraft') && !this.hasAvailableActionItem('saveAndContinueEditing')) {
            return;
        }

        this.actionSaveAndContinueEditing();
    }
}

export default EmailDetailRecordView;
