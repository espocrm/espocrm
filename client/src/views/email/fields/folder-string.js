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

import BaseFieldView from 'views/fields/base';

class EmailFolderStringFieldView extends BaseFieldView {

    // language=Handlebars
    detailTemplateContent = `
        {{#if valueIsSet}}
            {{#if value}}
                {{#if isList}}
                    {{#each value}}
                        <div class="multi-enum-item-container">{{this}}</div>
                    {{/each}}
                {{else}}
                    {{value}}
                {{/if}}

            {{else}}
                <span class="none-value">{{translate 'None'}}</span>
            {{/if}}
        {{else}}
            <span class="loading-value"></span>
        {{/if}}
    `

    // noinspection JSCheckFunctionSignatures
    data() {
        if (!this.model.has('folderId')) {
            return {valueIsSet: false};
        }

        const value = this.getFolderString();

        return {
            valueIsSet: true,
            value: this.getFolderString(),
            isList: Array.isArray(value),
        }
    }

    getAttributeList() {
        return [
            'isUsers',
            'folderId',
            'folderName',
            'groupFolderId',
            'groupFolderName',
            'inArchive',
            'inTrash',
            'isUsersSent',
            'groupStatusFolder',
        ];
    }

    /**
     * @return {string|string[]}
     */
    getFolderString() {
        if (this.model.attributes.groupFolderName) {
            let string = this.translate('group', 'strings', 'Email') + ' · ' + this.model.attributes.groupFolderName;

            if (this.model.attributes.groupStatusFolder === 'Archive') {
                string += ' · ' + this.translate('archive', 'presetFilters', 'Email');
            } else if (this.model.attributes.groupStatusFolder === 'Trash') {
                string += ' · ' + this.translate('trash', 'presetFilters', 'Email');
            }

            if (this.model.attributes.isUsersSent) {
                return [
                    string,
                    this.translate('sent', 'presetFilters', 'Email'),
                ];
            }

            return string;
        }

        let string;

        if (this.model.attributes.inTrash) {
            string = this.translate('trash', 'presetFilters', 'Email');
        }

        if (this.model.attributes.inArchive) {
            string = this.translate('archive', 'presetFilters', 'Email');
        }

        if (this.model.attributes.folderName && this.model.attributes.folderId) {
            string = this.model.attributes.folderName;
        }

        if (string && this.model.attributes.isUsersSent) {
            return [
                string,
                this.translate('sent', 'presetFilters', 'Email'),
            ];
        }

        if (this.model.attributes.isUsersSent) {
            return this.translate('sent', 'presetFilters', 'Email');
        }

        if (this.model.attributes.createdById === this.getUser().id && this.model.attributes.status === 'Draft') {
            return this.translate('drafts', 'presetFilters', 'Email');
        }

        if (this.model.attributes.isUsers) {
            return this.translate('inbox', 'presetFilters', 'Email');
        }

        return undefined;
    }
}

export default EmailFolderStringFieldView;
