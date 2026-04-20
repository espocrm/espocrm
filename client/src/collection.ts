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

/** @module collection */

import Model from 'model';
import {Events, View as BullView} from 'bullbone';
import _ from 'underscore';
import {onSync} from 'util/event';
import {AjaxPromise} from 'util/ajax';

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
 * A where item. Sent to the backend.
 */
export interface WhereItem {
    type: string;
    attribute?: string;
    value?: WhereItem[] | string | number | boolean | string[] | null;
    dateTime?: boolean;
    timeZone?: string;
}

/**
 * Search data.
 */
export interface Data {
    primaryFilter?: string | null,
    boolFilterList?: string[],
    textFilter?: string,
    select?: string,
    q?: string,
}

export default class Collection<TModel extends Model = Model> {

    /**
     * An entity type.
     */
    entityType: string | null = null

    /**
     * A total number of records.
     */
    total: number = 0

    /**
     * A current offset (for pagination).
     */
    offset: number = 0

    /**
     * A max size (for pagination).
     */
    maxSize: number = 20

    /**
     * A number of records.
     */
    length: number

    /**
     * An order.
     */
    order: boolean | 'asc' | 'desc' | null = null

    /**
     * An order-by field.
     */
    orderBy: string | null = null

    /**
     * A default order.
     */
    defaultOrder: boolean | 'asc' | 'desc' | null = null

    /**
     * A default order-by field.
     */
    defaultOrderBy: string | null = null

    /**
     * A where clause.
     */
    where: WhereItem[] | null = null

    /**
     * @deprecated
     */
    whereAdditional: WhereItem[] | null = null

    /**
     * A length correction.
     */
    lengthCorrection: number = 0

    /**
     * A max max-size.
     */
    maxMaxSize: number = 0

    /**
     * A where function.
     */
    whereFunction: () => WhereItem[] | null = null

    /**
     * A last sync request promise.
     */
    lastSyncPromise: AjaxPromise | null = null

    /**
     * A parent model. To be used for own purposes. E.g. to have access to a parent from related models.
     */
    parentModel: Model | undefined

    /**
     * A root URL.
     */
    urlRoot: string | null

    /**
     * A URL.
     */
    url: string | null

    /**
     * Model definitions.
     */
    protected defs: import('model').Defs | null

    /**
     * A model type.
     */
    protected model: typeof Model

    /**
     * Search data.
     */
    data: Data & Record<string, any>

    /**
     * Models. Do not write, do not mutate.
     */
    models: TModel[];

    private _byId: Record<string, TModel>


    private readonly _onModelEventBind: () => void;

    /**
     * @param {Model[]|Record<string, *>[]|null} [models] Models.
     * @param {{
     *     entityType?: string,
     *     model?: Model.prototype,
     *     defs?: import('model').Defs,
     *     order?: 'asc'|'desc'|boolean|null,
     *     orderBy?: string|null,
     *     urlRoot?: string,
     *     url?: string,
     *     maxSize?: number,
     * }} [options] Options.
     */
    constructor(
        models: TModel[] | Record<string, any>[] | null,
        options: {
            model?: typeof Model,
            defs: import('model').Defs,
            maxSize?: number,
            entityType?: string,
            urlRoot?: string,
            url?: string,
            orderBy: string | null,
            order: 'asc' | 'desc' | boolean,
        }
    ) {
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
            // @ts-ignore
            this.name = this.entityType;
        }

        this.urlRoot = options.urlRoot || this.urlRoot || this.entityType || null;
        this.url = options.url || this.url || this.urlRoot;

        this.orderBy = options.orderBy || this.orderBy;
        this.order = options.order || this.order;

        this.defaultOrder = this.order;
        this.defaultOrderBy = this.orderBy;

        this.defs = options.defs ?? {};
        this.data = {};

        this.model = options.model || Model;

        if (models) {
            this.reset(models, {silent: true, ...options});
        }

        this._onModelEventBind = this._onModelEvent.bind(this);
    }

    // noinspection JSValidateJSDoc
    /**
     * Add models or a model.
     *
     * @param models Models ar a model.
     * @param [options] Options. `at` – position; `merge` – merge existing models, otherwise, they are ignored.
     * @fires Collection#update
     */
    add(
        models: TModel[] | TModel | Record<string, any>[] | Record<string, any>,
        options?: {
            merge?: boolean,
            at?: number,
            silent?: boolean,
        },
    ): this {

        this.set(models, {merge: false, ...options, ...addOptions});

        return this;
    }

    // noinspection JSValidateJSDoc
    /**
     * Remove models or a model.
     *
     * @param models Models, a model or a model ID.
     * @param [options] Options.
     * @fires Collection#update
     */
    remove(
        models: (TModel | string)[] | TModel | string,
        options?: {
            silent?: boolean,
            [s: string]: any,
        },
    ): this {

        options = {...options};

        models = Array.isArray(models) ? models.slice() : [models];

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
     * @param {Model[]|Model|Record[]} models Models or a model.
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
    protected set(
        models: TModel[] | TModel | Record<string, any>[] | Record<string, any>,
        options: {
            silent?: boolean;
            at?: number;
            prepare?: boolean;
            add?: boolean;
            merge?: boolean;
            remove?: boolean;
            index?: number,
            [s: string]: any,
        },
    ): TModel[] {

        if (models == null) {
            return [];
        }

        options = {...setOptions, ...options};

        if (options.prepare && !this._isModel(models)) {
            models = this.prepareAttributes(models, options) || [];
        }

        models = Array.isArray(models) ? models.slice() : [models];

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

        let model: TModel | Record<string, any>;

        for (let i = 0; i < models.length; i++) {
            model = models[i];

            const existing = this._get(model);

            if (existing) {
                if (merge && model !== existing) {
                    let attributes = this._isModel(model) ?
                        model.attributes : model;

                    if (options.prepare) {
                        attributes = existing.prepareAttributes(attributes, options);
                    }

                    existing.setMultiple(attributes, options);
                    toMerge.push(existing);
                }

                if (!modelMap[existing.cid]) {
                    modelMap[existing.cid] = true;
                    set.push(existing);
                }

                models[i] = existing;
            } else if (add) {
                model = models[i] = this._prepareModel(model);

                if (model && model instanceof Model) {
                    toAdd.push(model);

                    this._addReference(model);

                    modelMap[model.cid] = true;
                    set.push(model);
                }
            }
        }

        // Remove stale models.
        if (remove) {
            for (let i = 0; i < this.length; i++) {
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
        } else if (toAdd.length) {
            splice(this.models, toAdd, at == null ? this.length : at);

            this.length = this.models.length;
        }

        if (!options.silent) {
            for (let i = 0; i < toAdd.length; i++) {
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

        return (models) as TModel[];
    }

    // noinspection JSValidateJSDoc
    /**
     * Reset.
     *
     * @param [models] Models to replace the collection with.
     * @param [options]
     * @return {this}
     * @fires Collection#reset
     */
    reset(
        models?: TModel[] | Record<string, any>,
        options?: {
            silent?: boolean,
            [s: string]: any,
        },
    ): this {

        this.lengthCorrection = 0;

        options = options ? _.clone(options) : {};

        for (let i = 0; i < this.models.length; i++) {
            this._removeReference(this.models[i]);
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
     * @param model A model.
     * @param [options] Options
     * @return {this}
     */
    push(
        model: TModel,
        options?: {
            silent?: boolean,
        },
    ): this {
        this.add(model, {at: this.length, ...options});

        return this;
    }

    /**
     * Remove and return the last model.
     *
     * @param [options] Options
     */
    pop(
        options?: {
            silent?: boolean,
        },
    ): TModel | null {

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
    unshift(
        model: TModel,
        options?: {
            silent?: boolean,
        },
    ): this {

        this.add(model, {at: 0, ...options});

        return this;
    }

    /**
     * Remove and return the first model.
     *
     * @param [options] Options
     * @return {Model|null}
     */
    shift(
        options?: {
            silent?: boolean,
        },
    ): TModel | null {

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
     * @param id An ID.
     */
    get(id: string): TModel | undefined {
        return this._get(id);
    }

    /**
     * Whether a model in the collection.
     *
     * @todo Usage to _has.
     * @param id An ID.
     */
    has(id: string): boolean {
        return this._has(id);
    }

    /**
     * Get a model by index.
     *
     * @param index An index. Can be negative, then counted from the end.
     */
    at(index: number): TModel | undefined {
        if (index < 0) {
            index += this.length;
        }

        return this.models[index];
    }

    /**
     * Iterates through a collection.
     *
     * @param {function(Model)} callback A function.
     */
    forEach(callback: (model: TModel) => void): this {
        this.models.forEach(callback, arguments[1]);

        return this;
    }

    /**
     * Get an index of a model. Returns -1 if not found.
     *
     * @param model A model
     */
    indexOf(model: TModel): number {
        return this.models.indexOf(model);
    }

    private _has(obj: string | Record<string, any> | TModel): boolean {
        return !!this._get(obj)
    }

    private _get(obj: string | Record<string, any> | TModel): TModel | undefined {
        if (obj == null) {
            return void 0;
        }

        // @ts-ignore
        return this._byId[obj] || this._byId[this.modelId(obj.attributes || obj)] || obj.cid && this._byId[obj.cid];
    }

    private modelId(attributes: Record<string, any>): any {
        return attributes['id'];
    }

    private _reset(): void {
        this.length = 0;
        this.models = [];
        this._byId = {};
    }

    /**
     * @param orderBy An order field.
     * @param [order] True for desc.
     */
    sort(
        orderBy: string | null,
        order: 'asc' | 'desc' | boolean | null,
    ): AjaxPromise {

        this.orderBy = orderBy;

        if (order === true) {
            order = 'desc';
        } else if (order === false) {
            order = 'asc';
        }

        this.order = order || 'asc';

        return this.fetch();
    }

    /**
     * Has previous page.
     */
    hasPreviousPage(): boolean {
        return this.offset > 0;
    }

    /**
     * Has next page.
     */
    hasNextPage(): boolean {
        return this.total - this.offset > this.length || this.total === -1;
    }

    /**
     * Next page.
     */
    nextPage(): AjaxPromise {
        return this.setOffset(this.offset + this.length);
    }

    /**
     * Previous page.
     */
    previousPage(): AjaxPromise {
        return this.setOffset(Math.max(0, this.offset - this.maxSize));
    }

    /**
     * First page.
     */
    firstPage(): AjaxPromise {
        return this.setOffset(0);
    }

    /**
     * Last page.
     */
    lastPage(): AjaxPromise {
        let offset = this.total - this.total % this.maxSize;

        if (offset === this.total) {
            offset = this.total - this.maxSize;
        }

        return this.setOffset(offset);
    }

    /**
     * Set an offset.
     *
     * @param offset Offset.
     */
    setOffset(offset: number): AjaxPromise {
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
     */
    hasMore(): boolean {
        return this.total > (this.length + this.offset + this.lengthCorrection) || this.total === -1;
    }

    /**
     * Prepare attributes.
     *
     * @param response A response from the backend.
     * @param options Options.
     */
    protected prepareAttributes(
        response: Record<string, any>[] | Record<string, any>,
        options: Record<string, any>,
    ): Record<string, any>[] {

        if (Array.isArray(response)) {
            return response;
        }

        // noinspection BadExpressionStatementJS
        options;

        this.total = response.total;

        return response.list ?? [];
    }

    // noinspection JSValidateJSDoc
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
    fetch(
        options?: {
            remove?: boolean;
            more?: boolean;
            offset?: number;
            maxSize?: number;
            orderBy?: string | null;
            order?: 'asc' | 'desc';
            [s: string]: any,
        },
    ): AjaxPromise {

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

        options.success = (response: TModel[]) => {
            options.reset ?
                this.reset(response, options) :
                this.set(response, options);

            if (success) {
                success.call(options.context, this, response, options);
            }

            this.trigger('sync', this, response, options);
        };

        const error = options.error;

        options.error = (response: any) => {
            if (error) {
                error.call(options.context, this, response, options);
            }

            this.trigger('error', this, response, options);
        };

        // @ts-ignore
        this.lastSyncPromise = Model.prototype.sync.call(this, 'read', this, options);

        return this.lastSyncPromise;
    }

    /**
     * Is being fetched.
     *
     * @return {boolean}
     */
    isBeingFetched(): boolean {
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
     */
    getWhere(): WhereItem[] {
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
    getEntityType(): string {
        // @ts-ignore
        return this.entityType || this.name;
    }

    /**
     * Reset the order to default.
     */
    resetOrderToDefault(): void {
        this.orderBy = this.defaultOrderBy;
        this.order = this.defaultOrder;
    }

    /**
     * Set an order.
     *
     * @param orderBy
     * @param [order]
     * @param [setDefault]
     */
    setOrder(
        orderBy: string | null,
        order: boolean | 'asc' | 'desc' | null,
        setDefault?: boolean,
    ): void {

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
     */
    clone(options: { withModels?: boolean } = {}): Collection<TModel> {
        let models = this.models;

        if (options.withModels) {
            models = this.models.map(m => m.clone());
        }

        // @ts-ignore
        const collection = new this.constructor(models, {
            model: this.model,
            entityType: this.entityType,
            defs: this.defs,
            orderBy: this.orderBy,
            order: this.order,
        });

        // @ts-ignore
        collection.name = this.name;
        collection.urlRoot = this.urlRoot;
        collection.url = this.url;
        collection.defaultOrder = this.defaultOrder;
        collection.defaultOrderBy = this.defaultOrderBy;
        collection.data = Espo.Utils.cloneDeep(this.data);
        collection.where = Espo.Utils.cloneDeep(this.where);
        // noinspection JSDeprecatedSymbols
        // @ts-ignore
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
    prepareModel(): TModel {
        return this._prepareModel({});
    }

    // noinspection JSUnusedGlobalSymbols
    /**
     * Compose a URL for syncing. Called from Model.sync.
     */
    protected composeSyncUrl(): string {
        return this.url;
    }

    /**
     * Listen to sync.
     *
     * Important. Owner must be specified.
     *
     * @param {{
     *     owner: import('view').default | import('model').default | import('collection').default,
     *     once?: boolean,
     *     callback: function({
     *         action: 'fetch'|'save'|'destroy'|null,
     *         response: *,
     *     }),
     * }} params
     * @return {{stop: function()}}
     * @since 10.0.0
     */
    onSync(
        params: {
            owner: import('view').default | import('model').default | import('collection').default;
            once?: boolean;
            callback: (arg0: {
                action: 'fetch' | 'save' | 'destroy' | null;
                response: any;
            }) => void;
        }
    ): { stop: () => any} {

        return onSync({
            owner: params.owner,
            once: params.once,
            target: this,
            callback: params.callback,
        });
    }

    private _isModel(object: any): object is Model {
        return object instanceof Model;
    }

    private _removeModels(models: any, options: Record<string, any>): any[] {
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

            this._removeReference(model);
        }

        return removed;
    }

    private _addReference(model: TModel) {
        this._byId[model.cid] = model;

        const id = this.modelId(model.attributes);

        if (id != null) {
            this._byId[id] = model;
        }

        model.on('all', this._onModelEventBind);
    }

    private _removeReference(model: TModel) {
        delete this._byId[model.cid];

        const id = this.modelId(model.attributes);

        if (id != null) {
            delete this._byId[id];
        }

        if (this === model.collection) {
            delete model.collection;
        }

        model.off('all', this._onModelEventBind);
    }

    private _onModelEvent(
        event: string,
        model: TModel,
        collection: Collection,
        options?: Record<string, any>,
    ): void {

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
    private _prepareModel(attributes: any): TModel {
        if (this._isModel(attributes)) {
            if (!attributes.collection) {
                attributes.collection = this;
            }

            return attributes as TModel;
        }

        const ModelClass = this.model;

        // @ts-ignore
        return new ModelClass(attributes, {
            collection: this,
            // @ts-ignore
            entityType: this.entityType || this.name,
            defs: this.defs,
        });
    }

    /**
     * Subscribe to an event.
     *
     * @param {string} name An event.
     * @param {function(...any)} callback A callback.
     */
    on(name: string, callback: (...args: unknown[]) => any): this {
        Events.on.call(this, name, callback, arguments[2]);

        return this;
    }

    /**
     * Subscribe to an event. Fired once.
     *
     * @param {string} name An event.
     * @param {function(...any)} callback A callback.
     */
    once(name: string, callback: (...args: unknown[]) => void): this {
        Events.once.call(this, name, callback, arguments[2]);

        return this;
    }

    /**
     * Unsubscribe from an event or all events.
     *
     * @param {string} [name] From a specific event.
     * @param {function()} [callback] From a specific callback.
     */
    off(name?: string, callback?: (...args: unknown[]) => void): this {
        Events.off.call(this, name, callback);

        return this;
    }

    /**
     * Subscribe to an event of other object.
     *
     * @param {Object} other What to listen.
     * @param {string} name An event.
     * @param callback A callback.
     */
    listenTo(other: object, name: string, callback: (...args: unknown[]) => void): this {
        Events.listenTo.call(this, other, name, callback);

        return this;
    }

    /**
     * Subscribe to an event of other object. Fired once. Will be automatically unsubscribed on view removal.
     *
     * @param {Object} other What to listen.
     * @param {string} name An event.
     * @param {function()} callback A callback.
     */
    listenToOnce(other: object, name: string, callback: (...args: unknown[]) => void): this {
        Events.listenToOnce.call(this, other, name, callback);

        return this;
    }

    /**
     * Stop listening to other object. No arguments will remove all listeners.
     *
     * @param {Object} [other] To remove listeners to a specific object.
     * @param {string} [name] To remove listeners to a specific event.
     * @param {function()} [callback] To remove listeners to a specific callback.
     */
    stopListening(other?: object, name?: string, callback?: (...args: unknown[]) => any): this {
        Events.stopListening.call(this, other, name, callback);

        return this;
    }

    /**
     * Trigger an event.
     *
     * @param {string} name An event.
     * @param {...*} parameters Arguments.
     */
    trigger(name: string, ...parameters: any[]): this {
        Events.trigger.call(this, name, ...parameters);

        return this;
    }
}

// @ts-ignore
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

const splice = (array: any[], insert: string | any[], at: number): void => {
    at = Math.min(Math.max(at, 0), array.length);

    const tail = Array(array.length - at);
    const length = insert.length;

    for (let i = 0; i < tail.length; i++) {
        tail[i] = array[i + at];
    }

    for (let i = 0; i < length; i++) {
        array[i + at] = insert[i];
    }

    for (let i = 0; i < tail.length; i++) {
        array[i + length + at] = tail[i];
    }
};
