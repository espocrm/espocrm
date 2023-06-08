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

define('views/admin/entity-manager/modals/select-formula', ['views/modal'], function (Dep) {

    /**
     * @class
     * @name Class
     * @extends module:views/modal
     * @memberOf module:views/admin/entity-manager/modals/select-formula
     */
    return Dep.extend(/** @lends module:views/admin/entity-manager/modals/select-formula.Class# */{

        templateContent: `
            <div class="no-side-margin">
                <table class="table table-bordered table-panel">
                    {{#each typeList}}
                    <tr>
                        <td style="width: 40%">
                            <a
                                class="btn btn-default btn-lg btn-full-wide"
                                href="#Admin/entityManager/formula&scope={{../scope}}&type={{this}}"
                            >
                            {{translate this category='fields' scope='EntityManager'}}
                            </a>
                        </td>
                        <td style="width: 60%">
                            <div class="complex-text">{{complexText (translate this category='messages' scope='EntityManager')}}
                        </td>
                    </tr>
                    {{/each}}
                </table>
            </div>
        `,

        backdrop: true,

        data: function () {
            return {
                typeList: this.typeList,
                scope: this.scope,
            };
        },

        setup: function () {
            this.scope = this.options.scope;

            this.typeList = [
                'beforeSaveCustomScript',
                'beforeSaveApiScript',
            ];

            this.headerText = this.translate('Formula', 'labels', 'EntityManager')
        },
    });
});
