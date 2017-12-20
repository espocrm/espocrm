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

Espo.define('crm:views/opportunity/fields/stage', 'views/fields/enum', function (Dep) {

    return Dep.extend({

        listTemplate: 'fields/enum-styled/detail',

        detailTemplate: 'fields/enum-styled/detail',

        data: function () {
            var style = 'default';
            var stage = this.model.get('stage');
            if (stage == 'Closed Won') {
                style = 'success';
            } else if (stage == 'Closed Lost') {
                style = 'danger';
            }
            return _.extend({
                style: style,
            }, Dep.prototype.data.call(this));
        },

        setup: function () {
            Dep.prototype.setup.call(this);

            this.probabilityMap = this.getMetadata().get('entityDefs.Opportunity.fields.stage.probabilityMap') || {};

            if (this.mode != 'list') {
                this.on('change', function () {
                    this.model.set('probability', this.probabilityMap[this.model.get(this.name)]);
                }, this);
            }
        }

    });

});
