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

Espo.define('views/inbound-email/fields/target-user-position', 'views/fields/enum', function (Dep) {

    return Dep.extend({

        setup: function () {
            Dep.prototype.setup.call(this);

            this.translatedOptions = {
                '': '--' + this.translate('All') + '--'
            };

            this.params.options = [''];
            if (this.model.get('targetUserPosition') && this.model.get('teamId')) {
                this.params.options.push(this.model.get('targetUserPosition'));
            }

            this.loadRoleList(function () {
                if (this.mode == 'edit') {
                    if (this.isRendered()) {
                        this.render();
                    }
                }
            }, this);

            this.listenTo(this.model, 'change:teamId', function () {
                this.loadRoleList(function () {
                    this.render();
                }, this);
            }, this);
        },

        loadRoleList: function (callback, context) {
            var teamId = this.model.get('teamId');
            if (!teamId) {
                this.params.options = [''];
            }

            this.getModelFactory().create('Team', function (team) {
                team.id = teamId;

                this.listenToOnce(team, 'sync', function () {
                    this.params.options = team.get('positionList') || [];
                    this.params.options.unshift('');
                    callback.call(context);
                }, this);

                team.fetch();
            }, this);

        },

    });
});
