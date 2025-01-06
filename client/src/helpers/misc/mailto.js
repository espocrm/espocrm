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

class MailtoHelper {

    /**
     * @param {import('models/settings').default} config
     * @param {import('models/preferences').default} preferences
     * @param {import('acl-manager').default} acl
     */
    constructor(config, preferences, acl) {
        this.config = config;
        this.preferences = preferences;
        this.acl = acl;
    }

    /**
     * Whether mailto should be used.
     *
     * @return {boolean}
     */
    toUse() {
        return this.config.get('emailForceUseExternalClient') ||
            this.preferences.get('emailUseExternalClient') ||
            !this.acl.checkScope('Email', 'create');
    }

    /**
     * Compose a mailto link.
     *
     * @param {Record} attributes
     * @return {string}
     */
    composeLink(attributes) {
        let link = 'mailto:';

        link += (attributes.to || '').split(';').join(',');

        const params = {};

        if (attributes.cc) {
            params.cc = attributes.cc.split(';').join(',');
        }

        let bcc = this.config.get('outboundEmailBccAddress');

        if (attributes.bcc) {
            if (!bcc) {
                bcc = '';
            } else {
                bcc += ';';
            }

            bcc += attributes.bcc;
        }

        if (bcc) {
            params.bcc = bcc.split(';').join(',');
        }

        if (attributes.name) {
            params.subject = attributes.name;
        }

        if (attributes.body) {
            params.body = /** @type {string} */attributes.body;

            if (attributes.isHtml) {
                params.body = this.htmlToPlain(params.body);
            }

            if (params.body.length > 700) {
                params.body = params.body.substring(0, 700) + '...';
            }
        }

        if (attributes.inReplyTo) {
            params['In-Reply-To'] = attributes.inReplyTo;
        }

        let part = '';

        for (const key in params) {
            if (part !== '') {
                part += '&';
            }
            else {
                part += '?';
            }

            part += key + '=' + encodeURIComponent(params[key]);
        }

        link += part;

        return link;
    }

    /**
     * @private
     * @param {string} text
     * @returns {string}
     */
    htmlToPlain(text) {
        text = text || '';

        let value = text.replace(/<br\s*\/?>/mg, '\n');

        value = value.replace(/<\/p\s*\/?>/mg, '\n\n');

        const $div = $('<div>').html(value);

        $div.find('style').remove();
        $div.find('link[ref="stylesheet"]').remove();

        value =  $div.text();

        return value;
    }
}

export default MailtoHelper;
