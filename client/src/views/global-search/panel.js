/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2026 EspoCRM, Inc.
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

import View from 'view';

class GlobalSearchPanel extends View {

    template = 'global-search/panel'

    setup() {
        this.addHandler('click', '[data-action="closePanel"]', () => this.close());

        this.maxSize = this.getConfig().get('globalSearchMaxSize') || 10;
    }

    afterRender() {
        this.collection.reset();
        this.collection.maxSize = this.maxSize;

        this.collection.fetch()
            .then(() => this.createRecordView())
            .then(view => view.render());
    }

    /**
     * @return {Promise<module:views/record/list-expanded>}
     */
    createRecordView() {
        // noinspection JSValidateTypes
        return this.createView('list', 'views/record/list-expanded', {
            selector: '.list-container',
            collection: this.collection,
            listLayout: {
                rows: [
                    [
                        {
                            name: 'name',
                            view: 'views/global-search/name-field',
                        }
                    ],
                    [
                        {
                            name: 'status',
                            view: 'views/global-search/status-field',
                        }
                    ]
                ],
                right: {
                    name: 'read',
                    view: 'views/global-search/scope-badge',
                    width: '80px',
                },
            }
        });
    }

    close() {
        this.trigger('close');
    }
}

export default GlobalSearchPanel;
