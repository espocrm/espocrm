/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

import ModalView from 'views/modal';

class AddDashletModalView extends ModalView {

    template = 'modals/add-dashlet'

    cssName = 'add-dashlet'
    backdrop = true

    events = {
        /** @this AddDashletModalView */
        'click .add': function (e) {
            const name = $(e.currentTarget).data('name');

            this.trigger('add', name);
            this.close();
        },
        /** @this AddDashletModalView */
        'keyup input[data-name="quick-search"]': function (e) {
            this.processQuickSearch(e.currentTarget.value);
        },
    }

    data() {
        return {
            dashletList: this.dashletList,
        };
    }

    setup() {
        this.headerText = this.translate('Add Dashlet');

        const dashletList = Object.keys(this.getMetadata().get('dashlets') || {})
            .sort((v1, v2) => {
                return this.translate(v1, 'dashlets').localeCompare(this.translate(v2, 'dashlets'));
            });

        this.translations = {};

        this.dashletList = dashletList.filter(item => {
            const aclScope = this.getMetadata().get(['dashlets', item, 'aclScope']) || null;
            const accessDataList = this.getMetadata().get(['dashlets', item, 'accessDataList']) || null;

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

        this.dashletList.forEach(item => {
            this.translations[item] = this.translate(item, 'dashlets');
        });
    }

    afterRender() {
        this.$noData = this.$el.find('.no-data');

        setTimeout(() => {
            this.$el.find('input[data-name="quick-search"]').focus()
        }, 100);
    }

    processQuickSearch(text) {
        text = text.trim();

        const $noData = this.$noData;

        $noData.addClass('hidden');

        if (!text) {
            this.$el.find('ul .list-group-item').removeClass('hidden');

            return;
        }

        const matchedList = [];

        const lowerCaseText = text.toLowerCase();

        this.dashletList.forEach(item => {
            if (!(item in this.translations)) {
                return;
            }

            const label = this.translations[item].toLowerCase();

            for (const word of label.split(' ')) {
                const matched = word.indexOf(lowerCaseText) === 0;

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
            const $row = this.$el.find(`ul .list-group-item[data-name="${item}"]`);

            if (!~matchedList.indexOf(item)) {
                $row.addClass('hidden');

                return;
            }

            $row.removeClass('hidden');
        });
    }
}

export default AddDashletModalView;
