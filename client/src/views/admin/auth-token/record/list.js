/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
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

Espo.define('views/admin/auth-token/record/list', 'views/record/list', function (Dep) {

    return Dep.extend({

        rowActionsView: 'views/admin/auth-token/record/row-actions/default',

        massActionList: ['remove', 'setInactive'],

        checkAllResultMassActionList: ['remove', 'setInactive'],

        massActionSetInactive: function () {
            var ids = false;
            var allResultIsChecked = this.allResultIsChecked;
            if (!allResultIsChecked) {
                ids = this.checkedList;
            }
            var attributes = {
                isActive: false
            };

            var ids = false;
            var allResultIsChecked = this.allResultIsChecked;
            if (!allResultIsChecked) {
                ids = this.checkedList;
            }

            this.ajaxPutRequest(this.scope + '/action/massUpdate', {
                attributes: attributes,
                ids: ids || null,
                where: (!ids || ids.length == 0) ? this.collection.getWhere() : null,
                selectData: (!ids || ids.length == 0) ? this.collection.data : null,
                byWhere: this.allResultIsChecked
            }).then(function () {
                var result = result || {};
                var count = result.count;
                this.collection.fetch();
            }.bind(this));
        },

        actionSetInactive: function (data) {
            if (!data.id) return;
            var model = this.collection.get(data.id);

            if (!model) return;

            Espo.Ui.notify(this.translate('pleaseWait', 'messages'));

            model.save({
                'isActive': false
            }, {patch: true}).then(function () {
                Espo.Ui.notify(false);
            });
        }

    });
});

