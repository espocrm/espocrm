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

Espo.define('views/admin/field-manager/fields/options-with-style', 'views/admin/field-manager/fields/options', function (Dep) {

    return Dep.extend({

        setup: function () {
            Dep.prototype.setup.call(this);

            this.optionsStyleMap = this.model.get('style') || {};

            this.styleList = ['default', 'success', 'danger', 'warning', 'info', 'primary'];

            this.events['click [data-action="selectOptionItemStyle"]'] = function (e) {
                var $target = $(e.currentTarget);
                var style = $target.data('style');
                var value = $target.data('value').toString();

                this.changeStyle(value, style);
            };

        },

        changeStyle: function (value, style) {
            var valueInternal = value.replace(/"/g, '\\"');

            this.$el.find('[data-action="selectOptionItemStyle"][data-value="'+valueInternal+'"] .check-icon').addClass('hidden');
            this.$el.find('[data-action="selectOptionItemStyle"][data-value="'+valueInternal+'"][data-style="'+style+'"] .check-icon').removeClass('hidden');

            var $item = this.$el.find('.list-group-item[data-value="'+valueInternal+'"]').find('.item-text');

            this.styleList.forEach(function (item) {
                $item.removeClass('text-' + item);
            }, this);

            $item.addClass('text-' + style);

            if (style === 'default') {
                style = null;
            }
            this.optionsStyleMap[value] = style;
        },

        getItemHtml: function (value) {
            var html = Dep.prototype.getItemHtml.call(this, value);

            if (!value) return html;

            var valueSanitized = this.escapeValue(value);
            var valueInternal = this.escapeValue(value);

            var $item = $(html);

            var itemListHtml = '';
            var styleList = this.styleList;

            var styleMap = this.optionsStyleMap;

            var style = 'default';

            styleList.forEach(function (item) {
                var hiddenPart = ' hidden';
                if (styleMap[value] === item) {
                    hiddenPart = '';
                    style = item;
                } else {
                    if (item === 'default' && !styleMap[value]) {
                        hiddenPart = '';
                    }
                }
                var translated = this.getLanguage().translateOption(item, 'style', 'LayoutManager');
                var innerHtml = '<span class="check-icon fas fa-check pull-right'+hiddenPart+'"></span><div class="text-'+item+'">'+translated+'</div>';
                itemListHtml += '<li><a href="javascript:" data-action="selectOptionItemStyle" data-style="'+item+'" data-value="'+valueInternal+'">'+innerHtml+'</a></li>'
            }, this);

            var dropdownHtml =
                '<div class="btn-group pull-right">' +
                '<button type="button" class="btn btn-link btn-sm dropdown-toggle" data-toggle="dropdown"><span class="caret"></span></button>' +
                '<ul class="dropdown-menu pull-right">'+itemListHtml+'</ul>' +
                '</div>';

            $item.find('.item-content > input').after($(dropdownHtml));

            $item.find('.item-text').addClass('text-' + style);

            $item.addClass('link-group-item-with-columns');

            return $item.get(0).outerHTML;
        },

        fetch: function () {
            var data = Dep.prototype.fetch.call(this);

            data.style = {};

            (data.options || []).forEach(function (item) {
                data.style[item] = this.optionsStyleMap[item] || null;
            }, this);

            return data;
        },

    });
});
