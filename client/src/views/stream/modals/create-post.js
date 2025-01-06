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

class CreatePostModalView extends ModalView {

    templateContent = '<div class="record">{{{record}}}</div>'

    shortcutKeys = {
        'Control+Enter': 'post',
    }

    setup() {
        this.headerText = this.translate('Create Post');

        this.buttonList = [
            {
                name: 'post',
                label: 'Post',
                style: 'primary',
                title: 'Ctrl+Enter',
                onClick: () => this.post(),
            },
            {
                name: 'cancel',
                label: 'Cancel',
                title: 'Esc',
                onClick: dialog => {
                    dialog.close();
                },
            }
        ];

        this.wait(true);

        this.getModelFactory().create('Note', model => {
            this.createView('record', 'views/stream/record/edit', {
                model: model,
                selector: '.record',
            }, view => {
                this.listenTo(view, 'after:save', () => {
                    this.trigger('after:save');
                });

                this.listenTo(view, 'disable-post-button', () => this.disableButton('post'));
                this.listenTo(view, 'enable-post-button', () => this.enableButton('post'));
            });

            this.wait(false);
        });
    }

    /**
     * @return {module:views/record/edit}
     */
    getRecordView() {
        return this.getView('record');
    }

    post() {
        this.getRecordView().save();
    }
}

export default CreatePostModalView;
