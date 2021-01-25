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

define('views/search/filter', 'view', function (Dep) {

    return Dep.extend({

        template: 'search/filter',

        data: function () {
            return {
                name: this.name,
                scope: this.model.name,
                notRemovable: this.options.notRemovable,
            };
        },

        setup: function () {
            var name = this.name = this.options.name;
            var type = this.model.getFieldType(name);

            if (type) {
                var viewName = this.model.getFieldParam(name, 'view') || this.getFieldManager().getViewName(type);

                this.createView('field', viewName, {
                    mode: 'search',
                    model: this.model,
                    el: this.options.el + ' .field',
                    defs: {
                        name: name,
                    },
                    searchParams: this.options.params,
                }, function (view) {
                    this.listenTo(view, 'change', function () {
                        this.trigger('change');
                    }, this);
                });
            }
        },

        populateDefaults: function () {
            var view = this.getView('field');

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

