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

define('views/address-map/view', 'views/main', function (Dep) {

    return Dep.extend({

        templateContent: '<div class="header page-header">{{{header}}}</div><div class="map-container">{{{map}}}</div>',

        setup: function () {
            var field = this.options.field;

            this.scope = this.model.entityType;

            this.headerHtml = this.getHelper().sanitizeHtml(
                this.translate(field, 'fields', this.model.entityType)
            );

            this.createView('header', 'views/header', {
                model: this.model,
                el: '#main > .header',
                scope: this.model.entityType,
                fontSizeFlexible: true,
            });
        },

        afterRender: function () {
        	var field = this.options.field;

            var viewName = this.model.getFieldParam(field + 'Map', 'view') || this.getFieldManager().getViewName('map');

            this.createView('map', viewName, {
                model: this.model,
                name: field + 'Map',
                el: this.getSelector() + ' .map-container',
                height: this.getHelper().calculateContentContainerHeight(this.$el.find('.map-container')),
            }, function (view) {
            	view.render();
            });
        },

        getHeader: function () {
            var name = Handlebars.Utils.escapeExpression(this.model.get('name'));

            if (name === '') {
                name = this.model.id;
            }

            name = '<span class="font-size-flexible title">' + name + '</span>';

            if (this.model.get('deleted')) {
                name = '<span style="text-decoration: line-through;">' + name + '</span>';
            }

            var rootUrl = this.options.rootUrl || this.options.params.rootUrl || '#' + this.model.entityType;

            var headerIconHtml = this.getHeaderIconHtml();

            return this.buildHeaderHtml([
                headerIconHtml +
                    '<a href="' + rootUrl + '" class="action" data-action="navigateToRoot">' +
                    this.getLanguage().translate(this.model.entityType, 'scopeNamesPlural') + '</a>',
                name,
                this.translate(this.options.field, 'fields', this.model.entityType)
            ]);
        },

    });
});
