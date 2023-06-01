/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2023 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

        template: 'modals/add-dashlet',

        cssName: 'add-dashlet',
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
            'keyup input[data-name="quick-search"]': function (e) {
                this.processQuickSearch(e.currentTarget.value);
            },
        },

        setup: function () {
            this.headerText = this.translate('Add Dashlet');

            let dashletList = Object.keys(this.getMetadata().get('dashlets') || {})
                .sort((v1, v2) => {
                    return this.translate(v1, 'dashlets').localeCompare(this.translate(v2, 'dashlets'));
                });


            this.translations = {};

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

                this.translations[item] = this.translate(item, 'dashlets');

                return true;
            });
        },

        afterRender: function () {
            this.$noData = this.$el.find('.no-data');

            setTimeout(() => {
                this.$el.find('input[data-name="quick-search"]').focus()
            }, 100);
        },

        processQuickSearch: function (text) {
            text = text.trim();

            let $noData = this.$noData;

            $noData.addClass('hidden');

            if (!text) {
                this.$el.find('ul .list-group-item').removeClass('hidden');

                return;
            }

            let matchedList = [];

            let lowerCaseText = text.toLowerCase();

            this.dashletList.forEach(item => {
                let label = this.translations[item].toLowerCase();

                for (let word of label.split(' ')) {
                    let matched = word.indexOf(lowerCaseText) === 0;

                    if (matched) {
                        matchedList.push(item);

                        return;
                    }
                }
            });

            if (matchedList.length === 0) {
                this.$el.find('ul .list-group-item').addClass('hidden');

                $noData.removeClass('hidden');

                return;
            }

            this.dashletList.forEach(item => {
                let $row = this.$el.find(`ul .list-group-item[data-name="${item}"]`);

                if (!~matchedList.indexOf(item)) {
                    $row.addClass('hidden');

                    return;
                }

                $row.removeClass('hidden');
            });
        },
    });
});
