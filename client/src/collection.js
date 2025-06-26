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

/** @module collection */

import Model from 'model';
import {Events, View as BullView} from 'bullbone';
import _ from 'underscore';

/**
 * On sync with backend.
 *
 * @event Collection#sync
 * @param {Collection} collection A collection.
 * @param {Object} response Response from backend.
 * @param {Object} o Options.
 */

/**
 * Any number of models have been added, removed or changed.
 *
 * @event Collection#update
 * @param {Collection} collection A collection.
 * @param {Object} o Options.
 */

/**
 * On reset.
 *
 * @event Collection#reset
 * @param {Collection} collection A collection.
 * @param {Object} o Options.
 */

/**
 * On model sync.
 *
 * @event Collection#model-sync
 * @param {Model} model A model.
 * @param {Record & {action?: 'fetch'|'save'|'destroy'}} o Options.
 * @since 9.1.0
 */

/**
 * @typedef {Object} module:collection~Data
 * @property {string|null} [primaryFilter]
 * @property {string[]} [boolFilterList]
 * @property {string} [textFilter]
 * @property {string} [select]
 * @property {string} [q]
 */

/**
 * A collection.
 *
 * @mixes Bull.Events
 * @copyright Credits to Backbone.js.
 */
class Collection {

    /**
     * An entity type.
     *
     * @type {string|null}
     */
    entityType = null

    /**
     * A total number of records.
     *
     * @type {number}
     */
    total = 0

    /**
     * A current offset (for pagination).
     *
     * @type {number}
     */
    offset = 0

    /**
     * A max size (for pagination).
     *
     * @type {number}
     */
    maxSize = 20

    /**
     * An order.
     *
     * @type {boolean|'asc'|'desc'|null}
     */
    order = null

    /**
     * An order-by field.
     *
     * @type {string|null}
     */
    orderBy = null

    /**
     * A where clause.
     *
     * @type {module:search-manager~whereItem[]|null}
     */
    where = null

    /**
     * @deprecated
     * @type {module:search-manager~whereItem[]|null}
     */
    whereAdditional = null

    /**
     * A length correction.
     *
     * @type {number}
     */
    lengthCorrection = 0

    /**
     * A max max-size.
     *
     * @type {number}
     */
    maxMaxSize = 0

    /**
     * A where function.
     *
     * @type {function(): module:search-manager~whereItem[]}
     */
    whereFunction

    /**
     * A last sync request promise.
     *
     * @type {module:ajax.AjaxPromise|null}
     */
    lastSyncPromise = null

    /**
     * A parent model. To be used for own purposes. E.g. to have access to a parent from related models.
     *
     * @type {import('model').default}
     */
    parentModel

    /**
     * @param {Model[]|null} [models] Models.
     * @param {{
     *     entityType?: string,
     *     model?: Model.prototype,
     *     defs?: module:model~defs,
     *     order?: 'asc'|'desc'|boolean|null,
     *     orderBy?: string|null,
     *     urlRoot?: string,
     *     url?: string,
     *     maxSize?: number,
     * }} [options] Options.
     */
    constructor(models, options) {
        options = {...options};

        if (options.model) {
            this.model = options.model;
        }

        if (options.maxSize !== undefined) {
            this.maxSize = options.maxSize;
        }

        this._reset();

        if (options.entityType) {
            this.entityType = options.entityType;
            /** @deprecated */
            this.name = this.entityType;
        }

        /**
         * A root URL.
         *
         * @public
         * @type {string|null}
         */
        this.urlRoot = options.urlRoot || this.urlRoot || this.entityType;

        /**
         * An URL.
         *
         * @type {string|null}
         */
        this.url = options.url || this.url || this.urlRoot;

        this.orderBy = options.orderBy || this.orderBy;
        this.order = options.order || this.order;

        this.defaultOrder = this.order;
        this.defaultOrderBy = this.orderBy;

        /** @type {module:model~defs} */
        this.defs = options.defs || {};

        /**
         * @type {module:collection~Data | Record<string, *>}
         */
        this.data = {};

        /**
         * @private
         * @type {Model#}
         */
        this.model = options.model || Model;

        if (models) {
            this.reset(models, {silent: true, ...options});
        }
    }

    /**
     * Add models or a model.
     *
     * @param {Model[]|Model|Record[]|Record} models Models ar a model.
     * @param {{
     *     merge?: boolean,
     *     at?: number,
     *     silent?: boolean,
     * }} [options] Options. `at` – position; `merge` – merge existing models, otherwise, they are ignored.
     * @return {this}
     * @fires Collection#update
     */
    add(models, options) {
        this.set(models, {merge: false, ...options, ...addOptions});

        return this;
    }

    /**
     * Remove models or a model.
     *
     * @param {Model[]|Model|string} models Models, a model or a model ID.
     * @param {{
     *     silent?: boolean,
     * } & Object.<string, *>} [options] Options.
     * @return {this}
     * @fires Collection#update
     */
    remove(models, options) {
        options = {...options};

        const singular = !_.isArray(models);

        models = singular ? [models] : models.slice();

        const removed = this._removeModels(models, options);

        if (!options.silent && removed.length) {
            options.changes = {
                added: [],
                merged: [],
                removed: removed,
            };

            this.trigger('update', this, options);
        }

        return this;
    }

    /**
     * @protected
     * @param {Model[]|Model|Record[]} models Models ar a model.
     * @param {{
     *     silent?: boolean,
     *     at?: number,
     *     prepare?: boolean,
     *     add?: boolean,
     *     merge?: boolean,
     *     remove?: boolean,
     *     index?: number,
     * } & Object.<string, *>} [options]
     * @return {Model[]}
     */
    set(models, options) {
        if (models == null) {
            return [];
        }

        options = {...setOptions, ...options};

        if (options.prepare && !this._isModel(models)) {
            models = this.prepareAttributes(models, options) || [];
        }

        const singular = !_.isArray(models);
        models = singular ? [models] : models.slice();

        let at = options.at;

        if (at != null) {
            at = +at;
        }

        if (at > this.length) {
            at = this.length;
        }

        if (at < 0) {
            at += this.length + 1;
        }

        const set = [];
        const toAdd = [];
        const toMerge = [];
        const toRemove = [];
        const modelMap = {};

        const add = options.add;
        const merge = options.merge;
        const remove = options.remove;

        let model, i;

        for (i = 0; i < models.length; i++) {
            model = models[i];

            const existing = this._get(model);

            if (existing) {
                if (merge && model !== existing) {
                    let attributes = this._isModel(model) ?
                        model.attributes :
                        model;

                    if (options.prepare) {
                        attributes = existing.prepareAttributes(attributes, options);
                    }

                    existing.set(attributes, options);
                    toMerge.push(existing);
                }

                if (!modelMap[existing.cid]) {
                    modelMap[existing.cid] = true;
                    set.push(existing);
                }

                models[i] = existing;
            }
            else if (add) {
                model = models[i] = this._prepareModel(model);

                if (model) {
                    toAdd.push(model);

                    this._addReference(model, options);

                    modelMap[model.cid] = true;
                    set.push(model);
                }
            }
        }

        // Remove stale models.
        if (remove) {
            for (i = 0; i < this.length; i++) {
                model = this.models[i];

                if (!modelMap[model.cid]) {
                    toRemove.push(model);
                }
            }

            if (toRemove.length) {
                this._removeModels(toRemove, options);
            }
        }

        let orderChanged = false;
        const replace = add && remove;

        if (set.length && replace) {
            orderChanged =
                this.length !== set.length ||
                _.some(this.models, (m, index) => {
                    return m !== set[index];
                });

            this.models.length = 0;
            splice(this.models, set, 0);

            this.length = this.models.length;
        }
        else if (toAdd.length) {
            splice(this.models, toAdd, at == null ? this.length : at);

            this.length = this.models.length;
        }

        if (!options.silent) {
            for (i = 0; i < toAdd.length; i++) {
                if (at != null) {
                    options.index = at + i;
                }

                model = toAdd[i];

                model.trigger('add', model, this, options);
            }

            if (orderChanged) {
                this.trigger('sort', this, options);
            }

            if (toAdd.length || toRemove.length || toMerge.length) {
                options.changes = {
                    added: toAdd,
                    removed: toRemove,
                    merged: toMerge
                };

                this.trigger('update', this, options);
            }
        }

        return models;
    }

    /**
     * Reset.
     *
     * @param {Model[]|null} [models] Models to replace the collection with.
     * @param {{
     *     silent?: boolean,
     * } & Object.<string, *>} [options]
     * @return {this}
     * @fires Collection#reset
     */
    reset(models, options) {
        this.lengthCorrection = 0;

        options = options ? _.clone(options) : {};

        for (let i = 0; i < this.models.length; i++) {
            this._removeReference(this.models[i], options);
        }

        options.previousModels = this.models;

        this._reset();

        if (models) {
            this.add(models, {silent: true, ...options});
        }

        if (!options.silent) {
            this.trigger('reset', this, options);
        }

        return this;
    }

    /**
     * Add a model at the end.
     *
     * @param {Model} model A model.
     * @param {{
     *     silent?: boolean,
     * }} [options] Options
     * @return {this}
     */
    push(model, options) {
        this.add(model, {at: this.length, ...options});

        return this;
    }

    /**
     * Remove and return the last model.
     *
     * @param {{
     *     silent?: boolean,
     * }} [options] Options
     * @return {Model|null}
     */
    pop(options) {
        const model = this.at(this.length - 1);

        if (!model) {
            return null;
        }

        this.remove(model, options);

        return model;
    }

    /**
     * Add a model to the beginning.
     *
     * @param {Model} model A model.
     * @param {{
     *     silent?: boolean,
     * }} [options] Options
     * @return {this}
     */
    unshift(model, options) {
        this.add(model, {at: 0, ...options});

        return this;
    }

    /**
     * Remove and return the first model.
     *
     * @param {{
     *     silent?: boolean,
     * }} [options] Options
     * @return {Model|null}
     */
    shift(options) {
        const model = this.at(0);

        if (!model) {
            return null;
        }

        this.remove(model, options);

        return model;
    }

    /**
     * Get a model by an ID.
     *
     * @todo Usage to _get.
     * @param {string} id An ID.
     * @return {Model|undefined}
     */
    get(id) {
        return this._get(id);
    }

    /**
     * Whether a model in the collection.
     *
     * @todo Usage to _has.
     * @param {string} id An ID.
     * @return {boolean}
     */
    has(id) {
        return this._has(id);
    }

    /**
     * Get a model by index.
     *
     * @param {number} index An index. Can be negative, then counted from the end.
     * @return {Model|undefined}
     */
    at(index) {
        if (index < 0) {
            index += this.length;
        }

        return this.models[index];
    }

    /**
     * Iterates through a collection.
     *
     * @param {function(Model)} callback A function.
     * @param {Object} [context] A context.
     */
    forEach(callback, context) {
        return this.models.forEach(callback, context);
    }

    /**
     * Get an index of a model. Returns -1 if not found.
     *
     * @param {Model} model A model
     * @return {number}
     */
    indexOf(model) {
        return this.models.indexOf(model);
    }

    /**
     * @private
     * @param {string|Object.<string, *>|Model} obj
     * @return {boolean}
     */
    _has(obj) {
        return !!this._get(obj)
    }

    /**
     * @private
     * @param {string|Object.<string, *>|Model} obj
     * @return {Model|undefined}
     */
    _get(obj) {
        if (obj == null) {
            return void 0;
        }

        return this._byId[obj] ||
            this._byId[this.modelId(obj.attributes || obj)] ||
            obj.cid && this._byId[obj.cid];
    }

    /**
     * @protected
     * @param {Object.<string, *>} attributes
     * @return {*}
     */
    modelId(attributes) {
        return attributes['id'];
    }

    /** @private */
    _reset() {
        /**
         * A number of records.
         */
        this.length = 0;

        /**
         * Models.
         *
         * @type {Model[]}
         */
        this.models = [];

        /** @private */
        this._byId  = {};
    }

    /**
     * @param {string} orderBy An order field.
     * @param {bool|null|'desc'|'asc'} [order] True for desc.
     * @returns {Promise}
     */
    sort(orderBy, order) {
        this.orderBy = orderBy;

        if (order === true) {
            order = 'desc';
        }
        else if (order === false) {
            order = 'asc';
        }

        this.order = order || 'asc';

        return this.fetch();
    }

    /**
     * Has previous page.
     *
     * @return {boolean}
     */
    hasPreviousPage() {
        return this.offset > 0;
    }

    /**
     * Has next page.
     *
     * @return {boolean}
     */
    hasNextPage() {
        return this.total - this.offset > this.length || this.total === -1;
    }

    /**
     * Next page.
     *
     * @returns {Promise}
     */
    nextPage() {
        return this.setOffset(this.offset + this.length);
    }

    /**
     * Previous page.
     *
     * @returns {Promise}
     */
    previousPage() {
        return this.setOffset(Math.max(0, this.offset - this.maxSize));
    }

    /**
     * First page.
     *
     * @returns {Promise}
     */
    firstPage() {
        return this.setOffset(0);
    }

    /**
     * Last page.
     *
     * @returns {Promise}
     */
    lastPage() {
        let offset = this.total - this.total % this.maxSize;

        if (offset === this.total) {
            offset = this.total - this.maxSize;
        }

        return this.setOffset(offset);
    }

    /**
     * Set an offset.
     *
     * @param {number} offset Offset.
     * @returns {Promise}
     */
    setOffset(offset) {
        if (offset < 0) {
            throw new RangeError('offset can not be less than 0');
        }

        if (
            offset > this.total &&
            this.total !== -1 &&
            this.total !== -2 &&
            offset > 0
        ) {
            throw new RangeError('offset can not be larger than total count');
        }

        this.offset = offset;

        return this.fetch({maxSize: this.maxSize});
    }

    /**
     * Has more.
     *
     * @return {boolean}
     */
    hasMore() {
        return this.total > (this.length + this.offset + this.lengthCorrection) || this.total === -1;
    }

    /**
     * Prepare attributes.
     *
     * @protected
     * @param {Object.<string, *>|Record[]} response A response from the backend.
     * @param {Object.<string, *>} options Options.
     * @returns {Object.<string, *>[]}
     */
    prepareAttributes(response, options) {
        this.total = response.total;

        // noinspection JSUnusedGlobalSymbols
        /**
         * @deprecated As of v8.4. Use 'sync' event to obtain any additional data from a response.
         */
        this.dataAdditional = response.additionalData || null;

        return response.list;
    }

    /**
     * Fetch from the backend.
     *
     * @param {{
     *     remove?: boolean,
     *     more?: boolean,
     *     offset?: number,
     *     maxSize?: number,
     *     orderBy?: string,
     *     order?: 'asc'|'desc',
     * } & Object.<string, *>} [options] Options.
     * @returns {Promise}
     * @fires Collection#sync Unless `{silent: true}`.
     */
    fetch(options) {
        options = {...options};

        options.data = {...options.data, ...this.data};

        this.offset = options.offset || this.offset;
        this.orderBy = options.orderBy || this.orderBy;
        this.order = options.order || this.order;
        this.where = options.where || this.where;

        const length = this.length + this.lengthCorrection;

        if ('maxSize' in options) {
            options.data.maxSize = options.maxSize;
        } else {
            options.data.maxSize = options.more ? this.maxSize : Math.max(length, this.maxSize);

            if (this.maxMaxSize && options.data.maxSize > this.maxMaxSize) {
                options.data.maxSize = this.maxMaxSize;
            }
        }

        options.data.offset = options.more ? (this.offset + length) : this.offset;
        options.data.orderBy = this.orderBy;
        options.data.order = this.order;
        options.data.whereGroup = this.getWhere();

        if (options.data.select) {
            options.data.attributeSelect = options.data.select;

            delete options.data.select;
        }

        options = {prepare: true, ...options};

        const success = options.success;

        options.success = response => {
            options.reset ?
                this.reset(response, options) :
                this.set(response, options);

            if (success) {
                success.call(options.context, this, response, options);
            }

            this.trigger('sync', this, response, options);
        };

        const error = options.error;

        options.error = response => {
            if (error) {
                error.call(options.context, this, response, options);
            }

            this.trigger('error', this, response, options);
        };

        this.lastSyncPromise = Model.prototype.sync.call(this, 'read', this, options);

        return this.lastSyncPromise;
    }

    /**
     * Is being fetched.
     *
     * @return {boolean}
     */
    isBeingFetched() {
        return this.lastSyncPromise && this.lastSyncPromise.getReadyState() < 4;
    }

    /**
     * Abort the last fetch.
     */
    abortLastFetch() {
        if (this.isBeingFetched()) {
            this.lastSyncPromise.abort();
        }
    }

    /**
     * Get a where clause.
     *
     * @returns {module:search-manager~whereItem[]}
     */
    getWhere() {
        let where = (this.where ?? []).concat(this.whereAdditional || []);

        if (this.whereFunction) {
            where = where.concat(this.whereFunction() || []);
        }

        return where;
    }

    /**
     * Get an entity type.
     *
     * @returns {string}
     */
    getEntityType() {
        return this.entityType || this.name;
    }

    /**
     * Reset the order to default.
     */
    resetOrderToDefault() {
        this.orderBy = this.defaultOrderBy;
        this.order = this.defaultOrder;
    }

    /**
     * Set an order.
     *
     * @param {string|null} orderBy
     * @param {boolean|'asc'|'desc'|null} [order]
     * @param {boolean} [setDefault]
     */
    setOrder(orderBy, order, setDefault) {
        this.orderBy = orderBy;
        this.order = order;

        if (setDefault) {
            this.defaultOrderBy = orderBy;
            this.defaultOrder = order;
        }
    }

    /**
     * Clone.
     *
     * @param {{withModels?: boolean}} [options]
     * @return {Collection}
     */
    clone(options = {}) {
        let models = this.models;

        if (options.withModels) {
            models = this.models.map(m => m.clone());
        }

        const collection = new this.constructor(models, {
            model: this.model,
            entityType: this.entityType,
            defs: this.defs,
            orderBy: this.orderBy,
            order: this.order,
        });

        collection.name = this.name;
        collection.urlRoot = this.urlRoot;
        collection.url = this.url;
        collection.defaultOrder = this.defaultOrder;
        collection.defaultOrderBy = this.defaultOrderBy;
        collection.data = Espo.Utils.cloneDeep(this.data);
        collection.where = Espo.Utils.cloneDeep(this.where);
        collection.whereAdditional = Espo.Utils.cloneDeep(this.whereAdditional);
        collection.total = this.total;
        collection.offset = this.offset;
        collection.maxSize = this.maxSize;
        collection.maxMaxSize = this.maxMaxSize;
        collection.whereFunction = this.whereFunction;
        collection.parentModel = this.parentModel;

        return collection;
    }

    /**
     * Prepare an empty model instance.
     *
     * @return {Model}
     */
    prepareModel() {
        return this._prepareModel({});
    }

    // noinspection JSUnusedGlobalSymbols
    /**
     * Compose a URL for syncing. Called from Model.sync.
     *
     * @protected
     * @return {string}
     */
    composeSyncUrl() {
        return this.url;
    }

    /** @private */
    _isModel(object) {
        return object instanceof Model;
    }

    /** @private */
    _removeModels(models, options) {
        const removed = [];

        for (let i = 0; i < models.length; i++) {
            const model = this.get(models[i]);

            if (!model) {
                continue;
            }

            const index = this.models.indexOf(model);

            this.models.splice(index, 1);
            this.length--;

            delete this._byId[model.cid];
            const id = this.modelId(model.attributes);

            if (id != null) {
                delete this._byId[id];
            }

            if (!options.silent) {
                options.index = index;

                model.trigger('remove', model, this, options);
            }

            removed.push(model);

            this._removeReference(model, options);
        }

        return removed;
    }

    /** @private */
    _addReference(model) {
        this._byId[model.cid] = model;

        const id = this.modelId(model.attributes);

        if (id != null) {
            this._byId[id] = model;
        }

        model.on('all', this._onModelEvent, this);
    }

    /** @private */
    _removeReference(model) {
        delete this._byId[model.cid];

        const id = this.modelId(model.attributes);

        if (id != null) {
            delete this._byId[id];
        }

        if (this === model.collection) {
            delete model.collection;
        }

        model.off('all', this._onModelEvent, this);
    }

    /** @private */
    _onModelEvent(event, model, collection, options) {
        // @todo Revise. Never triggerred? Remove?
        if (event === 'sync' && collection !== this) {
            return;
        }

        if (!model) {
            this.trigger.apply(this, arguments);

            return;
        }

        if ((event === 'add' || event === 'remove') && collection !== this) {
            return;
        }

        if (event === 'destroy') {
            this.remove(model, options);
        }

        if (event === 'change') {
            const prevId = this.modelId(model.previousAttributes());
            const id = this.modelId(model.attributes);

            if (prevId !== id) {
                if (prevId != null) {
                    delete this._byId[prevId];
                }

                if (id != null) {
                    this._byId[id] = model;
                }
            }
        }

        this.trigger.apply(this, arguments);
    }

    // noinspection JSDeprecatedSymbols
    /** @private*/
    _prepareModel(attributes) {
        if (this._isModel(attributes)) {
            if (!attributes.collection) {
                attributes.collection = this;
            }

            return attributes;
        }

        const ModelClass = this.model;

        // noinspection JSValidateTypes
        return new ModelClass(attributes, {
            collection: this,
            entityType: this.entityType || this.name,
            defs: this.defs,
        });
    }
}

Object.assign(Collection.prototype, Events);

Collection.extend = BullView.extend;

const setOptions = {
    add: true,
    remove: true,
    merge: true,
};

const addOptions = {
    add: true,
    remove: false,
};

const splice = (array, insert, at) => {
    at = Math.min(Math.max(at, 0), array.length);

    const tail = Array(array.length - at);
    const length = insert.length;
    let i;

    for (i = 0; i < tail.length; i++) {
        tail[i] = array[i + at];
    }

    for (i = 0; i < length; i++) {
        array[i + at] = insert[i];
    }

    for (i = 0; i < tail.length; i++) {
        array[i + length + at] = tail[i];
    }
};

export default Collection;
