/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2025 EspoCRM, Inc.
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

import ArrayFieldView from 'views/fields/array';

export default class EmailAccountFoldersFieldView extends ArrayFieldView {

    // language=Handlebars
    detailTemplateContent = `
        {{#unless isEmpty}}
            {{#each itemDataList}}
                <div class="multi-enum-item-container">
                    <span>{{value}}</span>
                    {{~#if folderLabel}}
                    <span class="text-muted small"><span> · </span>{{folderLabel}}</span>
                    {{/if~}}
                </div>
            {{/each}}
        {{else}}
            {{~#if valueIsSet~}}
                <span class="none-value">{{translate 'None'}}</span>{{else}}<span class="loading-value"></span>
            {{~/if~}}
        {{/unless}}

    `

    /**
     * @protected
     * @type {boolean}
     */
    noFolderMap = false

    /**
     * @protected
     * @type {string}
     */
    getFoldersUrl = 'EmailAccount/action/getFolders'

    /**
     * @private
     * @type {{id: string, name: string}[]}
     */
    folderDataList

    getAttributeList() {
        return [
            ...super.getAttributeList(),
            'folderMap',
        ];
    }

    data() {
        return {
            ...super.data(),
            itemDataList: this.getItemDataList(),
        };
    }

    setup() {
        super.setup();

        if (!this.noFolderMap) {
            this.loadEmailFolders();

            this.listenTo(this.model, 'change:assignedUserId', () => this.loadEmailFolders());
        }

        this.addHandler('change', 'select[data-role="folderId"]', () => {
            this.trigger('change');
        })
    }

    /**
     * @private
     * @return {Promise<void>}
     */
    async loadEmailFolders() {
        this.folderDataList = await this.fetchEmailFolders();

        await this.whenRendered();
        await this.reRender();
    }

    setupOptions() {
        this.params.options = ['INBOX'];
    }

    /**
     * @private
     * @return {Record[]}
     */
    getItemDataList() {
        /** @type {string[]} */
        const values = this.model.attributes[this.name] ?? [];

        return values.map(value => {
            const folderId = this.getItemMappedFolderId(value);

            const item = this.folderDataList?.find(it => it.id === folderId);

            return {
                value: value,
                folderLabel: item?.name,
            };
        });
    }

    fetchFolders() {
        return new Promise(resolve => {
            const data = {
                host: this.model.get('host'),
                port: this.model.get('port'),
                security: this.model.get('security'),
                username: this.model.get('username'),
                emailAddress: this.model.get('emailAddress'),
                userId: this.model.get('assignedUserId'),
            };

            if (this.model.has('password')) {
                data.password = this.model.get('password');
            }

            if (!this.model.isNew()) {
                data.id = this.model.id;
            }

            Espo.Ajax.postRequest(this.getFoldersUrl, data)
                .then(folders => {
                    resolve(folders);
                })
                .catch(xhr =>{
                    Espo.Ui.error(this.translate('couldNotConnectToImap', 'messages', 'EmailAccount'));

                    xhr.errorIsHandled = true;

                    resolve(["INBOX"]);
                });
        });
    }

    afterRender() {
        super.afterRender();

        if (this.isDetailMode()) {

        }
    }

    getItemHtml(value) {
        const html = super.getItemHtml(value);

        if (this.noFolderMap) {
            return html;
        }

        let folderDataList = this.folderDataList;

        const folderId = this.getItemMappedFolderId(value);

        if (!folderDataList && folderId) {
            folderDataList = [{id: folderId, name: folderId}];
        }

        if (!folderDataList) {
            return html;
        }

        const div = document.createElement('div');
        div.innerHTML = html;

        /** @type {HTMLElement} */
        const item = div.querySelector('.list-group-item');

        const group = document.createElement('div');
        group.classList.add('item-input-container');

        const select = document.createElement('select');
        select.className = 'form-control native-select input-sm';
        select.dataset.role = 'folderId';

        select.append(
            (() => {
                return document.createElement('option');
            })()
        );

        for (const item of folderDataList) {
            const option = document.createElement('option');

            option.value = item.id;
            option.text = item.name;

            if (folderId && item.id === folderId) {
                option.selected = true;
                option.setAttribute('selected', 'selected');
            }

            select.append(option)
        }

        group.append(select);
        item.append(group);

        return div.innerHTML;
    }

    /**
     * @protected
     * @return Promise.<{id: string, name: string}[]>
     */
    async fetchEmailFolders() {
        if (!this.model.attributes.assignedUserId) {
            return [];
        }

        const collection = await this.getCollectionFactory().create('EmailFolder');

        collection.data.select = ['id', 'name'].join(',');
        collection.where = [
            {
                attribute: 'assignedUserId',
                type: 'equals',
                value: this.model.attributes.assignedUserId,
            }
        ];

        await collection.fetch();

        return collection.models.map(m => ({id: m.id, name: m.attributes.name}));
    }

    /**
     * @private
     * @param {string|null} folder
     */
    getItemMappedFolderId(folder) {
        const map = /** @type {Record.<string, string>} */
            this.model.attributes.folderMap ?? {};

        return map[folder] ?? null;
    }

    actionAddItem() {
        Espo.Ui.notifyWait();

        this.fetchFolders()
            .then(options => {
                Espo.Ui.notify(false);

                this.createView('addModal', this.addItemModalView, {options: options})
                    .then(view => {
                        view.render();

                        view.once('add', item =>{
                            this.addValue(item);

                            view.close();
                        });

                        view.once('add-mass', items => {
                            items.forEach(item => {
                                this.addValue(item);
                            });

                            view.close();
                        });
                    });
            });
    }

    fetch() {
        const data = super.fetch();

        if (this.noFolderMap) {
            return data;
        }

        const map = {};

        /** @type {string[]} */
        const folders = data[this.name] ?? [];

        /** @type {HTMLElement[]} */
        const items = Array.from(this.element?.querySelectorAll('.list-group-item') ?? []);

        for (const folder of folders) {
            const item = items.find(div => div.dataset.value === folder);

            if (!item) {
                continue;
            }

            /** @type {HTMLSelectElement|null} */
            const select = item.querySelector('select[data-role="folderId"]');

            if (!select) {
                continue;
            }

            map[folder] = select.value !== '' ? select.value : null;
        }

        return {
            ...super.fetch(),
            folderMap: map,
        };
    }
}
