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

import View from 'view';

class CollapsedModalView extends View {

    templateContent = `
        <div class="title-container">
            <a role="button" data-action="expand" class="title">{{title}}</a>
        </div>
        <div class="close-container">
            <a role="button" data-action="close"><span class="fas fa-times"></span></a>
        </div>
    `

    events = {
        /** @this CollapsedModalView */
        'click [data-action="expand"]': function () {
            this.expand();
        },
        /** @this CollapsedModalView */
        'click [data-action="close"]': function () {
            this.close();
        },
    }

    /**
     * @private
     */
    title

    /**
     * @param {{
     *     modalView: import('views/modal').default,
     *     onClose: function(),
     *     onExpand: function(),
     *     duplicateNumber?: number|null,
     *     title?: string,
     * }} options
     */
    constructor(options) {
        super(options);

        this.options = options;

        this.modalView = options.modalView;
    }

    data() {
        let title = this.title;

        if (this.options.duplicateNumber) {
            title = `${this.title} ${this.options.duplicateNumber}`;
        }

        return {
            title: title,
        };
    }

    setup() {
        this.title = this.options.title || 'no-title';
    }

    expand() {
        this.options.onExpand();
    }

    close() {
        this.options.onClose();
    }
}

// noinspection JSUnusedGlobalSymbols
export default CollapsedModalView;
