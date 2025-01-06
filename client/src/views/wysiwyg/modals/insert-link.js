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

import ModalView from 'views/modal';

export default class WysiwygInsertLinkModal extends ModalView {

    className = 'dialog dialog-record'

    template = 'wysiwyg/modals/insert-link'

    events = {
        /** @this {WysiwygInsertLinkModal} */
        'input [data-name="url"]': function () {
            this.controlInputs();
        },
        /** @this {WysiwygInsertLinkModal} */
        'paste [data-name="url"]': function () {
            this.controlInputs();
        },
    }

    shortcutKeys = {
        /** @this {WysiwygInsertLinkModal} */
        'Control+Enter': function () {
            if (this.hasAvailableActionItem('insert')) {
                this.actionInsert();
            }
        },
    }

    data() {
        return {
            labels: this.options.labels || {},
        };
    }

    setup() {
        const labels = this.options.labels || {};

        this.headerText = labels.insert;

        this.buttonList = [
            {
                name: 'insert',
                text: this.translate('Insert'),
                style: 'primary',
                disabled: true,
            }
        ];

        this.linkInfo = this.options.linkInfo || {};

        if (this.linkInfo.url) {
            this.enableButton('insert');
        }
    }

    afterRender() {
        this.$url = this.$el.find('[data-name="url"]');
        this.$text = this.$el.find('[data-name="text"]');
        this.$openInNewWindow = this.$el.find('[data-name="openInNewWindow"]');

        const linkInfo = this.linkInfo;

        this.$url.val(linkInfo.url || '');
        this.$text.val(linkInfo.text || '');

        if ('isNewWindow' in linkInfo) {
            this.$openInNewWindow.get(0).checked = !!linkInfo.isNewWindow;
        }
    }

    controlInputs() {
        const url = this.$url.val().trim();

        if (url) {
            this.enableButton('insert');
        } else {
            this.disableButton('insert');
        }
    }

    actionInsert() {
        const url = this.$url.val().trim();
        const text = this.$text.val().trim();
        const openInNewWindow = this.$openInNewWindow.get(0).checked;

        const data = {
            url: url,
            text: text || url,
            isNewWindow: openInNewWindow,
            range: this.linkInfo.range,
        };

        this.trigger('insert', data);
        this.close();
    }
}
