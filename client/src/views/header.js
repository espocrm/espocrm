/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2021 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

define('views/header', 'view', function (Dep) {

    return Dep.extend({

        template: 'header',

        data: function () {
            var data = {};
            if ('getHeader' in this.getParentView()) {
                data.header = this.getParentView().getHeader();
            }

            data.scope = this.scope || this.getParentView().scope;
            data.items = this.getItems();

            var dropdown = (data.items || {}).dropdown || [];

            data.hasVisibleDropdownItems = false;
            dropdown.forEach(function (item) {
                if (!item.hidden) data.hasVisibleDropdownItems = true;
            });

            data.noBreakWords = this.options.fontSizeFlexible;

            data.isXsSingleRow = this.options.isXsSingleRow;

            if ((data.items.buttons || []).length < 2) {
                data.isHeaderAdditionalSpace = true;
            }

            return data;
        },

        setup: function () {
            this.scope = this.options.scope;
            if (this.model) {
                this.listenTo(this.model, 'after:save', function () {
                    if (this.isRendered()) {
                        this.reRender();
                    }
                }, this);
            }
        },

        afterRender: function () {
            if (this.options.fontSizeFlexible) {
                this.adjustFontSize();
            }
        },

        adjustFontSize: function (step) {
            step = step || 0;

            if (!step) this.fontSizePercentage = 100;

            var $container = this.$el.find('.header-breadcrumbs');
            var containerWidth = $container.width();
            var childrenWidth = 0;
            $container.children().each(function (i, el) {
                childrenWidth += $(el).outerWidth(true);
            });

            if (containerWidth < childrenWidth) {
                if (step > 7) {
                    $container.addClass('overlapped');
                    this.$el.find('.title').each(function (i, el) {
                        var $el = $(el);
                        var text = $(el).text();
                        $el.attr('title', text);

                        var isInitialized = false;
                        $el.on('touchstart', function () {
                            if (!isInitialized) {
                                $el.attr('title', '');
                                isInitialized = true;
                                Espo.Ui.popover($el, {
                                    content: text,
                                    noToggleInit: true,
                                }, this);
                            }
                            $el.popover('toggle');
                        }.bind(this));
                    }.bind(this));
                    return;
                }

                var fontSizePercentage = this.fontSizePercentage -= 4;
                var $flexible = this.$el.find('.font-size-flexible');
                $flexible.css('font-size', this.fontSizePercentage + '%');

                $flexible.css('position', 'relative');

                if (step > 6) {
                    $flexible.css('top', '-1px');
                } else if (step > 4) {
                    $flexible.css('top', '-1px');
                }

                this.adjustFontSize(step + 1);
            }
        },

        getItems: function () {
            var items = this.getParentView().getMenu() || {};

            return items;
        },
    });
});
