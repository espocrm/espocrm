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

import EditModalView from 'views/modals/edit';
import MailtoHelper from 'helpers/misc/mailto';
import EmailScheduleSendModalView from 'views/email/modals/schedule-send';

class ComposeEmailModalView extends EditModalView {

    scope = 'Email'
    layoutName = 'composeSmall'
    saveDisabled = true
    fullFormDisabled = true
    isCollapsible = true
    wasModified = false

    shortcutKeys = {
        /** @this ComposeEmailModalView */
        'Control+Enter': function (e) {
            if (this.buttonList.findIndex(item => item.name === 'send' && !item.hidden) === -1) {
                return;
            }

            e.stopPropagation();
            e.preventDefault();

            this.actionSend();
        },
        /** @this ComposeEmailModalView */
        'Control+KeyS': function (e) {
            if (this.buttonList.findIndex(item => item.name === 'saveDraft' && !item.hidden) === -1) {
                return;
            }

            e.preventDefault();
            e.stopPropagation();

            this.actionSaveDraft();
        },
        /** @this ComposeEmailModalView */
        'Escape': function (e) {
            e.stopPropagation();
            e.preventDefault();

            const focusedFieldView = this.getRecordView().getFocusedFieldView();

            if (focusedFieldView) {
                this.model.set(focusedFieldView.fetch());
            }

            if (this.getRecordView().isChanged) {
                this.confirm(this.translate('confirmLeaveOutMessage', 'messages'))
                    .then(() => this.actionClose());

                return;
            }

            this.actionClose();
        },
    }

    setup() {
        super.setup();

        this.buttonList.unshift({
            name: 'saveDraft',
            text: this.translate('Save Draft', 'labels', 'Email'),
            title: 'Ctrl+S',
            onClick: () => this.actionSaveDraft(),
        });

        this.buttonList.unshift({
            name: 'send',
            text: this.translate('Send', 'labels', 'Email'),
            style: 'primary',
            title: 'Ctrl+Enter',
            onClick: () => this.actionSend(),
        });

        this.dropdownItemList.push({
            name: 'scheduleSend',
            text: this.translate('Schedule Send', 'labels', 'Email'),
            onClick: () => this.actionScheduleSend(),
        });

        this.$header = $('<a>')
            .attr('role', 'button')
            .attr('tabindex', '0')
            .attr('data-action', 'fullFormDraft')
            .text(this.getLanguage().translate('Compose Email'));

        this.events['click a[data-action="fullFormDraft"]'] = () => this.actionFullFormDraft();

        const helper = new MailtoHelper(this.getConfig(), this.getPreferences(), this.getAcl());

        if (helper.toUse()) {
            this.once('after:render', () => this.actionClose());
            this.getRouter().confirmLeaveOut = false;

            const attributes = this.options.attributes || {};

            this.once('after:render', () => document.location.href = helper.composeLink(attributes));

            return;
        }

        this.once('remove', () => {
            this.dialogIsHidden = false;
        });

        this.listenTo(this.model, 'change', (m, o) => {
            if (o.ui) {
                this.wasModified = true;
            }
        });
    }

    createRecordView(model, callback) {
        const viewName = this.getMetadata().get('clientDefs.' + model.entityType + '.recordViews.compose') ||
            'views/email/record/compose';

        const options = {
            model: model,
            fullSelector: this.containerSelector + ' .edit-container',
            type: 'editSmall',
            layoutName: this.layoutName || 'detailSmall',
            buttonsDisabled: true,
            selectTemplateDisabled: this.options.selectTemplateDisabled,
            removeAttachmentsOnSelectTemplate: this.options.removeAttachmentsOnSelectTemplate,
            signatureDisabled: this.options.signatureDisabled,
            appendSignature: this.options.appendSignature,
            focusForCreate: this.options.focusForCreate,
            exit: () => {},
        };

        this.createView('edit', viewName, options, callback);
    }

    actionSend() {
        const dialog = this.dialog;

        const editView = /** @type {module:views/email/record/compose} */this.getRecordView();

        const model = editView.model;

        const afterSend = () => {
            this.dialogIsHidden = false;

            this.trigger('after:save', model);
            this.trigger('after:send', model);

            dialog.close();

            this.stopListening(editView, 'before:save', beforeSave);
            this.stopListening(editView, 'error:save', errorSave);

            this.remove();
        };

        const beforeSave = () => {
            this.dialogIsHidden = true;

            dialog.hideWithBackdrop();

            editView.setConfirmLeaveOut(false);

            if (!this.forceRemoveIsInitiated) {
                this.initiateForceRemove();
            }
        };

        const errorSave = () => {
            this.dialogIsHidden = false;

            if (this.isRendered()) {
                dialog.show();
            }

            this.stopListening(editView, 'before:save', beforeSave);
            this.stopListening(editView, 'error:save', errorSave);
        };

        this.listenToOnce(editView, 'after:send', afterSend);

        this.disableButton('send');
        this.disableButton('saveDraft');

        this.listenToOnce(editView, 'cancel:save', () => {
            this.enableButton('send');
            this.enableButton('saveDraft');

            this.stopListening(editView, 'after:send', afterSend);

            this.stopListening(editView, 'before:save', beforeSave);
            this.stopListening(editView, 'error:save', errorSave);
        });

        this.listenToOnce(editView, 'before:save', beforeSave);
        this.listenToOnce(editView, 'error:save', errorSave);

        editView.send();
    }

    /**
     * @param {module:views/record/base~saveOptions} [options] Options.
     * @return {Promise}
     */
    actionSaveDraft(options) {
        const editView = /** @type {module:views/email/record/compose} */this.getRecordView();

        const model = editView.model;

        this.disableButton('send');
        this.disableButton('saveDraft');

        const afterSave = () => {
            this.enableButton('send');
            this.enableButton('saveDraft');

            Espo.Ui.success(this.translate('savedAsDraft', 'messages', 'Email'))

            this.trigger('after:save', model);

            this.$el.find('button[data-name="cancel"]').html(this.translate('Close'));
        };

        editView.once('after:save', () => afterSave());

        editView.once('cancel:save', () => {
            this.enableButton('send');
            this.enableButton('saveDraft');

            editView.off('after:save', afterSave);
        });

        return editView.saveDraft(options);
    }

    initiateForceRemove() {
        this.forceRemoveIsInitiated = true;

        const parentView = this.getParentView();

        if (!parentView) {
            return true;
        }

        parentView.once('remove', () => {
            if (!this.dialogIsHidden) {
                return;
            }

            this.remove();
        });
    }

    actionFullFormDraft() {
        this.actionSaveDraft({skipNotModifiedWarning: true})
            .then(() => {
                this.getRecordView().setConfirmLeaveOut(false);

                this.getRouter().navigate('#Email/edit/' + this.model.id, {trigger: true});

                this.close();
            })
            .catch(reason => {
                if (reason === 'notModified') {
                    Espo.Ui.notify(false);

                    this.getRouter().navigate('#Email/edit/' + this.model.id, {trigger: true});
                }
            });
    }

    beforeCollapse() {
        if (this.wasModified) {
            this.actionSaveDraft({skipNotModifiedWarning: true})
                .then(() => {
                    this.getRecordView().setConfirmLeaveOut(false);
                    this.getRouter().removeWindowLeaveOutObject(this);
                });
        }

        return super.beforeCollapse();
    }

    /**
     * @private
     */
    async actionScheduleSend() {
        // Prevents skipping required validation of the 'To' field.
        this.model.set('status', 'Sending');

        if (this.getRecordView().validate()) {
            Espo.Ui.error(this.translate('Not valid'));

            this.model.set('status', 'Draft');

            return;
        }

        this.model.set('status', 'Draft');

        const view = new EmailScheduleSendModalView({
            model: this.model,
            onSave: () => {
                this.trigger('after:save', this.model);

                this.close();
            },
        });

        await this.assignView('dialog', view);
        await view.render();
    }
}

export default ComposeEmailModalView;
