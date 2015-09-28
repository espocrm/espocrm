/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2015 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
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
 ************************************************************************/

Espo.define('Views.Fields.Varchar', 'Views.Fields.Base', function (Dep) {

    return Dep.extend({

        type: 'varchar',

        searchTemplate: 'fields.varchar.search',

        setupSearch: function () {
            this.searchParams.typeOptions = ['startsWith', 'contains', 'equals'];
        },

        fetch: function () {
            var data = {};
            var value = this.$element.val();
            if (this.params.trim) {
                if (typeof value.trim === 'function') {
                    value = value.trim();
                }
            }
            data[this.name] = value;
            return data;
        },

        fetchSearch: function () {
            var value = this.$element.val();
            if (typeof value.trim === 'function') {
                value = value.trim();
            }
            var type = this.$el.find('[name="'+this.name+'-type"]').val() || 'startsWith';
            if (value) {
                var data = {
                    value: value,
                    type: type
                }
                return data;
            }
            return false;
        }

    });
});

