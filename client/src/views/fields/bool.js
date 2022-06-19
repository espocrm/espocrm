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

define('views/fields/bool', ['views/fields/base'], function (Dep) {

    /**
     * A boolean field (checkbox).
     *
     * @class
     * @name Class
     * @extends module:views/fields/base.Class
     * @memberOf module:views/fields/bool
     */
    return Dep.extend(/** @lends module:views/fields/bool.Class# */{

        type: 'bool',

        listTemplate: 'fields/bool/list',

        detailTemplate: 'fields/bool/detail',

        editTemplate: 'fields/bool/edit',

        searchTemplate: 'fields/bool/search',

        validations: [],

        initialSearchIsNotIdle: true,

        data: function () {
            let data = Dep.prototype.data.call(this);

            data.valueIsSet = this.model.has(this.name);

            return data;
        },

        afterRender: function () {
            Dep.prototype.afterRender.call(this);

            if (this.mode === 'search') {
                this.$element.on('change', () => {
                    this.trigger('change');
                });
            }
        },

        fetch: function () {
            var value = this.$element.get(0).checked;

            var data = {};

            data[this.name] = value;

            return data;
        },

        fetchSearch: function () {
            var type = this.$element.val();

            if (!type) {
                return;
            }

            if (type === 'any') {
                return {
                    type: 'or',
                    value: [
                        {
                            type: 'isTrue',
                            attribute: this.name,

                        },
                        {
                            type: 'isFalse',
                            attribute: this.name,
                        },
                    ],
                    data: {
                        type: type,
                    },
                };
            }

            var data = {
                type: type,
                data: {
                    type: type,
                },
            };

            return data;
        },

        getSearchType: function () {
            return this.getSearchParamsData().type || this.searchParams.type || 'isTrue';
        },
    });
});
