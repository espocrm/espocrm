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

import RelatedListModalView from 'views/modals/related-list';

export default class {

    toShow

    constructor(/** import('views/record/detail').default */view) {
        this.view = view;
        this.metadata = /** @type {module:metadata} */view.getMetadata();
        this.entityType = this.view.entityType;
        this.model = this.view.model;

        const level = this.view.getAcl().getPermissionLevel('user');

        this.toShow = (level === 'all' || level === 'team') &&
            (
                this.metadata.get(`scopes.${this.entityType}.object`) ||
                this.metadata.get(`scopes.${this.entityType}.acl`)
            )
            this.view.getAcl().checkScope('User');
    }

    isAvailable() {
        return this.toShow;
    }

    async show() {
        const actionList = this.getActionList();

        /** @type {Record[]} */
        const listLayout = [
            {
                name: 'name',
                link: true,
                view: 'views/user/fields/name',
            },
        ];

        //const width = Math.round((100.0 - 40) / (actionList.length + 1));

        actionList.forEach(action => {
            listLayout.push({
                name: 'recordAccessLevel' + action,
                customLabel: this.view.translate(action, 'recordActions'),
                view: 'views/user/fields/record-access-level',
                notSortable: true,
                width: 16,
            });
        });

        const view = new RelatedListModalView({
            model: this.model,
            link: 'usersAccess',
            entityType: 'User',
            title: this.view.translate('View User Access'),
            url: `${this.entityType}/${this.model.id}/usersAccess`,
            createDisabled: true,
            selectDisabled: true,
            massActionsDisabled: true,
            maxSize: this.view.getConfig().get('recordsPerPageSmall'),
            rowActionsView: null,
            listLayout: listLayout,
            filter: 'active',
        });

        await this.view.assignView('dialog', view);
        await view.render();
    }

    /**
     * @private
     * @return {string[]}
     */
    getActionList() {
        /** @type {string[]} */
        let actionList = this.metadata.get(`scopes.${this.entityType}.aclActionList`);

        if (!actionList) {
            actionList = [
                'read',
                'edit',
                'delete',
            ];

            if (this.metadata.get(`scopes.${this.entityType}.stream`)) {
                actionList.push('stream');
            }
        }

        return actionList.filter(it => it !== 'create');
    }
}
