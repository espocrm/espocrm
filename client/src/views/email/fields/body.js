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

import WysiwygFieldView from 'views/fields/wysiwyg';

class EmailBodyFieldView extends WysiwygFieldView {

    useIframe = true
    hasBodyPlainField = true
    toShowQuotePart = false;

    /**
     * @type {string}
     * @private
     */
    replyPart

    getAttributeList() {
        return ['body', 'bodyPlain'];
    }

    setupToolbar() {
        super.setupToolbar();

        const attachmentItem = this.toolbar.find(it => it[0] === 'attachment');

        if (attachmentItem) {
            attachmentItem[1].push('insert-field');
        } else {
            this.toolbar.push(['insert-field', ['insert-field']]);
        }


        this.buttons['insert-field'] = () => {
            const ui = $.summernote.ui;

            const button = ui.button({
                contents: '<i class="fas fa-plus"></i>',
                tooltip: this.translate('Insert Field', 'labels', 'Email'),
                click: () => {
                    this.showInsertFieldModal();
                },
            });

            return button.render();
        };

        this.listenTo(this.model, 'change', m => {
            if (!this.isRendered()) {
                return;
            }

            if (m.hasChanged('parentId') || m.hasChanged('to')) {
                this.controlInsertFieldButton();
            }
        });
    }

    onEditModeSet() {
        this.toShowQuotePart = false;

        return super.onEditModeSet();
    }

    afterRender() {
        super.afterRender();

        this.controlInsertFieldButton();

        if (this.isReadMode() && this.replyPart) {
            this.element.appendChild(this.createShowQuotePartButton());
        }
    }

    controlInsertFieldButton() {
        const $b = this.$el.find('.note-insert-field > button');

        if (
            this.model.get('to') &&
            this.model.get('to').length ||
            this.model.get('parentId')
        ) {
            $b.removeAttr('disabled').removeClass('disabled');
        } else {
            $b.attr('disabled', 'disabled').addClass('disabled');
        }
    }

    showInsertFieldModal() {
        let to = this.model.get('to');

        if (to) {
            to = to.split(';')[0].trim();
        }

        const parentId = this.model.get('parentId');
        const parentType = this.model.get('parentType');

        Espo.Ui.notifyWait();

        this.createView('insertFieldDialog', 'views/email/modals/insert-field', {
            parentId: parentId,
            parentType: parentType,
            to: to,
        }, view => {
            view.render();

            Espo.Ui.notify();

            this.listenToOnce(view, 'insert', /** string */string => {
                if (this.$summernote) {
                    if (string.includes('\n')) {
                        string = string.replace(/\r\n|\r|\n/g, '<br>');
                        const html = '<p>' + string + '</p>';

                        this.$summernote.summernote('editor.pasteHTML', html);
                    } else {
                        this.$summernote.summernote('editor.insertText', string);
                    }
                }

                this.clearView('insertFieldDialog');
            });
        });
    }

    getValueForIframe() {
        let contents = super.getValueForIframe();

        if (this.toShowQuotePart) {
            this.replyPart = undefined;

            return contents;
        }

        this.replyPart = undefined;

        contents = this.processQuotePart(contents);

        if (!this.replyPart) {
            return contents;
        }

        return contents
    }

    getValueForEdit() {
        this.replyPart = undefined;
        const contents = super.getValueForEdit();

        return this.processQuotePart(contents, true);
    }

    /**
     * @private
     * @param {string} contents
     * @param {boolean} [isEdit=false]
     * @return {string}
     */
    processQuotePart(contents, isEdit) {
        const container = document.createElement('div');
        container.innerHTML = contents;

        let selector = `:scope > [data-quote-start="true"]`;

        if (!isEdit) {
            selector +=
                ', :scope > blockquote:last-child' +
                ', :scope > div:last-child > blockquote:last-child';
        }

        let element = container.querySelector(selector);

        if (!element) {
            return contents;
        }

        container.querySelectorAll('style, link[ref="stylesheet"]').forEach(element => {
            element.parentElement.removeChild(element);
        });

        this.replyPart = '';
        /** @type {HTMLElement[]} */
        const removeList = [];

        while (element) {
            this.replyPart += element.outerHTML;

            removeList.push(element);
            element = element.nextElementSibling;
        }

        removeList.forEach(element => element.parentElement.removeChild(element));

        if (isEdit) {
            this.element.appendChild(this.createShowQuotePartButton());
        }

        return container.innerHTML;
    }

    /**
     * @private
     * @return {HTMLAnchorElement}
     */
    createShowQuotePartButton() {
        const a = this.showQuoteButtonElement = document.createElement('a');

        a.setAttribute('role', 'button');
        a.innerHTML = '...';
        a.classList.add('show-quote-button', 'btn', 'btn-default', 'btn-sm');
        a.addEventListener('click', () => this.showQuotePart());

        return a;
    }

    /** @private */
    showQuotePart() {
        if (this.isReadMode()) {
            this.toShowQuotePart = true;
            this.reRender();

            return;
        }

        const value = this.$summernote.summernote('code') + this.replyPart;

        this.replyPart = undefined;
        this.showQuoteButtonElement.parentElement.removeChild(this.showQuoteButtonElement);

        this.$summernote.summernote('code', value);
    }

    fetch() {
        const data = super.fetch();

        if (this.model.attributes.isHtml && this.replyPart) {
            if (data[this.name] == null) {
                data[this.name] = '';
            }

            data[this.name] += this.replyPart;
        }

        if (this.model.attributes.isHtml) {
            data[this.name + 'Plain'] = this.htmlToPlain(data[this.name]);
        }

        return data;
    }
}

export default EmailBodyFieldView;
