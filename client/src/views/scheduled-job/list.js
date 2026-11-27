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

import ListView from 'views/list';
import Ajax from 'ajax';

export default class extends ListView {

    setup() {
        super.setup();

        this.addMenuItem('buttons', {
            link: '#Admin/jobs',
            text: this.translate('Jobs', 'labels', 'Admin'),
        });
    }

    afterRender() {
        super.afterRender();

        const searchElement = this.element.querySelector('.list-container');

        if (searchElement) {
            searchElement.after(
                (() => {
                    const template = document.createElement('template');

                    template.innerHTML = `
                        <div
                            class="cronjob well"
                            style="
                                margin: var(--vertical-gap) auto var(--vertical-gap) auto;
                                max-width: var(--900px);
                            "
                        >
                            <div class="message">&nbsp;</div>
                            <div class="margin-top">
                                <code class="command">&nbsp;</code>
                            </div>
                        </div>
                    `;

                    return template.content.firstElementChild;
                })()
            );
        }

        Ajax.getRequest('Admin/action/cronMessage')
            .then(data => {
                const message = this.getHelper().transformMarkdownText(data.message ?? '', {
                    linksInNewTab: true,
                });

                const messageElement = this.element.querySelector('.cronjob .message');
                const commandElement = this.element.querySelector('.cronjob .command');

                if (messageElement) {
                    messageElement.innerHTML = message.toString();
                }

                if (commandElement) {
                    commandElement.innerText = data.command;
                }
            });
    }

    getHeader() {
        return this.buildHeaderHtml([
            $('<a>')
                .attr('href', '#Admin')
                .text(this.translate('Administration', 'labels', 'Admin')),
            this.getLanguage().translate(this.scope, 'scopeNamesPlural')
        ]);
    }
}
