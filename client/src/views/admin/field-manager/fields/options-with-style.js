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

import OptionsView from 'views/admin/field-manager/fields/options';

export default class extends OptionsView {

    setup() {
        super.setup();

        this.optionsStyleMap = this.model.get('style') || {};

        this.styleList = [
            'default',
            'success',
            'danger',
            'warning',
            'info',
            'primary',
        ];

        this.addActionHandler('selectOptionItemStyle', (e, target) => {
            const style = target.dataset.style;
            const value = target.dataset.value;

            this.changeStyle(value, style);
        })
    }

    changeStyle(value, style) {
        const val = CSS.escape(value);

        this.$el
            .find(`[data-action="selectOptionItemStyle"][data-value="${val}"] .check-icon`)
            .addClass('hidden');

        this.$el
            .find(`[data-action="selectOptionItemStyle"][data-value="${val}"][data-style="${style}"] .check-icon`)
            .removeClass('hidden');

        const $item = this.$el.find(`.list-group-item[data-value="${val}"]`).find('.item-text');

        this.styleList.forEach(item => {
            $item.removeClass('text-' + item);
        });

        $item.addClass('text-' + style);

        if (style === 'default') {
            style = null;
        }

        this.optionsStyleMap[value] = style;
    }

    getItemHtml(value) {
        // Do not use the `html` method to avoid XSS.

        const html = super.getItemHtml(value);

        const styleList = this.styleList;
        const styleMap = this.optionsStyleMap;

        let style = 'default';
        const $liList = [];

        styleList.forEach(item => {
            let isHidden = true;

            if (styleMap[value] === item) {
                style = item;
                isHidden = false;
            }
            else {
                if (item === 'default' && !styleMap[value]) {
                    isHidden = false;
                }
            }

            const text = this.getLanguage().translateOption(item, 'style', 'LayoutManager');

            const $li = $('<li>')
                .append(
                    $('<a>')
                        .attr('role', 'button')
                        .attr('tabindex', '0')
                        .attr('data-action', 'selectOptionItemStyle')
                        .attr('data-style', item)
                        .attr('data-value', value)
                        .append(
                            $('<span>')
                                .addClass('check-icon fas fa-check pull-right')
                                .addClass(isHidden ? 'hidden' : ''),
                            $('<div>')
                                .addClass(`text-${item}`)
                                .text(text)
                        )
                );

            $liList.push($li);
        });

        const $dropdown = $('<div>')
            .addClass('btn-group pull-right')
            .append(
                $('<button>')
                    .addClass('btn btn-link btn-sm dropdown-toggle')
                    .attr('type', 'button')
                    .attr('data-toggle', 'dropdown')
                    .append(
                        $('<span>').addClass('caret')
                    ),
                $('<ul>')
                    .addClass('dropdown-menu pull-right')
                    .append($liList)
            );

        const $item = $(html);

        $item.find('.item-content > input').after($dropdown);
        $item.find('.item-text').addClass(`text-${style}`);
        $item.addClass('link-group-item-with-columns');

        return $item.get(0).outerHTML;
    }

    fetch() {
        const data = super.fetch();

        data.style = {};

        (data.options || []).forEach(item => {
            data.style[item] = this.optionsStyleMap[item] || null;
        });

        return data;
    }
}
