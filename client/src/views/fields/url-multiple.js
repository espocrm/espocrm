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

import Dep from 'views/fields/array';

/**
 * An Url-Multiple field.
 *
 * @class Class
 * @extends module:views/fields/array
 */
export default Dep.extend(/** @lends Class# */{

    type: 'urlMultiple',

    maxItemLength: 255,
    displayAsList: true,
    defaultProtocol: 'https:',

    setup: function () {
        Dep.prototype.setup.call(this);

        this.noEmptyString = true;
        this.params.pattern = '$uriOptionalProtocol';
    },

    addValueFromUi: function (value) {
        value = value.trim();

        if (this.params.strip) {
            value = this.strip(value);
        }

        if (value === decodeURI(value)) {
            value = encodeURI(value);
        }

        Dep.prototype.addValueFromUi.call(this, value);
    },

    /**
     * @param {string} value
     * @return {string}
     */
    strip: function (value) {
        if (value.indexOf('//') !== -1) {
            value = value.substring(value.indexOf('//') + 2);
        }

        value = value.replace(/\/+$/, '');

        return value;
    },

    prepareUrl: function (url) {
        if (url.indexOf('//') === -1) {
            url = this.defaultProtocol + '//' + url;
        }

        return url;
    },

    getValueForDisplay: function () {
        /** @type {JQuery[]} */
        let $list = this.selected.map(value => {
            return $('<a>')
                .attr('href', this.prepareUrl(value))
                .attr('target', '_blank')
                .text(decodeURI(value));
        });

        return $list
            .map($item =>
                $('<div>')
                    .addClass('multi-enum-item-container')
                    .append($item)
                    .get(0).outerHTML
            )
            .join('');
    },

    getItemHtml: function (value) {
        let html = Dep.prototype.getItemHtml.call(this, value);

        let $item = $(html);

        $item.find('span.text').html(
            $('<a>')
                .attr('href', this.prepareUrl(value))
                .css('user-drag', 'none')
                .attr('target', '_blank')
                .text(decodeURI(value))
        );

        return $item.get(0).outerHTML;
    },
});
