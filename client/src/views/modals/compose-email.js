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

define('views/modals/compose-email', 'views/modals/edit', function (Dep) {

    return Dep.extend({

        scope: 'Email',

        layoutName: 'composeSmall',

        saveDisabled: true,

        fullFormDisabled: true,

        setup: function () {
            Dep.prototype.setup.call(this);

            this.buttonList.unshift({
                name: 'saveDraft',
                text: this.translate('Save Draft', 'labels', 'Email'),
            });

            this.buttonList.unshift({
                name: 'send',
                text: this.translate('Send', 'labels', 'Email'),
                style: 'primary'
            });

            this.headerHtml = this.getLanguage().translate('Compose Email');

            if (
                this.getConfig().get('emailForceUseExternalClient') ||
                this.getPreferences().get('emailUseExternalClient') ||
                !this.getAcl().checkScope('Email', 'create')
            ) {
                var attributes = this.options.attributes || {};

                require('email-helper', function (EmailHelper) {
                    this.getRouter().confirmLeaveOut = false;

                    var emailHelper = new EmailHelper();

                    var link = emailHelper.composeMailToLink(attributes, this.getConfig().get('outboundEmailBccAddress'));

                    document.location.href = link;
                }.bind(this));

                this.once('after:render', function () {
                    this.actionClose();
                }, this);

                return;
            }

            this.once('remove', function () {
                this.dialogIsHidden = false;
            }, this);
        },

        createRecordView: function (model, callback) {
            var viewName = this.getMetadata().get('clientDefs.' + model.name + '.recordViews.compose') ||
                'views/email/record/compose';

            var options = {
                model: model,
                el: this.containerSelector + ' .edit-container',
                type: 'editSmall',
                layoutName: this.layoutName || 'detailSmall',
                buttonsDisabled: true,
                selectTemplateDisabled: this.options.selectTemplateDisabled,
                removeAttachmentsOnSelectTemplate: this.options.removeAttachmentsOnSelectTemplate,
                signatureDisabled: this.options.signatureDisabled,
                appendSignature: this.options.appendSignature,
                exit: function () {}
            };

            this.createView('edit', viewName, options, callback);
        },

        actionSend: function () {
            var dialog = this.dialog;

            var editView = this.getView('edit');

            var model = editView.model;

            var afterSend = function () {
                this.dialogIsHidden = false;

                this.trigger('after:save', model);
                this.trigger('after:send', model);

                dialog.close();

                this.stopListening(editView, 'before:save', beforeSave);
                this.stopListening(editView, 'error:save', errorSave);

                this.remove();
            }.bind(this);

            var beforeSave = function () {
                this.dialogIsHidden = true;

                dialog.hideWithBackdrop();

                editView.setConfirmLeaveOut(false);

                if (!this.forceRemoveIsInitiated) {
                    this.initiateForceRemove();
                }
            }.bind(this);

            var errorSave = function () {
                this.dialogIsHidden = false;

                if (this.isRendered()) {
                    dialog.show();
                }
            }.bind(this);

            this.listenToOnce(editView, 'after:send', afterSend);

            this.disableButton('send');
            this.disableButton('saveDraft');

            this.listenToOnce(editView, 'cancel:save', function () {
                this.enableButton('send');
                this.enableButton('saveDraft');

                this.stopListening(editView, 'after:send', afterSend);

                this.stopListening(editView, 'before:save', beforeSave);
                this.stopListening(editView, 'error:save', errorSave);
            }, this);

            this.listenToOnce(editView, 'before:save', beforeSave);
            this.listenToOnce(editView, 'error:save', errorSave);

            editView.send();
        },

        actionSaveDraft: function () {
            var dialog = this.dialog;

            var editView = this.getView('edit');

            var model = editView.model;

            this.disableButton('send');
            this.disableButton('saveDraft');

            var afterSave = function () {
                this.enableButton('send');
                this.enableButton('saveDraft');
                Espo.Ui.success(this.translate('savedAsDraft', 'messages', 'Email'))

                this.trigger('after:save', model);

                this.$el.find('button[data-name="cancel"]').html(this.translate('Close'));
            }.bind(this);

            editView.once('after:save', afterSave , this);

            editView.once('cancel:save', function () {
                this.enableButton('send');
                this.enableButton('saveDraft');

                editView.off('after:save', afterSave);
            }, this);

            editView.saveDraft();
        },

        initiateForceRemove: function () {
            this.forceRemoveIsInitiated = true;

            var parentView = this.getParentView();

            if (!parentView) {
                return true;
            }

            parentView.once('remove', function () {
                if (!this.dialogIsHidden) {
                    return;
                }

                this.remove();
            }, this);
        },

    });
});
