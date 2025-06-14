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

import ArrayFieldView from 'views/fields/array';
import ReactionsHelper from 'helpers/misc/reactions';

// noinspection JSUnusedGlobalSymbols
export default class extends ArrayFieldView {

    /**
     * @type {Object.<string, string>}
     * @private
     */
    iconClassMap

    /**
     * @private
     * @type {ReactionsHelper}
     */
    reactionsHelper

    setup() {
        this.reactionsHelper = new ReactionsHelper();

        this.iconClassMap = this.reactionsHelper.getDefinitionList().reduce((o, it) => {
            o[it.type] = it.iconClass;

            return o;
        }, {});

        super.setup();
    }

    setupOptions() {
        const list = this.reactionsHelper.getDefinitionList();

        this.params.options = list.map(it => it.type);

        this.translatedOptions = list.reduce((o, it) => {
            o[it.type] = this.translate(it.type, 'reactions');

            return o;
        }, {});
    }

    /**
     * @param {string} value
     * @return {string}
     */
    getItemHtml(value) {
        const html = super.getItemHtml(value);

        const item = /** @type {HTMLElement} */
            new DOMParser().parseFromString(html, 'text/html').body.childNodes[0];

        const icon = this.createIconElement(value);

        item.prepend(icon);

        return item.outerHTML;
    }

    /**
     * @private
     * @param {string} value
     * @return {HTMLSpanElement}
     */
    createIconElement(value) {
        const icon = document.createElement('span');

        (this.iconClassMap[value] || '')
            .split(' ')
            .filter(it => it)
            .forEach(it => icon.classList.add(it));

        icon.classList.add('text-soft');
        icon.style.display = 'inline-block';
        icon.style.width = 'var(--24px)';

        return icon;
    }

    /**
     * @inheritDoc
     */
    async actionAddItem() {
        const view = await super.actionAddItem();

        view.whenRendered().then(() => {
            const anchors = /** @type {HTMLAnchorElement[]} */
                view.element.querySelectorAll(`a[data-value]`);

            anchors.forEach(a => {
                const icon = this.createIconElement(a.dataset.value);

                a.prepend(icon);
            });
        });

        return view;
    }
}
