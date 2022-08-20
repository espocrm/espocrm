/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2022 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

define('views/email/record/edit', ['views/record/edit', 'views/email/record/detail'], function (Dep, Detail) {

    return Dep.extend({

        shortcutKeyCtrlEnterAction: 'send',

        init: function () {
            Dep.prototype.init.call(this);
            Detail.prototype.layoutNameConfigure.call(this);
        },

        setup: function () {
            Dep.prototype.setup.call(this);

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

            this.listenTo(this.model, 'change:attachmentsIds', () => {
                this.handleAttachmentField();
            });

            this.listenTo(this.model, 'change:cc', () => {
                this.handleCcField();
            });

            this.listenTo(this.model, 'change:bcc', () => {
                this.handleBccField();
            });
        },

        handleAttachmentField: function () {
            if (
                (this.model.get('attachmentsIds') || []).length === 0 &&
                !this.isNew &&
                this.model.get('status') !== 'Draft'
            ) {
                this.hideField('attachments');

                return;
            }

            this.showField('attachments');
        },

        handleCcField: function () {
            if (!this.model.get('cc') && this.model.get('status') !== 'Draft') {
                this.hideField('cc');
            } else {
                this.showField('cc');
            }
        },

        handleBccField: function () {
            if (!this.model.get('bcc') && this.model.get('status') !== 'Draft') {
                this.hideField('bcc');
            } else {
                this.showField('bcc');
            }
        },

        controlSendButton: function ()  {
            let status = this.model.get('status');

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
        },

        actionSaveDraft: function () {
            this.actionSaveAndContinueEditing();
        },

        actionSend: function () {
            Detail.prototype.send.call(this)
                .then(() => {
                    this.exit();
                })
                .catch(() => {});
        },
    });
});
