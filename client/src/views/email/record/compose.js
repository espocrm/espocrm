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

/** @module views/email/record/compose */

import EditRecordView from 'views/record/edit';
import EmailDetailRecordView from 'views/email/record/detail';

class EmailComposeRecordView extends EditRecordView {

    isWide = true
    sideView = false

    /**
     * @private
     * @type {string|null}
     */
    initialBody

    /**
     * @private
     * @type {boolean|null}
     */
    initialIsHtml

    setupBeforeFinal() {
        super.setupBeforeFinal();

        this.initialBody = this.model.attributes.body;
        this.initialIsHtml = this.model.attributes.isHtml;

        if (
            !this.model.attributes.isHtml &&
            this.getPreferences().get('emailReplyForceHtml')
        ) {
            this.initialBody = (this.initialBody || '').replace(/\n/g, '<br>') || null;
            this.initialIsHtml = true;
        }

        let body = this.initialBody;

        if (!this.options.signatureDisabled && this.hasSignature()) {
            let addSignatureMethod = 'prependSignature';

            if (this.options.appendSignature) {
                addSignatureMethod = 'appendSignature';
            }

            body = this[addSignatureMethod](body || '', this.initialIsHtml) || null;
        }

        this.model.set('body', body, {silent: true});
        this.model.set('isHtml', this.initialIsHtml, {silent: true});
    }

    setup() {
        super.setup();

        this.isBodyChanged = false;

        this.listenTo(this.model, 'change:body', () => {
            this.isBodyChanged = true;
        });

        if (!this.options.removeAttachmentsOnSelectTemplate) {
            this.initialAttachmentsIds = this.model.get('attachmentsIds') || [];
            this.initialAttachmentsNames = this.model.get('attachmentsNames') || {};
        }

        this.initInsertTemplate();

        if (this.options.selectTemplateDisabled) {
            this.hideField('selectTemplate');
        }
    }

    initInsertTemplate() {
        this.listenTo(this.model, 'insert-template', data => {
            const body = this.model.get('body') || '';

            let bodyPlain = body.replace(/<br\s*\/?>/mg, '');

            bodyPlain = bodyPlain.replace(/<\/p\s*\/?>/mg, '');
            bodyPlain = bodyPlain.replace(/ /g, '');
            bodyPlain = bodyPlain.replace(/\n/g, '');

            const $div = $('<div>').html(bodyPlain);

            bodyPlain = $div.text();

            if (bodyPlain !== '' && this.isBodyChanged) {
                this.confirm({
                        message: this.translate('confirmInsertTemplate', 'messages', 'Email'),
                        confirmText: this.translate('Yes')
                    })
                    .then(() => this.insertTemplate(data));

                return;
            }

            this.insertTemplate(data);
        });
    }

    insertTemplate(data) {
        let body = data.body;

        if (this.hasSignature()) {
            body = this.appendSignature(body || '', data.isHtml);
        }

        if (this.initialBody && !this.isBodyChanged) {
            let initialBody = this.initialBody;

            if (data.isHtml !== this.initialIsHtml) {
                if (data.isHtml) {
                    initialBody = this.plainToHtml(initialBody);
                } else {
                    initialBody = this.htmlToPlain(initialBody);
                }
            }

            body += initialBody;
        }

        this.model.set('isHtml', data.isHtml);

        if (data.subject) {
            this.model.set('name', data.subject);
        }

        this.model.set('body', '');
        this.model.set('body', body);

        if (!this.options.removeAttachmentsOnSelectTemplate) {
            this.initialAttachmentsIds.forEach((id) => {
                if (data.attachmentsIds) {
                    data.attachmentsIds.push(id);
                }

                if (data.attachmentsNames) {
                    data.attachmentsNames[id] = this.initialAttachmentsNames[id] || id;
                }
            });
        }

        this.model.set({
            attachmentsIds: data.attachmentsIds,
            attachmentsNames: data.attachmentsNames
        });

        this.isBodyChanged = false;
    }

    prependSignature(body, isHtml) {
        if (isHtml) {
            let signature = this.getSignature();

            if (body) {
                signature += '';
            }

            return'<p><br></p>' + signature + body;
        }

        let signature = this.getPlainTextSignature();

        if (body) {
            signature += '\n';
        }

        return '\n\n' + signature + body;
    }

    appendSignature(body, isHtml) {
        if (isHtml) {
            const signature = this.getSignature();

            return  body + '' + signature;
        }

        const signature = this.getPlainTextSignature();

        return body + '\n\n' + signature;
    }

    hasSignature() {
        return !!this.getPreferences().get('signature');
    }

    getSignature() {
        return this.getPreferences().get('signature') || '';
    }

    getPlainTextSignature() {
        let value = this.getSignature().replace(/<br\s*\/?>/mg, '\n');

        value = $('<div>').html(value).text();

        return value;
    }

    afterSave() {
        super.afterSave();

        if (this.isSending && this.model.get('status') === 'Sent') {
            Espo.Ui.success(this.translate('emailSent', 'messages', 'Email'));
        }
    }

    send() {
        EmailDetailRecordView.prototype.send.call(this);
    }

    /**
     * @param {module:views/record/base~saveOptions} [options] Options.
     * @return {Promise}
     */
    saveDraft(options) {
        const model = this.model;

        model.set('status', 'Draft');

        const subjectView = this.getFieldView('subject');

        if (subjectView) {
            subjectView.fetchToModel();

            if (!model.get('name')) {
                model.set('name', this.translate('No Subject', 'labels', 'Email'));
            }
        }

        return this.save(options);
    }

    htmlToPlain(text) {
        text = text || '';

        let value = text.replace(/<br\s*\/?>/mg, '\n');

        value = value.replace(/<\/p\s*\/?>/mg, '\n\n');

        const $div = $('<div>').html(value);

        $div.find('style').remove();
        $div.find('link[ref="stylesheet"]').remove();

        value =  $div.text();

        return value;
    }

    plainToHtml(html) {
        html = html || '';

        return html.replace(/\n/g, '<br>');
    }

    // noinspection JSUnusedGlobalSymbols
    errorHandlerSendingFail(data) {
        EmailDetailRecordView.prototype.errorHandlerSendingFail.call(this, data);
    }

    focusForCreate() {
        if (!this.model.get('to')) {
            this.$el
                .find('.field[data-name="to"] input')
                .focus();

            return;
        }

        if (!this.model.get('subject')) {
            this.$el
                .find('.field[data-name="subject"] input')
                .focus();

            return;
        }

        if (this.model.get('isHtml')) {
            const $div = this.$el.find('.field[data-name="body"] .note-editable');

            if (!$div.length) {
                return;
            }

            $div.focus();

            return;
        }

        this.$el
            .find('.field[data-name="body"] textarea')
            .prop('selectionEnd', 0)
            .focus();
    }
}

export default EmailComposeRecordView;
