/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2026 EspoCRM, Inc.
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

/**
 * @since 9.3.1
 */
export default class LinkFieldIconHelper {

    /**
     * @param {import('views/fields/link').default} view
     * @param {{
     *     iconClass: string,
     *     getIconClass: function(): string|null,
     *     getColor: function(): string,
     * }} options
     */
    constructor(view, options) {
        this.view = view;
        this.options = options;

        view.listenTo(view, 'after:render', () => {
            if (view.isEditMode()) {
                this.control();
            }
        });

        view.addHandler('keydown', `input[data-name="${view.nameName}"]`, (/** KeyboardEvent */e, target) => {
            if (e.code === 'Enter') {
                return;
            }

            target.classList.add('being-typed');
        });

        view.addHandler('change', `input[data-name="${view.nameName}"]`, (e, target) => {
            setTimeout(() => target.classList.remove('being-typed'), 200);
        });

        view.addHandler('blur', `input[data-name="${view.nameName}"]`, (e, target) => {
            target.classList.remove('being-typed');
        });

        view.on('change', () => {
            if (!view.isEditMode()) {
                return;
            }

            const span = view.element.querySelector('span.icon-in-input');

            if (span) {
                span.parentNode.removeChild(span);
            }

            setTimeout(() => this.control(), 0);
        });
    }

    /**
     * @private
     */
    control() {
        const view = this.view;

        const nameElement = view.element.querySelector(`input[data-name="${view.nameName}"]`);
        nameElement.classList.remove('being-typed');

        const icon = document.createElement('span');
        icon.className = 'icon-in-input ' + this.options.iconClass;
        icon.style.color = this.options.getColor();

        const iconClass = this.options.getIconClass();

        if (!iconClass) {
            return;
        }

        icon.className += ' ' + iconClass;

        const input = view.element.querySelector('.input-group > input');

        if (!input) {
            return;
        }

        input.after(icon);
    }
}
