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

import {register} from 'di';

/** @typedef {import('view').default} View */
/** @typedef {string|function(KeyboardEvent): void} Key */

@register()
export default class ShortcutManager {

    /**
     * @private
     * @type {number}
     */
    level = 0

    /**
     * @private
     * @type {{
     *     view: View[],
     *     keys: Record.<string, Key>,
     *     level: number,
     * }[]}
     */
    items

    constructor() {
        this.items = [];

        document.addEventListener('keydown', event => this.handle(event), {capture: true});
    }

    /**
     * Add a view and keys.
     *
     * @param {import('view').default} view
     * @param {Record.<string, Key>} keys
     * @param {{stack: boolean}} [options]
     */
    add(view, keys, options = {}) {
        if (this.items.find(it => it.view === view)) {
            return;
        }

        if (options.stack) {
            this.level ++;
        }

        this.items.push({
            view: view,
            keys: keys,
            level: this.level,
        });
    }

    /**
     * Remove a view.
     *
     * @param {import('view').default} view
     */
    remove(view) {
        const index = this.items.findIndex(it => it.view === view);

        if (index < 0) {
            return;
        }

        this.items.splice(index, 1);

        let maxLevel = 0;

        for (const item of this.items) {
            if (item.level > maxLevel) {
                maxLevel = item.level;
            }
        }

        this.level = maxLevel;
    }

    /**
     * Handle.
     *
     * @param {KeyboardEvent} event
     */
    handle(event) {
        const items = this.items.filter(it => it.level === this.level);

        if (items.length === 0) {
            return;
        }

        const key = Espo.Utils.getKeyFromKeyEvent(event);

        for (const item of items) {
            const subject = item.keys[key];

            if (!subject) {
                continue;
            }

            if (typeof subject === 'function') {
                subject.call(item.view, event);

                break;
            }

            event.preventDefault();
            event.stopPropagation();

            const methodName = 'action' + Espo.Utils.upperCaseFirst(subject);

            if (typeof item.view[methodName] === 'function') {
                item.view[methodName]();

                break;
            }
        }
    }
}
