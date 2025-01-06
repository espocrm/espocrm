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

import VarcharFieldView from 'views/fields/varchar';

// noinspection JSUnusedGlobalSymbols
export default class extends VarcharFieldView {

    detailTemplateContent = `
        {{#if isNotEmpty}}
            <a
                role="button"
                data-action="copyToClipboard"
                class="pull-right text-soft"
                title="{{translate 'Copy to Clipboard'}}"
            ><span class="far fa-copy"></span></a>
            {{value}}
        {{else}}
            <span class="none-value">{{translate 'None'}}</span>
        {{/if}}
    `

    portalCollection = null

    data() {
        const isNotEmpty = this.model.entityType !== 'AuthenticationProvider' ||
            this.portalCollection;

        return {
            value: this.getValueForDisplay(),
            isNotEmpty: isNotEmpty,
        };
    }

    /**
     * @protected
     */
    copyToClipboard() {
        const value = this.getValueForDisplay();

        navigator.clipboard.writeText(value).then(() => {
            Espo.Ui.success(this.translate('Copied to clipboard'));
        });
    }

    getValueForDisplay() {
        if (this.model.entityType === 'AuthenticationProvider') {
            if (!this.portalCollection) {
                return null;
            }

            return this.portalCollection.models
                .map(model => {
                    const file = 'oauth-callback.php'
                    const url = (model.get('url') || '').replace(/\/+$/, '') + `/${file}`;

                    const checkPart = `/portal/${model.id}/${file}`;

                    if (!url.endsWith(checkPart)) {
                        return url;
                    }

                    return url.slice(0, - checkPart.length) + `/portal/${file}`;
                })
                .join('\n');
        }

        const siteUrl = (this.getConfig().get('siteUrl') || '').replace(/\/+$/, '');

        return siteUrl + '/oauth-callback.php';
    }

    setup() {
        super.setup();

        if (this.model.entityType === 'AuthenticationProvider') {
            this.getCollectionFactory().create('Portal')
                .then(collection => {
                    collection.data.select = ['url', 'isDefault'].join(',');

                    collection.fetch().then(() => {
                        this.portalCollection = collection;

                        this.reRender();
                    })
                });
        }
    }
}
