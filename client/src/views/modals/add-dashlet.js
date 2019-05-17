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

define('views/modals/add-dashlet', 'views/modal', function (Dep) {

    return Dep.extend({

        cssName: 'add-dashlet',

        template: 'modals/add-dashlet',

        backdrop: true,

        fitHeight: true,

        data: function () {
            return {
                dashletList: this.dashletList,
            };
        },

        events: {
            'click .add': function (e) {
                var name = $(e.currentTarget).data('name');
                this.trigger('add', name);
                this.close();
            },
        },

        setup: function () {
            this.headerHtml = this.translate('Add Dashlet');

            var dashletList = Object.keys(this.getMetadata().get('dashlets') || {}).sort(function (v1, v2) {
                return this.translate(v1, 'dashlets').localeCompare(this.translate(v2, 'dashlets'));
            }.bind(this));

            this.dashletList = [];

            dashletList.forEach(function (item) {
                var aclScope = this.getMetadata().get('dashlets.' + item + '.aclScope') || null;
                if (aclScope) {
                    if (!this.getAcl().check(aclScope)) {
                        return;
                    }
                }
                var accessDataList = this.getMetadata().get(['dashlets', item, 'accessDataList']) || null;
                if (accessDataList) {
                    if (!Espo.Utils.checkAccessDataList(accessDataList, this.getAcl(), this.getUser())) {
                        return false;
                    }
                }
                this.dashletList.push(item);
            }, this);
        },
    });
});
