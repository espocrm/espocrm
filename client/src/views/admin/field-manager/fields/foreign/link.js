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

define('views/admin/field-manager/fields/foreign/link', 'views/fields/enum', function (Dep) {

    return Dep.extend({

        setup: function () {
            Dep.prototype.setup.call(this);
            if (!this.model.isNew()) {
                this.setReadOnly(true);
            }
        },

        setupOptions: function () {
            var links = this.getMetadata().get(['entityDefs', this.options.scope, 'links']) || {};

            this.params.options = Object.keys(Espo.Utils.clone(links)).filter(function (item) {
                if (links[item].type !== 'belongsTo' && links[item].type !== 'hasOne') return;
                if (links[item].noJoin) return;

                return true;
            }, this);

            var scope = this.options.scope;

            this.translatedOptions = {};
            this.params.options.forEach(function (item) {
                this.translatedOptions[item] = this.translate(item, 'links', scope);
            }, this);

            this.params.options = this.params.options.sort(function (v1, v2) {
                return this.translate(v1, 'links', scope).localeCompare(this.translate(v2, 'links', scope));
            }.bind(this));

            this.params.options.unshift('');
        },
    });

});
