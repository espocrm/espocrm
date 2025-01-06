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

export default class WysiwygInsertImageModal extends ModalView {

    className = 'dialog dialog-record'

    template = 'wysiwyg/modals/insert-image'

    events = {
        /** @this {WysiwygInsertImageModal} */
        'click [data-action="insert"]': function () {
            this.actionInsert();
        },
        /** @this {WysiwygInsertImageModal} */
        'input [data-name="url"]': function () {
            this.controlInsertButton();
        },
        /** @this {WysiwygInsertImageModal} */
        'paste [data-name="url"]': function () {
            this.controlInsertButton();
        },
    }

    shortcutKeys = {
        /** @this {WysiwygInsertImageModal} */
        'Control+Enter': function () {
            if (!this.$el.find('[data-name="insert"]').hasClass('disabled')) {
                this.actionInsert();
            }
        }
    }

    data() {
        return {
            labels: this.options.labels || {},
        };
    }

    setup() {
        const labels = this.options.labels || {};

        this.headerText = labels.insert;

        this.buttonList = [];
    }

    afterRender() {
        const $files = this.$el.find('[data-name="files"]');

        $files.replaceWith(
            $files.clone()
                .on('change', (e) => {
                  this.trigger('upload', e.target.files || e.target.value);
                  this.close();
                })
                .val('')
        );
    }

    controlInsertButton() {
        const value = this.$el.find('[data-name="url"]').val().trim();

        const $button = this.$el.find('[data-name="insert"]');

        if (value) {
            $button.removeClass('disabled').removeAttr('disabled');
        } else {
            $button.addClass('disabled').attr('disabled', 'disabled');
        }
    }

    actionInsert() {
        const url = this.$el.find('[data-name="url"]').val().trim();

        this.trigger('insert', url);
        this.close();
    }
}
