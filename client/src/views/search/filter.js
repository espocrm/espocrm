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

define('views/search/filter', ['view'], function (Dep) {

    /**
     * @class
     * @name Class
     * @extends module:view.Class
     * @memberOf module:views/search/filter
     */
    return Dep.extend(/** @lends module:views/search/filter.Class# */{

        template: 'search/filter',

        data: function () {
            return {
                name: this.name,
                scope: this.model.name,
                notRemovable: this.options.notRemovable,
            };
        },

        setup: function () {
            let name = this.name = this.options.name;
            let type = this.model.getFieldType(name);

            if (type) {
                let viewName = this.model.getFieldParam(name, 'view') ||
                    this.getFieldManager().getViewName(type);

                this.createView('field', viewName, {
                    mode: 'search',
                    model: this.model,
                    el: this.options.el + ' .field',
                    defs: {
                        name: name,
                    },
                    searchParams: this.options.params,
                }, (view) => {
                    this.listenTo(view, 'change', () => {
                        this.trigger('change');
                    });

                    this.listenTo(view, 'search', () => {
                        this.trigger('search');
                    });
                });
            }
        },

        /**
         * @return {module:views/fields/base.Class}
         */
        getFieldView: function () {
            return this.getView('field');
        },

        populateDefaults: function () {
            let view = this.getView('field');

            if (!view) {
                return;
            }

            if (!('populateSearchDefaults' in view)) {
                return;
            }

            view.populateSearchDefaults();
        },
    });
});
