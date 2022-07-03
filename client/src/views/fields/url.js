/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2022 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

define('views/fields/url', ['views/fields/varchar', 'lib!underscore'], function (Dep, _) {

    return Dep.extend({

        type: 'url',

        listTemplate: 'fields/url/list',

        detailTemplate: 'fields/url/detail',

        defaultProtocol: 'https:',

        validations: [
            'required',
            'valid',
        ],

        setup: function () {
            Dep.prototype.setup.call(this);

            this.params.trim = true;
        },

        data: function () {
            return _.extend({
                url: this.getUrl()
            }, Dep.prototype.data.call(this));
        },

        afterRender: function () {
            Dep.prototype.afterRender.call(this);

            if (this.mode === 'edit') {
                if (this.params.strip) {
                    this.$element.on('change', () => {
                        var value = this.$element.val() || '';

                        value = this.strip(value);

                        this.$element.val(value);
                    });
                }
            }
        },

        strip: function (value) {
            value = value.trim();

            if (value.indexOf('//') !== -1) {
                value = value.substr(value.indexOf('//') + 2);
            }

            value = value.replace(/\/+$/, '');

            return value;
        },

        getUrl: function () {
            var url = this.model.get(this.name);

            if (url && url !== '') {
                if (url.indexOf('//') === -1) {
                    url = this.defaultProtocol + '//' + url;
                }

                return url;
            }

            return url;
        },

        validateValid: function () {
            let value = this.model.get(this.name);

            if (!value) {
                return false;
            }

            /** @var {string} */
            let pattern = this.getMetadata().get(['app', 'regExpPatterns', 'uriOptionalProtocol', 'pattern']);

            let regExp = new RegExp('^' + pattern + '$');

            if (regExp.test(value)) {
                return false;
            }

            let msg = this.translate('fieldInvalid', 'messages')
                .replace('{field}', this.translate(this.name, 'fields', this.entityType));

            this.showValidationMessage(msg, '[data-name="' + name + '"]');

            return true;
        },

        fetch: function () {
            var data = Dep.prototype.fetch.call(this);

            if (this.params.strip && data[this.name]) {
                data[this.name] = this.strip(data[this.name]);
            }

            return data;
        },
    });
});
