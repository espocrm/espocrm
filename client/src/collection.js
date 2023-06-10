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

/** @module collection */

import Model from 'model';
import Backbone from 'lib!backbone';
import _ from 'lib!underscore';
import Bull from 'lib!bullbone';

const Dep = Backbone.Collection;

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
 * Add a model or models.
 *
 * @function add
 * @memberof Collection#
 * @param {Model|Model[]} models A model or models.
 * @param {Object} [options] Options.
 *
 * @fires Collection#update Unless `{silent: true}`.
 */

/**
 * Remove a model or models.
 *
 * @function remove
 * @memberof Collection#
 * @param {Model|Model[]|string|string[]} models A model, models, ID or IDs.
 * @param {Object} [options] Options.
 *
 * @fires Collection#update Unless `{silent: true}`.
 */

/**
 * Append a model.
 *
 * @function push
 * @memberof Collection#
 * @param {Model} model A model.
 * @param {Object} [options] Options.
 */

/**
 * Remove and return the last model from the collection.
 *
 * @function pop
 * @memberof Collection#
 * @param {Object} [options] Options.
 */

/**
 * @class
 * @extends Backbone.Collection.prototype
 * @mixes Bull.Events
 */
const Collection = Dep.extend(/** @lends Collection# */ {

    /**
     * A number of records.
     *
     * @name length
     * @type {number}
     * @memberof Collection#
     */

    /**
     * Models.
     *
     * @name length
     * @type {Model[]}
     * @memberof Collection#
     */

    /**
     * An API URL.
     *
     * @name url
     * @type {string|null}
     * @public
     * @memberof Collection.prototype
     */

    /**
     * A name.
     *
     * @type {string|null}
     */
    name: null,

    /**
     * An entity type.
     *
     * @type {string|null}
     */
    entityType: null,

    /**
     * A total number of records.
     *
     * @type {number}
     */
    total: 0,

    /**
     * A current offset (for pagination).
     *
     * @type {number}
     */
    offset: 0,

    /**
     * A max size (for pagination).
     *
     * @type {number}
     */
    maxSize: 20,

    /**
     * An order.
     *
     * @type {boolean|'asc'|'desc'|null}
     */
    order: null,

    /**
     * An order-by field.
     *
     * @type {string|null}
     */
    orderBy: null,

    /**
     * A where clause.
     *
     * @type {Array.<Object>|null}
     */
    where: null,

    /**
     * @deprecated
     */
    whereAdditional: null,

    /**
     * @type {number}
     */
    lengthCorrection: 0,

    /**
     * @type {number}
     */
    maxMaxSize: 0,

    /**
     * Initialize.
     *
     * @protected
     * @param {Model[]} models Models.
     * @param {{
     *     entityType?: string,
     *     model?: Model.prototype,
     *     defs?: module:model~defs,
     *     order?: 'asc'|'desc'|boolean|null,
     *     orderBy?: string|null,
     * }} options Options.
     */
    initialize: function (models, options) {
        options = options || {};

        this.name = options.entityType || options.name || this.name;
        this.urlRoot = this.urlRoot || this.name;
        this.url = this.url || this.urlRoot;

        this.orderBy = this.sortBy = options.orderBy || options.sortBy || this.orderBy || this.sortBy;
        this.order = options.order || this.order;

        this.defaultOrder = this.order;
        this.defaultOrderBy = this.orderBy;

        /** @type {module:model~defs} */
        this.defs = options.defs || {};

        this.data = {};

        /**
         * @private
         * @type {Model#}
         */
        this.model = options.model || Model;
    },

    /**
     * @private
     */
    _onModelEvent: function(event, model, collection, options) {
        if (event === 'sync' && collection !== this) {
            return;
        }

        Dep.prototype._onModelEvent.apply(this, arguments);
    },

    /**
     * Reset.
     *
     * @param {Model[]} [models]
     * @param {Object} [options]
     */
    reset: function (models, options) {
        this.lengthCorrection = 0;

        Dep.prototype.reset.call(this, models, options);
    },

    /**
     * @param {string} orderBy An order field.
     * @param {bool|null|'desc'|'asc'} [order] True for desc.
     * @returns {Promise}
     */
    sort: function (orderBy, order) {
        this.orderBy = orderBy;

        if (order === true) {
            order = 'desc';
        }
        else if (order === false) {
            order = 'asc';
        }

        this.order = order || 'asc';

        if (typeof this.asc !== 'undefined') { // TODO remove in 5.7
            this.asc = this.order === 'asc';
            this.sortBy = orderBy;
        }

        return this.fetch();
    },

    /**
     * Next page.
     */
    nextPage: function () {
        var offset = this.offset + this.maxSize;

        this.setOffset(offset);
    },

    /**
     * Previous page.
     */
    previousPage: function () {
        var offset = this.offset - this.maxSize;

        this.setOffset(offset);
    },

    /**
     * First page.
     */
    firstPage: function () {
        this.setOffset(0);
    },

    /**
     * Last page.
     */
    lastPage: function () {
        let offset = this.total - this.total % this.maxSize;

        if (offset === this.total) {
            offset = this.total - this.maxSize;
        }

        this.setOffset(offset);
    },

    /**
     * Set an offset.
     *
     * @param {number} offset Offset.
     */
    setOffset: function (offset) {
        if (offset < 0) {
            throw new RangeError('offset can not be less than 0');
        }

        if (offset > this.total && this.total !== -1 && offset > 0) {
            throw new RangeError('offset can not be larger than total count');
        }

        this.offset = offset;
        this.fetch();
    },

    /**
     * Has more.
     *
     * @return {boolean}
     */
    hasMore: function () {
        return this.total > this.length || this.total === -1;
    },

    /**
     * Parse a response from the backend.
     *
     * @todo Rename to `handleResponse`.
     * @param {Object} response A response.
     * @param {Object} options Options.
     * @returns {Collection[]}
     */
    parse: function (response, options) {
        this.total = response.total;

        if ('additionalData' in response) {
            this.dataAdditional = response.additionalData;
        }
        else {
            this.dataAdditional = null;
        }

        return response.list;
    },

    /**
     * Fetches from the backend.
     *
     * @param {Object} [options] Options.
     * @returns {Promise}
     *
     * @fires Collection#sync Unless `{silent: true}`.
     */
    fetch: function (options) {
        options = options || {};

        options.data = _.extend(options.data || {}, this.data);

        this.offset = options.offset || this.offset;
        this.orderBy = options.orderBy || options.sortBy || this.orderBy;
        this.order = options.order || this.order;

        this.where = options.where || this.where;

        let length = this.length + this.lengthCorrection;

        if (!('maxSize' in options)) {
            options.data.maxSize = options.more ? this.maxSize : (
                (length > this.maxSize) ? length : this.maxSize
            );

            if (this.maxMaxSize && options.data.maxSize > this.maxMaxSize) {
                options.data.maxSize = this.maxMaxSize;
            }
        }
        else {
            options.data.maxSize = options.maxSize;
        }

        options.data.offset = options.more ? length : this.offset;
        options.data.orderBy = this.orderBy;
        options.data.order = this.order;
        options.data.where = this.getWhere();

        if (typeof this.asc !== 'undefined') { // TODO remove in 5.7
            options.data.asc = this.asc;
            options.data.sortBy = this.sortBy;

            delete options.data.orderBy;
            delete options.data.order;
        }

        this.lastXhr = Dep.prototype.fetch.call(this, options);

        return this.lastXhr;
    },

    /**
     * Abort the last fetch.
     */
    abortLastFetch: function () {
        if (this.lastXhr && this.lastXhr.readyState < 4) {
            this.lastXhr.abort();
        }
    },

    /**
     * Get a where clause.
     *
     * @returns {Object[]}
     */
    getWhere: function () {
        var where = (this.where || []).concat(this.whereAdditional || []);

        if (this.whereFunction) {
            where = where.concat(this.whereFunction() || []);
        }

        return where;
    },

    /**
     * @returns {string}
     */
    getEntityType: function () {
        return this.name;
    },

    /**
     * Reset the order to default.
     */
    resetOrderToDefault: function () {
        this.orderBy = this.defaultOrderBy;
        this.order = this.defaultOrder;
    },

    /**
     * Set an order.
     *
     * @param {string|null} orderBy
     * @param {boolean|'asc'|'desc'|null} [order]
     * @param {boolean} [setDefault]
     */
    setOrder: function (orderBy, order, setDefault) {
        this.orderBy = orderBy;
        this.order = order;

        if (setDefault) {
            this.defaultOrderBy = orderBy;
            this.defaultOrder = order;
        }
    },

    /**
     * Clone.
     *
     * @return {Collection}
     */
    clone: function () {
        /** @type {Collection} */
        let collection = Dep.prototype.clone.call(this);

        collection.name = this.name;
        collection.urlRoot = this.urlRoot;
        collection.url = this.url;
        collection.orderBy = this.orderBy;
        collection.order = this.order;
        collection.defaultOrder = this.defaultOrder;
        collection.defaultOrderBy = this.defaultOrderBy;
        collection.data = Espo.Utils.cloneDeep(this.data);
        collection.where = Espo.Utils.cloneDeep(this.where);
        collection.whereAdditional = Espo.Utils.cloneDeep(this.whereAdditional);
        collection.total = this.total;
        collection.offset = this.offset;
        collection.maxSize = this.maxSize;
        collection.maxMaxSize = this.maxMaxSize;

        collection.defs = this.defs;

        return collection;
    },

    /** @private */
    _isModel: function(object) {
        return object instanceof Model;
    },

    /**
     * Prepare an empty model instance.
     *
     * @return {Model}
     */
    prepareModel: function () {
        return this._prepareModel({}, {});
    },

    /** @private*/
    _prepareModel: function(attributes, options) {
        if (this._isModel(attributes)) {
            if (!attributes.collection) {
                attributes.collection = this;
            }

            return attributes;
        }

        const Model = this.model;

        return new Model(attributes, {
            collection: this,
            dateTime: this.dateTime,
            entityType: this.entityType || this.name,
            defs: this.defs,
            ...options,
        });
    },
});

Collection.extend = Bull.View.extend;

_.extend(Collection.prototype, Bull.Events);

export default Collection;
