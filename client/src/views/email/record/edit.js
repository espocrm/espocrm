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

/** @module views/email/record/edit */

import EditRecordView from 'views/record/edit';
import EmailDetailRecordView from 'views/email/record/detail';

class EmailEditRecordView extends EditRecordView {

    shortcutKeyCtrlEnterAction = 'send'

    init() {
        super.init();

        EmailDetailRecordView.prototype.layoutNameConfigure.call(this);
    }

    setup() {
        super.setup();

        if (['Archived', 'Sent'].includes(this.model.get('status'))) {
            this.shortcutKeyCtrlEnterAction = 'save';
        }

        this.addButton({
            name: 'send',
            label: 'Send',
            style: 'primary',
            title: 'Ctrl+Enter',
        }, true);

        this.addButton({
            name: 'saveDraft',
            label: 'Save Draft',
            title: 'Ctrl+S',
        }, true);

        this.controlSendButton();

        if (this.model.get('status') === 'Draft') {
            this.setFieldReadOnly('dateSent');

            // Not implemented for detail view yet.
            this.hideField('selectTemplate');
        }

        this.handleAttachmentField();
        this.handleCcField();
        this.handleBccField();

        this.listenTo(this.model, 'change:attachmentsIds', () => this.handleAttachmentField());
        this.listenTo(this.model, 'change:cc', () => this.handleCcField());
        this.listenTo(this.model, 'change:bcc', () => this.handleBccField());
    }

    handleAttachmentField() {
        if (
            (this.model.get('attachmentsIds') || []).length === 0 &&
            !this.isNew &&
            this.model.get('status') !== 'Draft'
        ) {
            this.hideField('attachments');

            return;
        }

        this.showField('attachments');
    }

    handleCcField() {
        if (!this.model.get('cc') && this.model.get('status') !== 'Draft') {
            this.hideField('cc');
        } else {
            this.showField('cc');
        }
    }

    handleBccField() {
        if (!this.model.get('bcc') && this.model.get('status') !== 'Draft') {
            this.hideField('bcc');
        } else {
            this.showField('bcc');
        }
    }

    controlSendButton()  {
        const status = this.model.get('status');

        if (status === 'Draft') {
            this.showActionItem('send');
            this.showActionItem('saveDraft');
            this.hideActionItem('save');
            this.hideActionItem('saveAndContinueEditing');

            return;
        }

        this.hideActionItem('send');
        this.hideActionItem('saveDraft');
        this.showActionItem('save');
        this.showActionItem('saveAndContinueEditing');
    }

    // noinspection JSUnusedGlobalSymbols
    actionSaveDraft() {
        this.actionSaveAndContinueEditing();
    }

    // noinspection JSUnusedGlobalSymbols
    actionSend() {
        EmailDetailRecordView.prototype.send.call(this)
            .then(() => this.exit())
            .catch(() => {});
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

        if (
            !this.hasAvailableActionItem('saveDraft') &&
            !this.hasAvailableActionItem('saveAndContinueEditing')
        ) {
            return;
        }

        this.actionSaveAndContinueEditing();
    }
}

export default EmailEditRecordView;
