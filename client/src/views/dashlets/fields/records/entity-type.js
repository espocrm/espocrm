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

import EnumFieldView from 'views/fields/enum';

export default class extends EnumFieldView {

    setup() {
        super.setup();

        this.on('change', () => {
            const o = {
                primaryFilter: null,
                boolFilterList: [],
                title: this.translate('Records', 'dashlets'),
                sortBy: null,
                sortDirection: 'asc',
            };

            o.expandedLayout = {
                rows: []
            };

            const entityType = this.model.get('entityType');

            if (entityType) {
                o.title = this.translate(entityType, 'scopeNamesPlural');
                o.sortBy = this.getMetadata().get(['entityDefs', entityType, 'collection', 'orderBy']);

                const order = this.getMetadata().get(['entityDefs', entityType, 'collection', 'order']);

                if (order) {
                    o.sortDirection = order;
                } else {
                    o.sortDirection = 'asc';
                }

                o.expandedLayout = {
                    rows: [[{name: "name", link: true, scope: entityType}]]
                };
            }

            this.model.set(o);
        });
    }

    setupOptions() {
        this.params.options =  Object.keys(this.getMetadata().get('scopes'))
            .filter(scope => {
                if (this.getMetadata().get(`scopes.${scope}.disabled`)) {
                    return;
                }

                if (!this.getAcl().checkScope(scope, 'read')) {
                    return;
                }

                if (!this.getMetadata().get(['scopes', scope, 'entity'])) {
                    return;
                }

                if (!this.getMetadata().get(['scopes', scope, 'object'])) {
                    return;
                }

                return true;
            })
            .sort((v1, v2) => {
                return this.translate(v1, 'scopeNames').localeCompare(this.translate(v2, 'scopeNames'));
            });

        this.params.options.unshift('');
    }
}
