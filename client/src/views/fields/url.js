/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

Espo.define('views/fields/url', 'views/fields/varchar', function (Dep) {

    return Dep.extend({

        type: 'url',

        listTemplate: 'fields/url/list',

        detailTemplate: 'fields/url/detail',

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
                    this.$element.on('change', function () {
                        var value = this.$element.val() || '';
                        value = this.strip(value);
                        this.$element.val(value);
                    }.bind(this));
                }
            }
        },

        strip: function (value) {
            value = value.trim();
            if (value.indexOf('http://') === 0) {
                value = value.substr(7);
            } else if (value.indexOf('https://') === 0) {
                value = value.substr(8);
            }
            value = value.replace(/\/+$/, '');
            return value;
        },

        getUrl: function () {
            var url = this.model.get(this.name);
            if (url && url != '') {
                if (!(url.indexOf('http://') === 0) && !(url.indexOf('https://') === 0)) {
                    url = 'http://' + url;
                }
                return url;
            }
            return url;
        },

        fetch: function () {
            var data = Dep.prototype.fetch.call(this);

            if (this.params.strip && data[this.name]) {
                data[this.name] = this.strip(data[this.name]);
            }
            return data;
        }

    });
});
