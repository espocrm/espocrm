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

    data() {
        let title = this.title;

        if (this.duplicateNumber) {
            title = this.title + ' ' + this.duplicateNumber;
        }

        return {
            title: title,
        };
    }

    setup() {
        this.title = this.options.title || 'no-title';
        this.duplicateNumber = this.options.duplicateNumber || null;
    }

    expand() {
        this.trigger('expand');
    }

    close() {
        this.trigger('close');
    }
}

// noinspection JSUnusedGlobalSymbols
export default CollapsedModalView;
