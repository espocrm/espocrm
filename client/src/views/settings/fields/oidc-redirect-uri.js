/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2023 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
 * Website: https://www.espocrm.com
 *
 * EspoCRM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * EspoCRM is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with EspoCRM. If not, see http://www.gnu.org/licenses/.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

define('views/settings/fields/oidc-redirect-uri', ['views/fields/varchar'], function (Dep) {

    return Dep.extend({

        detailTemplateContent: `
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
        `,

        portalCollection: null,

        data: function () {
            let isNotEmpty = this.model.entityType !== 'AuthenticationProvider' ||
                this.portalCollection;

            return {
                value: this.getValueForDisplay(),
                isNotEmpty: isNotEmpty,
            };
        },

        /**
         * @protected
         */
        copyToClipboard: function () {
            let value = this.getValueForDisplay();

            navigator.clipboard.writeText(value).then(() => {
                Espo.Ui.success(this.translate('Copied to clipboard'));
            });
        },

        getValueForDisplay: function () {
            if (this.model.entityType === 'AuthenticationProvider') {
                if (!this.portalCollection) {
                    return null;
                }

                return this.portalCollection.models
                    .map(model => {
                        let url = (model.get('url') || '').replace(/\/+$/, '');

                        return url + '/oauth-callback.php';
                    })
                    .join('\n');
            }

            let siteUrl = (this.getConfig().get('siteUrl') || '').replace(/\/+$/, '');

            return siteUrl + '/oauth-callback.php';
        },

        setup: function () {
            Dep.prototype.setup.call(this);

            if (this.model.entityType === 'AuthenticationProvider') {
                this.getCollectionFactory()
                    .create('Portal')
                    .then(collection => {
                        collection.data.select = ['url', 'isDefault'];

                        collection.fetch().then(() => {
                            this.portalCollection = collection;

                            this.reRender();
                        })
                    });
            }
        },
    });
});
