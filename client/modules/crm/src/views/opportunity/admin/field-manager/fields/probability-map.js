/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2024 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
 * Website: https://www.espocrm.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU Affero General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU Affero General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

define('crm:views/opportunity/admin/field-manager/fields/probability-map', ['views/fields/base'], function (Dep) {

    return Dep.extend({

        editTemplate: 'crm:opportunity/admin/field-manager/fields/probability-map/edit',

        setup: function () {
            Dep.prototype.setup.call(this);

            this.listenTo(this.model, 'change:options', function (m, v, o) {
                let probabilityMap = this.model.get('probabilityMap') || {}

                if (o.ui) {
                    (this.model.get('options') || []).forEach(item => {
                        if (!(item in probabilityMap)) {
                            probabilityMap[item] = 50;
                        }
                    });

                    this.model.set('probabilityMap', probabilityMap);
                }

                this.reRender();
            });
        },

        data: function () {
            let data = {};

            let values = this.model.get('probabilityMap') || {};

            data.stageList = this.model.get('options') || [];
            data.values = values;

            return data;
        },

        fetch: function () {
            let data = {
                probabilityMap: {},
            };

            (this.model.get('options') || []).forEach(item => {
                data.probabilityMap[item] = parseInt(this.$el.find('input[data-name="'+item+'"]').val());
            });

            return data;
        },

        afterRender: function () {
            this.$el.find('input').on('change', () => {
                this.trigger('change')
            });
        },
    });
});
