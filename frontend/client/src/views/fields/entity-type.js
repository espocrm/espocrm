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

Espo.define('views/fields/entity-type', 'views/fields/enum', function (Dep) {

    return Dep.extend({

        checkAvailability: function (entityType) {
            if (this.scopesMetadataDefs[entityType].entity) {
                return true;
            }
        },

        setupOptions: function () {
            var scopes = this.scopesMetadataDefs = this.getMetadata().get('scopes');
            this.params.options = Object.keys(scopes).filter(function (scope) {
                if (this.checkAvailability(scope)) {
                    return true;
                }
            }.bind(this)).sort(function (v1, v2) {
                 return this.translate(v1, 'scopeNames').localeCompare(this.translate(v2, 'scopeNames'));
            }.bind(this));
            this.params.options.unshift('');
        },

        setup: function () {
            this.params.translation = 'Global.scopeNames';
            this.setupOptions();
            Dep.prototype.setup.call(this);
        }

    });
});

