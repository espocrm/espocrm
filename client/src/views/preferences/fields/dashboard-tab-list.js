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

// noinspection JSUnusedGlobalSymbols
export default class extends ArrayFieldView {

    maxItemLength = 36

    setup() {
        super.setup();

        this.translatedOptions = {};

        const list = this.model.get(this.name) || [];

        list.forEach(value => {
            this.translatedOptions[value] = value;
        });

        this.validations.push('uniqueLabel');
    }

    getItemHtml(value) {
        value = value.toString();

        const translatedValue = this.translatedOptions[value] || value;

        return $('<div>')
            .addClass('list-group-item link-with-role form-inline')
            .attr('data-value', value)
            .append(
                $('<div>')
                    .addClass('pull-left')
                    .css('width', '92%')
                    .css('display', 'inline-block')
                    .append(
                        $('<input>')
                            .attr('maxLength', this.maxItemLength)
                            .attr('data-name', 'translatedValue')
                            .attr('data-value', value)
                            .addClass('role form-control input-sm')
                            .attr('value', translatedValue)
                            .css('width', '65%')
                    )
            )
            .append(
                $('<div>')
                    .css('width', '8%')
                    .css('display', 'inline-block')
                    .css('vertical-align', 'top')
                    .append(
                        $('<a>')
                            .attr('role', 'button')
                            .attr('tabindex', '0')
                            .addClass('pull-right')
                            .attr('data-value', value)
                            .attr('data-action', 'removeValue')
                            .append(
                                $('<span>').addClass('fas fa-times')
                            )
                    )
            )
            .append(
                $('<br>').css('clear', 'both')
            )
            .get(0).outerHTML;
    }

    /**
     * @private
     * @return {boolean}
     */
    validateUniqueLabel() {
        const keyList = this.model.get(this.name) || [];
        const labels = this.model.get('translatedOptions') || {};
        const metLabelList = [];

        for (const key of keyList) {
            const label = labels[key];

            if (!label) {
                return true;
            }

            if (metLabelList.indexOf(label) !== -1) {
                return true;
            }

            metLabelList.push(label);
        }

        return false;
    }

    fetch() {
        const data = super.fetch();

        data.translatedOptions = {};

        (data[this.name] || []).forEach(value => {
            const valueInternal = CSS.escape(value);

            data.translatedOptions[value] = this.$el
                .find(`input[data-name="translatedValue"][data-value="${valueInternal}"]`)
                .val() || value;
        });

        return data;
    }
}
