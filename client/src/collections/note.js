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

/** @module collections/note */

import Collection from 'collection';

class NoteCollection extends Collection {

    paginationByNumber = false

    /**
     * @private
     * @type {string|null}
     */
    reactionsCheckDate = null

    /**
     * @type {Record[]}
     */
    pinnedList

    /**
     * @type {number}
     */
    reactionsCheckMaxSize = 0;

    /** @inheritDoc */
    prepareAttributes(response, params) {
        const total = this.total;

        const list = super.prepareAttributes(response, params);

        if (params.data && params.data.after) {
            this.total = total >= 0 && response.total >= 0 ?
                total + response.total : total;
        }

        if (response.pinnedList) {
            this.pinnedList = Espo.Utils.cloneDeep(response.pinnedList);
        }

        this.reactionsCheckDate = response.reactionsCheckDate;

        /** @type {Record[]} */
        const updatedReactions = response.updatedReactions;

        if (updatedReactions) {
            updatedReactions.forEach(item => {
                const model = this.get(item.id);

                if (!model) {
                    return;
                }

                model.set(item);
            });
        }

        return list;
    }

    /**
     * Fetch new records.
     *
     * @param {Object} [options] Options.
     * @returns {Promise}
     */
    fetchNew(options) {
        options = options || {};

        options.data = options.data || {};
        options.fetchNew = true;
        options.noRebuild = true;
        options.lengthBeforeFetch = this.length;

        if (this.length) {
            options.data.after = this.models[0].get('createdAt');
            options.remove = false;
            options.at = 0;
            options.maxSize = null;

            if (this.reactionsCheckMaxSize) {
                options.data.reactionsAfter = this.reactionsCheckDate || options.data.after;

                options.data.reactionsCheckNoteIds = this.models
                    .filter(model => model.attributes.type === 'Post')
                    .map(model => model.id)
                    .slice(0, this.reactionsCheckMaxSize)
                    .join(',');
            }
        }

        return this.fetch(options);
    }

    fetch(options) {
        options = {...options};

        if (this.paginationByNumber && options.more) {
            options.more = false;
            options.data = options.data || {};

            const lastModel = this.models.at(this.length - 1);

            if (lastModel) {
                options.data.beforeNumber = lastModel.get('number');
            }
        }

        return super.fetch(options);
    }
}

export default NoteCollection;
