/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2018 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/
Espo.define('views/dashlets/fields/records/bool-filter-list', 'views/fields/multi-enum', function (Dep) {

    return Dep.extend({

        setup: function () {
            Dep.prototype.setup.call(this);

            this.listenTo(this.model, 'change:entityType', function () {
                this.setupOptions();
                this.reRender();
            }, this);
        },

        setupOptions: function () {
            var entityType = this.model.get('entityType');
            if (!entityType) {
                this.params.options = [];
                return;
            }
            this.params.options = this.getMetadata().get(['clientDefs', entityType, 'boolFilterList']) || [];

            if (this.getMetadata().get(['scopes', entityType, 'stream']) && this.getAcl().checkScope(entityType, 'stream')) {
                this.params.options.push('followed');
            }

            this.translatedOptions = {};
            this.params.options.forEach(function (item) {
                this.translatedOptions[item] = this.translate(item, 'boolFilters', entityType);
            }, this);
        }

    });

});
