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

/** @module model */

import {Events, View as BullView} from 'bullbone';
import _ from 'underscore';
import DefaultValueProvider from 'helpers/model/default-value-provider';
import {onModelChange, onSync} from 'util/event';
import {AjaxPromise} from 'util/ajax';

/**
 * When attributes have changed.
 *
 * @event Model#change
 * @param {Model} model A model.
 * @param {Record.<string, *> & {action?: string|'ui'|'save'|'fetch'|'cancel-edit'}} o Options.
 */

/**
 * On sync with backend.
 *
 * @event Model#sync
 * @param {Model} model A model.
 * @param {Object} response Response from backend.
 * @param {Record.<string, *> & {action?: 'fetch'|'save'|'destroy'}} o Options.
 */

/**
 * @property link A link.
 * @property model A model.
 */
interface SetRelateItem {
    link: string,
    model: Model,
}

/**
 * Definitions.
 */
export interface Defs {
    fields?: Record<string, FieldDefs & Record<string, any>>
    links?: Record<string, Record<string, any>>
}

/**
 * Field definitions.
 */
interface FieldDefs {
    type: string,
}

type Collection = import('collection').default;
type User = import('models/user').default;

/**
 * A model.
 */
export default class Model<T extends Record<string, any> = Record<string, any>> {

    /**
     * A root URL. An ID will be appended. Used for syncing with backend.
     */
    urlRoot: string | null = null

    /**
     * A URL. If not empty, then will be used for syncing instead of `urlRoot`.
     */
    url: string | null = null

    /**
     * A name.
     */
    name: string | null = null

    /**
     * An entity type.
     */
    entityType: string | null = null

    /**
     * A last request promise.
     */
    lastSyncPromise: AjaxPromise | null = null

    private _pending: any
    private _changing: boolean

    /**
     * An ID attribute.
     */
    private readonly idAttribute: string

    /**
     * A record ID.
     */
    public id: string | null

    /**
     * An instance ID.
     */
    public readonly cid: string

    /**
     * Attribute values.
     */
    public attributes: Partial<T>

    /**
     * A parent collection the model belongs to.
     */
    public collection: Collection | undefined

    private changed: Partial<T>
    private _previousAttributes: null | Partial<T>

    /**
     * Definitions.
     * @internal
     */
    protected defs: Defs

    constructor(
        attributes: Partial<T> | Model<T>,
        options: {
            collection?: Collection;
            entityType?: string;
            urlRoot?: string;
            url?: string;
            defs?: Defs;
            user?: User;
        },
    ) {
        options = options || {};

        this.idAttribute = 'id';
        this.id = null;
        this.cid = _.uniqueId('c');

        this.attributes = {};

        if (options.collection) {
            this.collection = options.collection;
        }

        attributes = (attributes ?? {}) as Partial<T>;

        this.setMultiple(attributes);

        this.defs = options.defs || {};

        if (!this.defs.fields) {
            this.defs.fields = {};
        }

        if (options.entityType) {
            this.entityType = options.entityType;
            this.name = options.entityType;
            this.urlRoot = options.entityType;
        }

        this.urlRoot = options.urlRoot || this.urlRoot;
        this.url = options.url || this.url;

        this.changed = {};
        this._previousAttributes = null;
    }

    /**
     * @param [method] HTTP method.
     * @param {Model} model
     * @param [options] Options.
     */
    protected sync(
        method: string,
        model: this,
        options?: {
            attributes?: any,
            error?: any,
            textStatus?: any,
            errorThrown?: any,
            context?: any,
            bypassRequest?: any,
            xhr?: any,
            [s: string]: any,
        },
    ): AjaxPromise {

        const methodMap = {
            'create': 'POST',
            'update': 'PUT',
            'patch': 'PUT',
            'delete': 'DELETE',
            'read': 'GET',
        };

        const httpMethod = methodMap[method];

        if (!httpMethod) {
            throw new Error(`Bad request method '${method}'.`);
        }

        options = options || {};

        const url = this.composeSyncUrl();

        if (!url) {
            throw new Error(`No 'url'.`);
        }

        const data = model && ['create', 'update', 'patch'].includes(method) ?
            (options.attributes || model.getClonedAttributes()) : null;

        const error = options.error;

        options.error = (xhr: XMLHttpRequest, textStatus: string, errorThrown: any) => {
            options.textStatus = textStatus;
            options.errorThrown = errorThrown;

            if (error) {
                error.call(options.context, xhr, textStatus, errorThrown);
            }
        };

        const stringData = data ? JSON.stringify(data) : null;

        const ajaxPromise = !options.bypassRequest ?
            Espo.Ajax.request(url, httpMethod, stringData, options) :
            Promise.resolve();

        options.xhr = ajaxPromise.xhr;

        model.trigger('request', url, httpMethod, data, ajaxPromise, options);

        return ajaxPromise;
    }

    // noinspection JSValidateJSDoc
    /**
     * Set an attribute value.
     *
     * @param attribute An attribute name or a {key => value} object.
     * @param {*} [value] A value or options if the first argument is an object.
     * @param [options] Options. `silent` won't trigger a `change` event.
     * @returns {this}
     * @fires Model#change Unless `{silent: true}`.
     */
    set(
        attribute: keyof T,
        value: any,
        options: {silent: boolean} & Record<string, any> = undefined
    ): this {

        if (attribute == null) {
            return this;
        }

        let attributes: any;

        if (typeof attribute === 'object') {
            return this.setMultiple(attribute, value);
        }

        attributes = {};
        attributes[attribute] = value;

        return this.setMultiple(attributes, options);
    }

    // noinspection JSValidateJSDoc
    /**
     * Set attributes values.
     *
     * @param attributes
     * @param [options] Options. `silent` won't trigger a `change` event.
     *     `sync` can be used to emulate syncing.
     * @return {this}
     * @fires Model#change Unless `{silent: true}`.
     * @copyright Credits to Backbone.js.
     */
    setMultiple(
        attributes: Partial<T>,
        options: {
            silent?: boolean,
            unset?: boolean,
            sync?: boolean,
        } & Record<string, any> = undefined,
    ): this {

        if (this.idAttribute in attributes) {
            this.id = attributes[this.idAttribute];
        }

        options = options || {};

        if (options.ui && !options.action) {
            options.action = 'ui';
        }

        if (!options.ui && options.action === 'ui') {
            options.ui = true;
        }

        const changes = [];
        const changing = this._changing;

        this._changing = true;

        if (!changing) {
            this._previousAttributes = _.clone(this.attributes);
            this.changed = {};
        }

        const current = this.attributes;
        const changed = this.changed;
        const previous = this._previousAttributes;

        for (const attribute in attributes) {
            const value = attributes[attribute];

            if (!_.isEqual(current[attribute], value)) {
                changes.push(attribute);
            }

            if (!_.isEqual(previous[attribute], value)) {
                changed[attribute] = value;
            } else {
                delete changed[attribute];
            }

            options.unset ?
                delete current[attribute] :
                current[attribute] = value;
        }

        if (!options.silent) {
            if (changes.length) {
                this._pending = options;
            }

            for (let i = 0; i < changes.length; i++) {
                this.trigger('change:' + changes[i], this, current[changes[i]], options);
            }
        }

        if (options.sync) {
            if (this.collection) {
                const modelSyncOptions = {...options, action: 'set'};

                this.collection.trigger('model-sync', this, modelSyncOptions);
            }
        }

        if (changing) {
            return this;
        }

        if (!options.silent) {
            // Changes can be recursively nested within `change` events.
            while (this._pending) {
                options = this._pending;
                this._pending = false;

                this.trigger('change', this, options);
            }
        }

        this._pending = false;
        this._changing = false;

        return this;
    }

    /**
     * Unset an attribute.
     *
     * @param attribute An attribute.
     * @param [options] Options.
     * @return {Model}
     */
    unset(
        attribute: keyof T,
        options: {silent?: boolean} & Record<string, any>,
    ): Model {

        options = {...options, unset: true};

        const attributes = {} as Partial<T>;
        attributes[attribute] = null;

        return this.setMultiple(attributes, options);
    }

    /**
     * Get an attribute value.
     *
     * @param attribute An attribute name.
     * @returns {*}
     */
    get(attribute: keyof T): any {
        if (attribute === this.idAttribute && this.id) {
            return this.id;
        }

        return this.attributes[attribute];
    }

    /**
     * Whether attribute is set.
     *
     * @param {string} attribute An attribute name.
     * @returns {boolean}
     */
    has(attribute: keyof T): boolean {
        const value = this.get(attribute);

        return typeof value !== 'undefined';
    }

    /**
     * Removes all attributes from the model.
     * Fires a `change` event unless `silent` is passed as an option.
     *
     * @param {{silent?: boolean} & Object.<string, *>} [options] Options.
     */
    clear(
        options: {silent?: boolean} & Record<string, any>,
    ): this {

        const attributes = {} as Partial<T>;

        for (const key in this.attributes) {
            attributes[key] = void 0;
        }

        options = {...options, unset: true};

        return this.setMultiple(attributes, options);
    }

    /**
     * Whether is new.
     */
    isNew(): boolean {
        return !this.id;
    }

    /**
     * Whether an attribute changed. To be called only within a 'change' event handler.
     *
     * @param [attribute]
     */
    hasChanged(attribute?: keyof T): boolean {
        if (!attribute) {
            return !_.isEmpty(this.changed);
        }

        return _.has(this.changed, attribute as string);
    }

    /**
     * Get changed attribute values. To be called only within a 'change' event handler.
     */
    changedAttributes(): Partial<T> {
        return this.hasChanged() ? _.clone(this.changed) : {};
    }

    /**
     * Get previous attributes. To be called only within a 'change' event handler.
     */
    previousAttributes(): Partial<T> {
        return _.clone(this._previousAttributes);
    }

    /**
     * Get a previous attribute value. To be called only within a 'change' event handler.
     *
     * @param attribute
     * @return {*}
     */
    previous(attribute: keyof T): any {
        if (!this._previousAttributes) {
            return null;
        }

        return this._previousAttributes[attribute];
    }

    // noinspection JSValidateJSDoc
    /**
     * Fetch values from the backend.
     *
     * @param [options] Options.
     * @fires Model#sync
     */
    fetch(options: Record<string, any>): AjaxPromise {
        options = {...options};

        options.action = 'fetch';

        // For bc.
        const success = options.success;

        options.success = (response: any) => {
            const serverAttributes = this.prepareAttributes(response, options);

            this.setMultiple(serverAttributes, options);

            if (success) {
                success.call(options.context, this, response, options);
            }

            this.trigger('sync', this, response, options);

            if (this.collection) {
                this.collection.trigger('model-sync', this, options);
            }
        };

        this.lastSyncPromise = this.sync('read', this, options);

        return this.lastSyncPromise;
    }

    // noinspection JSValidateJSDoc
    /**
     * Save values to the backend.
     *
     * @param [attributes] Attribute values.
     * @param [options] Options. Use `patch` to send a PATCH request. If `wait`, attributes will be
     *     set only after the request is completed.
     * @fires Model#sync
     * @copyright Credits to Backbone.js.
     */
    save(
        attributes?: Partial<T>,
        options?: {
            patch?: boolean,
            wait?: boolean,
            [s: string]: any;
        },
    ): AjaxPromise {

        options = {...options};

        if (attributes && !options.wait) {
            this.setMultiple(attributes, options);
        }

        const success = options.success;

        const setAttributes = this.attributes;

        options.success = (response: any) => {
            this.attributes = setAttributes;

            let responseAttributes = this.prepareAttributes(response, options);

            if (options.wait) {
                responseAttributes = {...setAttributes, ...responseAttributes};
            }

            options.action = 'save';

            if (responseAttributes) {
                this.setMultiple(responseAttributes, options);
            }

            if (success) {
                success.call(options.context, this, response, options);
            }

            this.trigger('sync', this, response, options);

            if (this.collection) {
                this.collection.trigger('model-sync', this, options);
            }
        };

        const error = options.error;

        options.error = (response: any) => {
            if (error) {
                error.call(options.context, this, response, options);
            }

            this.trigger('error', this, response, options);
        };

        if (attributes && options.wait) {
            // Set temporary attributes to properly find new IDs.
            this.attributes =  {...setAttributes, ...attributes};
        }

        const method = this.isNew() ?
            'create' :
            (options.patch ? 'patch' : 'update');

        if (method === 'patch') {
            options.attributes = attributes;
        }

        const result = this.sync(method, this, options);

        this.attributes = setAttributes;

        return result;
    }

    // noinspection JSValidateJSDoc
    /**
     * Delete the record in the backend.
     *
     * @param [options] Options. If `wait`, unsubscribing and
     *     removal from the collection will wait for a successful response.
     * @fires Model#sync
     * @copyright Credits to Backbone.js.
     */
    destroy(
        options: {wait?: boolean} & Record<string, any> = {},
    ): AjaxPromise | Promise<void> {

        options = {...options}

        const success = options.success;

        const collection = this.collection;

        const destroy = () => {
            this.stopListening();
            this.trigger('destroy', this, collection, options);
        };

        options.success = (response: any) => {
            if (options.wait) {
                destroy();
            }

            if (success) {
                success.call(options.context, this, response, options);
            }

            if (!this.isNew()) {
                const syncOptions = {...options};

                syncOptions.action = 'destroy';

                this.trigger('sync', this, response, syncOptions);

                if (collection) {
                    collection.trigger('model-sync', this, syncOptions);
                }
            }
        };

        if (this.isNew()) {
            _.defer(options.success);

            if (!options.wait) {
                destroy();
            }

            return Promise.resolve();
        }

        const error = options.error;

        options.error = (response: any) => {
            if (error) {
                error.call(options.context, this, response, options);
            }

            this.trigger('error', this, response, options);
        };

        const result = this.sync('delete', this, options);

        if (!options.wait) {
            destroy();
        }

        return result;
    }

    /**
     * Compose a URL for syncing.
     */
    protected composeSyncUrl(): string {
        if (this.url) {
            return this.url;
        }

        let urlRoot = this.urlRoot;

        if (!urlRoot && this.collection) {
            urlRoot = this.collection.urlRoot
        }

        if (!urlRoot) {
            throw new Error("No urlRoot.");
        }

        if (this.isNew()) {
            return urlRoot;
        }

        const id = this.get(this.idAttribute);

        return urlRoot.replace(/[^\/]$/, '$&/') + encodeURIComponent(id);
    }

    // noinspection JSUnusedLocalSymbols
    /**
     * Prepare attributes.
     *
     * @param {*} response A response from the backend.
     * @param {Object.<string, *>} options Options.
     * @return {*} Attributes.
     * @internal
     */
    prepareAttributes(response: any, options: Record<string, any>): any {
        // noinspection BadExpressionStatementJS
        options;

        return response;
    }

    /**
     * Clone.
     *
     * @return {Model}
     */
    clone(): this {

        // @todo Revise.
        // @ts-ignore
        return new this.constructor(
            Espo.Utils.cloneDeep(this.attributes),
            {
                entityType: this.entityType,
                urlRoot: this.urlRoot,
                url: this.url,
                defs: this.defs,
            }
        );
    }

    /**
     * Set defs.
     *
     * @param defs Definitions.
     */
    setDefs(defs: Defs): void {
        this.defs = defs || {};

        if (!this.defs.fields) {
            this.defs.fields = {};
        }
    }

    /**
     * Get cloned attribute values.
     */
    getClonedAttributes(): {[s: string]: any} {
        return Espo.Utils.cloneDeep(this.attributes);
    }

    /**
     * Populate default values.
     */
    populateDefaults() {
        let defaultHash = {};

        const fieldDefs = this.defs.fields;

        for (const field in fieldDefs) {
            if (this.hasFieldParam(field, 'default')) {
                try {
                    defaultHash[field] = this.parseDefaultValue(this.getFieldParam(field, 'default'));
                } catch (e) {
                    console.error(e);
                }
            }

            const defaultAttributes = this.getFieldParam(field, 'defaultAttributes');

            if (defaultAttributes) {
                for (const attribute in defaultAttributes) {
                    defaultHash[attribute] = defaultAttributes[attribute];
                }
            }
        }

        defaultHash = Espo.Utils.cloneDeep(defaultHash);

        for (const attr in defaultHash) {
            if (this.has(attr)) {
                delete defaultHash[attr];
            }
        }

        this.setMultiple(defaultHash, {silent: true});
    }

    /**
     * @private
     * @param {*} defaultValue
     * @returns {*}
     */
    parseDefaultValue(defaultValue: any): any {
        if (
            typeof defaultValue === 'string' &&
            defaultValue.indexOf('javascript:') === 0
        ) {
            const code = defaultValue.substring(11).trim();

            const provider = new DefaultValueProvider();

            defaultValue = provider.get(code);
        }

        return defaultValue;
    }

    /**
     * Get a link multiple column value.
     *
     * @param {string} field
     * @param {string} column
     * @param {string} id
     * @returns {*}
     */
    getLinkMultipleColumn(field: string, column: string, id: string): any {
        return ((this.get(field + 'Columns') || {})[id] || {})[column];
    }

    /**
     * Set relate data (when creating a related record).
     */
    setRelate(data: SetRelateItem | SetRelateItem[]): void {

        const setRelate = (options: SetRelateItem): void => {
            const link = options.link;
            const model = /** @type {Model} */options.model;

            if (!link || !model) {
                throw new Error('Bad related options');
            }

            const type = this.defs.links[link].type;

            switch (type) {
                case 'belongsToParent':
                    this.set(link + 'Id', model.id);
                    this.set(link + 'Type', model.entityType);
                    this.set(link + 'Name', model.get('name'));

                    break;

                case 'belongsTo':
                    this.set(link + 'Id', model.id);
                    this.set(link + 'Name', model.get('name'));

                    break;

                case 'hasMany':
                    const ids = [];
                    ids.push(model.id);

                    const names = {};

                    names[model.id] = model.get('name');

                    this.set(link + 'Ids', ids);
                    this.set(link + 'Names', names);

                    break;
            }
        };

        if (Array.isArray(data)) {
            data.forEach((options: any): void => setRelate(options));

            return;
        }

        setRelate(data);
    }

    /**
     * Get a field list.
     */
    getFieldList(): string[] {
        if (!this.defs || !this.defs.fields) {
            return [];
        }

        return Object.keys(this.defs.fields);
    }

    /**
     * Get a field type.
     *
     * @param {string} field
     * @returns {string|null}
     */
    getFieldType(field: string): string | null {
        if (!this.defs || !this.defs.fields) {
            return null;
        }

        if (field in this.defs.fields) {
            return this.defs.fields[field].type || null;
        }

        return null;
    }

    /**
     * Get a field param.
     *
     * @param {string} field
     * @param {string} param
     * @returns {*}
     */
    getFieldParam(field: string, param: string): any {
        if (!this.defs || !this.defs.fields) {
            return null;
        }

        if (field in this.defs.fields) {
            if (param in this.defs.fields[field]) {
                return this.defs.fields[field][param];
            }
        }

        return null;
    }

    hasFieldParam(field: string, param: string): boolean {
        if (!this.defs || !this.defs.fields) {
            return false;
        }

        if (field in this.defs.fields) {
            if (param in this.defs.fields[field]) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the link type.
     *
     * @param link A link name.
     */
    getLinkType(link: string): string | null {
        if (!this.defs || !this.defs.links) {
            return null;
        }

        if (link in this.defs.links) {
            return this.defs.links[link].type || null;
        }

        return null;
    }

    /**
     * Get the link parameter value.
     *
     * @param link A link name.
     * @param param A parameter.
     */
    getLinkParam(link: string, param: string): any {
        if (!this.defs || !this.defs.links) {
            return null;
        }

        if (link in this.defs.links) {
            if (param in this.defs.links[link]) {
                return this.defs.links[link][param];
            }
        }

        return null;
    }

    /**
     * Is the field read-only.
     *
     * @param field A field name.
     */
    isFieldReadOnly(field: string): boolean {
        return this.getFieldParam(field, 'readOnly') || false;
    }

    /**
     * If the field required.
     *
     * @param field A field name.
     */
    isRequired(field: string): boolean {
        return this.getFieldParam(field, 'required') || false;
    }

    /**
     * Get IDs of a link-multiple field.
     *
     * @param field A link-multiple field name.
     */
    getLinkMultipleIdList(field: string): string[] {
        return this.get(field + 'Ids') || [];
    }

    /**
     * Get team IDs.
     */
    getTeamIdList(): string[] {
        return this.get('teamsIds') || [];
    }

    /**
     * Whether it has a field.
     *
     * @param {string} field A field name.
     */
    hasField(field: string): boolean {
        return ('fields' in this.defs) && (field in this.defs.fields);
    }

    /**
     * Has a link.
     *
     * @param {string} link A link name.
     */
    hasLink(link: string): boolean {
        return ('links' in this.defs) && (link in this.defs.links);
    }

    /**
     * Is editable.
     */
    isEditable(): boolean {
        return true;
    }

    /**
     * Is removable.
     */
    isRemovable(): boolean {
        return true;
    }

    /**
     * Get an entity type.
     */
    getEntityType(): string {
        return this.name;
    }

    /**
     * Abort the last fetch.
     */
    abortLastFetch(): void {
        if (this.lastSyncPromise && this.lastSyncPromise.getReadyState() < 4) {
            this.lastSyncPromise.abort();
        }
    }

    /**
     * Listen to attribute change.
     *
     * Important. Owner must be specified.
     *
     * @param {{
     *     owner: import('view').default | import('model').default | import('collection').default,
     *     attributes?: string[],
     *     once?: boolean,
     *     callback: function({
     *         ui: boolean|null,
     *         action: string|'ui'|'save'|'fetch'|'cancel-edit'|null,
     *         fromView: import('views/fields/base').default,
     *     }),
     * }} params
     * @return {{stop: function()}}
     * @since 10.0.0
     */
    onChange(
        params: {
            owner: import('view').default | import('model').default | import('collection').default,
            attributes?: string[],
            once?: boolean,
            callback: (event: {
                ui: boolean | null,
                action: string | 'ui' | 'save' | 'fetch' | 'cancel-edit' | null,
                // @todo Remove ignore.
                // @ts-ignore
                fromView: import('views/fields/base').default,
            }) => any,
        }
    ): {stop: () => any} {

        return onModelChange({
            owner: params.owner,
            once: params.once,
            target: this,
            attributes: params.attributes,
            callback: params.callback,
        });
    }

    // noinspection JSUnusedGlobalSymbols
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
            owner: import('view').default | import('model').default | import('collection').default,
            once?: boolean,
            callback: (event: {
                action: 'fetch' | 'save' | 'destroy' | null,
                response: any;
            }) => any,
        }
    ): {stop: () => any} {

        return onSync({
            owner: params.owner,
            once: params.once,
            target: this,
            callback: params.callback,
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
    listenToOnce(other: object, name: string,  callback: (...args: unknown[]) => void): this {
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
Model.extend = BullView.extend;
