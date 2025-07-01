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

import StreamViewAuditLogModalView from 'views/stream/modals/view-audit-log';

class ViewAuditLogHandler {

    constructor(/** import('views/record/detail').default */view) {
        this.view = view;
        this.metadata = /** @type {module:metadata} */view.getMetadata();
        this.entityType = this.view.entityType;
        this.model = /** @type {module:model} */this.view.model;

        this.hasAudited =
            this.metadata.get(`scopes.${this.entityType}.statusField`) ||
            this.model.getFieldList().find(field => this.model.getFieldParam(field, 'audited')) !== undefined;

        if (this.entityType === 'User' && !this.view.getUser().isAdmin()) {
            this.hasAudited = false;
        }

        if (this.view.getUser().isPortal()) {
            this.hasAudited = false;
        }

        if (this.view.getAcl().getPermissionLevel('audit') !== 'yes') {
            this.hasAudited = false;
        }
    }

    isAvailable() {
        return this.hasAudited;
    }

    show() {
        const view = new StreamViewAuditLogModalView({model: this.model});

        this.view.assignView('dialog', view)
            .then(() => {
                view.render();
            })
    }
}

// noinspection JSUnusedGlobalSymbols
export default ViewAuditLogHandler;

