/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2022 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

define('views/modals/add-dashlet', ['views/modal'], function (Dep) {

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
            this.headerText = this.translate('Add Dashlet');

            let dashletList = Object.keys(this.getMetadata().get('dashlets') || {})
                .sort((v1, v2) => {
                    return this.translate(v1, 'dashlets').localeCompare(this.translate(v2, 'dashlets'));
                });

            this.dashletList = dashletList.filter(item => {
                let aclScope = this.getMetadata().get(['dashlets', item, 'aclScope']) || null;
                let accessDataList = this.getMetadata().get(['dashlets', item, 'accessDataList']) || null;

                if (this.options.parentType === 'Settings') {
                    return true;
                }

                if (this.options.parentType === 'Portal') {
                    if (accessDataList && accessDataList.find(item => item.inPortalDisabled)) {
                        return false;
                    }

                    return true;
                }

                if (aclScope) {
                    if (!this.getAcl().check(aclScope)) {
                        return false;
                    }
                }

                if (accessDataList) {
                    if (!Espo.Utils.checkAccessDataList(accessDataList, this.getAcl(), this.getUser())) {
                        return false;
                    }
                }

                return true;
            });
        },
    });
});
