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

define('views/import-error/fields/validation-failures', ['views/fields/base'], (Dep) => {

    /**
     * @class
     * @name Class
     * @extends module:views/fields/base.Class
     * @memberOf module:views/import-error/fields/validation-failures
     */
    return Dep.extend(/** @lends module:views/import-error/fields/validation-failures.Class# */{

        detailTemplateContent: `
            {{#if value}}
            <table class="table">
                <thead>
                    <tr>
                        <th style="width: 50%;">{{translate 'Field'}}</th>
                        <th>{{translateOption 'Validation' scope='ImportError' field='type'}}</th>
                    </tr>
                </thead>
                <tbody>
                    {{#each value}}
                    <tr>
                        <td>{{translate field category='fields' scope=entityType}}</td>
                        <td>{{translate type category='fieldValidations'}}</td>
                    </tr>
                    {{/each}}
                </tbody>
            </table>
            {{else}}
            <span class="none-value">{{translate 'None'}}</span>
            {{/if}}
        `,
    });
});
