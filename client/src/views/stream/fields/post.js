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

import TextFieldView from 'views/fields/text';

export default class StreamPostFieldView extends TextFieldView {

    data() {
        const data = super.data();

        if (this.isDetailMode() || this.isListMode()) {
            data.htmlValue = this.getTransformedValue();
        }

        return data;
    }

    /**
     * @private
     * @return {Handlebars.SafeString|string}
     */
    getTransformedValue() {
        let text = super.getValueForDisplay();

        if (typeof text !== 'string' && !(text instanceof String)) {
            return '';
        }

        /** @type {Record} */
        const data = this.model.attributes.data || {}

        const mentionData = /** @type {Record.<string, {id: string, name: string}>} */
            data.mentions || {};

        const items = Object.keys(mentionData).sort((a, b) => b.length - a.length);

        if (!items.length) {
            return this.getHelper().transformMarkdownText(text);
        }

        items.forEach(item => {
            const name = mentionData[item].name;
            const id = mentionData[item].id;

            const part = `[${name}](#User/view/${id})`;

            text = text.replace(new RegExp(item, 'g'), part);
        });

        let html = this.getHelper().transformMarkdownText(text).toString();

        const body = new DOMParser().parseFromString(html, 'text/html').body;

        items.forEach(item => {
            const id = mentionData[item].id;
            const url = `#User/view/${id}`;

            const avatarHtml = this.getHelper().getAvatarHtml(id, 'small', 16, 'avatar-link');

            if (!avatarHtml) {
                return;
            }

            const img = new DOMParser().parseFromString(avatarHtml, 'text/html').body.childNodes[0];

            body.querySelectorAll(`a[href="${url}"]`).forEach(a => {
                if (id === this.getUser().id) {
                    a.classList.add('text-warning');
                }

                const span = document.createElement('span');
                span.classList.add('nowrap', 'name-avatar');

                span.append(img.cloneNode());

                a.parentNode.replaceChild(span, a);

                span.append(a);
            });
        });

        html = body.innerHTML;

        return html;
    }
}
