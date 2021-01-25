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

Espo.define('views/user/record/list', 'views/record/list', function (Dep) {

    return Dep.extend({

        quickEditDisabled: true,

        rowActionsView: 'views/user/record/row-actions/default',

        massActionList: ['remove', 'massUpdate', 'export'],

        checkAllResultMassActionList: ['massUpdate', 'export'],

        setupMassActionItems: function () {
            Dep.prototype.setupMassActionItems.call(this);

            if (this.scope === 'ApiUser') {
                this.removeMassAction('massUpdate');
                this.removeMassAction('export');

                this.layoutName = 'listApi';
            }
            if (this.scope === 'PortalUser') {
                this.layoutName = 'listPortal';
            }
            if (!this.getUser().isAdmin()) {
                this.removeMassAction('massUpdate');
                this.removeMassAction('export');
            }
        },

        getModelScope: function (id) {
            var model = this.collection.get(id);

            if (model.isPortal()) {
                return 'PortalUser';
            }
            return this.scope;
        }
    });

});

