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

/** @module model */

import {Events, View as BullView} from 'bullbone';
import _ from 'underscore';

/**
 * When attributes have changed.
 *
 * @event Model#change
 * @param {Model} model A model.
 * @param {Object.<string, *>} o Options.
 */

/**
 * On sync with backend.
 *
 * @event Model#sync
 * @param {Model} model A model.
 * @param {Object} response Response from backend.
 * @param {Object.<string, *>} o Options.
 */

/**
 * Defs.
 *
 * @typedef module:model~defs
 * @type {Object}
 * @property {Object.<string, Object.<string, *>>} [fields] Fields.
 * @property {Object.<string, Object.<string, *>>} [links] Links.
 */

/**
 * A model.
 *
 * @mixes Bull.Events
 */
class Model {

    /**
     * A root URL. An ID will be appended. Used for syncing with backend.
     *
     * @type {string|null}
     */
    urlRoot = null

    /**
     * A URL. If not empty, then will be used for syncing instead of `urlRoot`.
     *
     * @type {string|null}
     */
    url = null

    /**
     * A name.
     *
     * @type {string|null}
     */
    name = null

    /**
     * An entity type.
     *
     * @type {string|null}
     */
    entityType = null

    /**
     * A last request promise.
     *
     * @type {module:ajax.AjaxPromise|null}
     */
    lastSyncPromise = null

    /** @private */
    _pending
    /** @private */
    _changing

    /**
     * @param {Object.<string, *>|Model} [attributes]
     * @param {{
     *     collection?: module:collection,
     *     entityType?: string,
     *     urlRoot?: string,
     *     url?: string,
     *     defs?: module:model~defs,
     *     user?: module:models/user,
     *     dateTime?: module:date-time,
     * }} [options]
     */
    constructor(attributes, options) {
        options = options || {};

        /**
         * An ID attribute.
         * @type {string}
         */
        this.idAttribute = 'id';

        /**
         * A record ID.
         * @type {string|null}
         */
        this.id = null;

        /**
         * An instance ID.
         * @type {string}
         */
        this.cid = _.uniqueId('c');

        /**
         * Attribute values.
         * @type {Object.<string, *>}
         */
        this.attributes = {};

        if (options.collection) {
            this.collection = options.collection;
        }

        this.set(attributes || {});

        /**
         * Definitions.
         */
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

        /** @private */
        this.dateTime = options.dateTime || null;

        /** @private */
        this.changed = {};
        /** @private */
        this._previousAttributes = null;
    }

    /**
     * @protected
     * @param {string} [method] HTTP method.
     * @param {Model} model
     * @param {Object.<string, *>} [options]
     * @returns {module:ajax.AjaxPromise|Promise}
     */
    sync(method, model, options) {
        const methodMap = {
            'create': 'POST',
            'update': 'PUT',
            'patch': 'PUT',
            'delete': 'DELETE',
            'read': 'GET',
        };

        let httpMethod = methodMap[method];

        if (!httpMethod) {
            throw new Error(`Bad request method '${method}'.`);
        }

        options = options || {};

        let url = this.composeSyncUrl();

        if (!url) {
            throw new Error(`No 'url'.`);
        }

        const data = model && ['create', 'update', 'patch'].includes(method) ?
            (options.attributes || model.getClonedAttributes()) : null;

        let error = options.error;

        options.error = (xhr, textStatus, errorThrown) => {
            options.textStatus = textStatus;
            options.errorThrown = errorThrown;

            if (error) {
                error.call(options.context, xhr, textStatus, errorThrown);
            }
        };

        let stringData = data ? JSON.stringify(data) : null;

        const ajaxPromise = !options.bypassRequest ?
            Espo.Ajax.request(url, httpMethod, stringData, options) :
            Promise.resolve();

        options.xhr = ajaxPromise.xhr;

        model.trigger('request', url, httpMethod, data, ajaxPromise, options);

        return ajaxPromise;
    }

    /**
     * Set an attribute value.
     *
     * @param {(string|Object)} attribute An attribute name or a {key => value} object.
     * @param {*} [value] A value or options if the first argument is an object.
     * @param {{silent?: boolean} & Object.<string, *>} [options] Options. `silent` won't trigger a `change` event.
     * @returns {this}
     * @fires Model#change Unless `{silent: true}`.
     */
    set(attribute, value, options) {
        if (attribute == null) {
            return this;
        }

        let attributes;

        if (typeof attribute === 'object') {
            return this.setMultiple(attribute, value);
        }

        attributes = {};
        attributes[attribute] = value;

        return this.setMultiple(attributes, options);
    }

    /**
     * Set attributes values.
     *
     * @param {Object.<string, *>} attributes
     * @param {{
     *     silent?: boolean,
     *     unset?: boolean,
     * } & Object.<string, *>} [options] Options. `silent` won't trigger a `change` event.
     * @return {this}
     * @fires Model#change Unless `{silent: true}`.
     * @copyright Credits to Backbone.js.
     */
    setMultiple(attributes, options) {
        if (this.idAttribute in attributes) {
            this.id = attributes[this.idAttribute];
        }

        options = options || {};

        let changes = [];
        let changing = this._changing;

        this._changing = true;

        if (!changing) {
            this._previousAttributes = _.clone(this.attributes);
            this.changed = {};
        }

        let current = this.attributes;
        let changed = this.changed;
        let previous = this._previousAttributes;

        for (let attribute in attributes) {
            let value = attributes[attribute];

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
     * @param {string} attribute An attribute.
     * @param {{silent?: boolean} & Object.<string, *>} [options] Options.
     * @return {Model}
     */
    unset(attribute, options) {
        options = {...options, unset: true};

        let attributes = {};
        attributes[attribute] = null;

        return this.setMultiple(attributes, options);
    }

    /**
     * Get an attribute value.
     *
     * @param {string} attribute An attribute name.
     * @returns {*}
     */
    get(attribute) {
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
    has(attribute) {
        let value = this.get(attribute);

        return typeof value !== 'undefined';
    }

    /**
     * Removes all attributes from the model.
     * Fires a `change` event unless `silent` is passed as an option.
     *
     * @param {{silent?: boolean} & Object.<string, *>} [options] Options.
     */
    clear(options) {
        let attributes = {};

        for (let key in this.attributes) {
            attributes[key] = void 0;
        }

        options = {...options, unset: true};

        return this.set(attributes, options);
    }

    /**
     * Whether is new.
     *
     * @returns {boolean}
     */
    isNew() {
        return !this.id;
    }

    /**
     * Whether an attribute changed. To be called only within a 'change' event handler.
     *
     * @param {string} [attribute]
     * @return {boolean}
     */
    hasChanged(attribute) {
        if (!attribute) {
            return !_.isEmpty(this.changed);
        }

        return _.has(this.changed, attribute);
    }

    /**
     * Get changed attribute values. To be called only within a 'change' event handler.
     *
     * @return {Object.<string, *>}
     */
    changedAttributes() {
        return this.hasChanged() ? _.clone(this.changed) : {};
    }

    /**
     * Get previous attributes. To be called only within a 'change' event handler.
     *
     * @return {Object.<string, *>}
     */
    previousAttributes() {
        return _.clone(this._previousAttributes);
    }

    /**
     * Get a previous attribute value. To be called only within a 'change' event handler.
     *
     * @param attribute
     * @return {*}
     */
    previous(attribute) {
        if (!this._previousAttributes) {
            return null;
        }

        return this._previousAttributes[attribute];
    }

    /**
     * Fetch values from the backend.
     *
     * @param {Object.<string, *>} [options] Options.
     * @returns {Promise}
     * @fires Model#sync
     */
    fetch(options) {
        options = {...options};

        let success = options.success;

        options.success = response => {
            let serverAttributes = this.prepareAttributes(response, options);

            this.set(serverAttributes, options);

            if (success) {
                success.call(options.context, this, response, options);
            }

            this.trigger('sync', this, response, options);
        };

        this.lastSyncPromise = this.sync('read', this, options);

        return this.lastSyncPromise;
    }

    /**
     * Save values to the backend.
     *
     * @param {Object.<string, *>} [attributes] Attribute values.
     * @param {{
     *     patch?: boolean,
     *     wait?: boolean,
     * } & Object.<string, *>} [options] Options.
     * @returns {Promise}
     * @fires Model#sync
     * @copyright Credits to Backbone.js.
     */
    save(attributes, options) {
        options = {...options};

        if (attributes && !options.wait) {
            this.setMultiple(attributes, options);
        }

        let success = options.success;

        let setAttributes = this.attributes;

        options.success = response => {
            this.attributes = attributes;

            let responseAttributes = this.prepareAttributes(response, options);

            if (options.wait) {
                responseAttributes = {...attributes, ...responseAttributes};
            }

            if (responseAttributes) {
                this.setMultiple(responseAttributes, options);
            }

            if (success) {
                success.call(options.context, this, response, options);
            }

            this.trigger('sync', this, response, options);
        };

        let error = options.error;

        options.error = response => {
            if (error) {
                error.call(options.context, this, response, options);
            }

            this.trigger('error', this, response, options);
        };

        if (attributes && options.wait) {
            // Set temporary attributes to properly find new ids.
            this.attributes =  {...setAttributes, ...attributes};
        }

        let method = this.isNew() ?
            'create' :
            (options.patch ? 'patch' : 'update');

        if (method === 'patch') {
            options.attributes = attributes;
        }

        const result = this.sync(method, this, options);

        this.attributes = setAttributes;

        return result;
    }

    /**
     * Delete the record in the backend.
     *
     * @param {{wait: boolean} & Object.<string, *>} [options] Options.
     * @returns {Promise}
     * @fires Model#sync
     * @copyright Credits to Backbone.js.
     */
    destroy(options) {
        options = _.clone(options || {});

        let success = options.success;

        const destroy = () => {
            this.stopListening();
            this.trigger('destroy', this, this.collection, options);
        };

        options.success = response => {
            if (options.wait) {
                destroy();
            }

            if (success) {
                success.call(options.context, this, response, options);
            }

            if (!this.isNew()) {
                this.trigger('sync', this, response, options);
            }
        };

        if (this.isNew()) {
            _.defer(options.success);

            if (!options.wait) {
                destroy();
            }

            return Promise.resolve();
        }

        let error = options.error;

        options.error = response => {
            if (error) {
                error.call(options.context, this, response, options);
            }

            this.trigger('error', this, response, options);
        };

        let result = this.sync('delete', this, options);

        if (!options.wait) {
            destroy();
        }

        return result;
    }

    /**
     * Compose a URL for syncing.
     *
     * @protected
     * @return {string}
     */
    composeSyncUrl() {
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

        let id = this.get(this.idAttribute);

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
    prepareAttributes(response, options) {
        return response;
    }

    /**
     * Clone.
     *
     * @return {Model}
     */
    clone() {
        return new this.constructor(
            Espo.Utils.cloneDeep(this.attributes),
            {
                entityType: this.entityType,
                urlRoot: this.urlRoot,
                url: this.url,
                defs: this.defs,
                dateTime: this.dateTime,
            }
        );
    }

    /**
     * Set defs.
     *
     * @param {module:model~defs} defs
     */
    setDefs(defs) {
        this.defs = defs || {};

        if (!this.defs.fields) {
            this.defs.fields = {};
        }
    }

    /**
     * Get cloned attribute values.
     *
     * @returns {Object.<string, *>}
     */
    getClonedAttributes() {
        return Espo.Utils.cloneDeep(this.attributes);
    }

    /**
     * Populate default values.
     */
    populateDefaults() {
        let defaultHash = {};

        const fieldDefs = this.defs.fields;

        for (let field in fieldDefs) {
            let defaultValue = this.getFieldParam(field, 'default');

            if (defaultValue !== null) {
                try {
                    defaultValue = this.parseDefaultValue(defaultValue);

                    defaultHash[field] = defaultValue;
                }
                catch (e) {
                    console.error(e);
                }
            }

            let defaultAttributes = this.getFieldParam(field, 'defaultAttributes');

            if (defaultAttributes) {
                for (let attribute in defaultAttributes) {
                    defaultHash[attribute] = defaultAttributes[attribute];
                }
            }
        }

        defaultHash = Espo.Utils.cloneDeep(defaultHash);

        for (let attr in defaultHash) {
            if (this.has(attr)) {
                delete defaultHash[attr];
            }
        }

        this.set(defaultHash, {silent: true});
    }

    /**
     * @protected
     * @param {*} defaultValue
     * @returns {*}
     */
    parseDefaultValue(defaultValue) {
        if (
            typeof defaultValue === 'string' &&
            defaultValue.indexOf('javascript:') === 0
        ) {
            let code = defaultValue.substring(11);

            defaultValue = (new Function( "with(this) { " + code + "}")).call(this);
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
    getLinkMultipleColumn(field, column, id) {
        return ((this.get(field + 'Columns') || {})[id] || {})[column];
    }

    /**
     * Set relate data (when creating a related record).
     *
     * @param {Object} data
     */
    setRelate(data) {
        let setRelate = options => {
            let link = options.link;
            let model = options.model;

            if (!link || !model) {
                throw new Error('Bad related options');
            }

            let type = this.defs.links[link].type;

            switch (type) {
                case 'belongsToParent':
                    this.set(link + 'Id', model.id);
                    this.set(link + 'Type', model.name);
                    this.set(link + 'Name', model.get('name'));

                    break;

                case 'belongsTo':
                    this.set(link + 'Id', model.id);
                    this.set(link + 'Name', model.get('name'));

                    break;

                case 'hasMany':
                    let ids = [];
                    ids.push(model.id);

                    let names = {};

                    names[model.id] = model.get('name');

                    this.set(link + 'Ids', ids);
                    this.set(link + 'Names', names);

                    break;
            }
        };

        if (Object.prototype.toString.call(data) === '[object Array]') {
            data.forEach(options => {
                setRelate(options);
            });

            return;
        }

        setRelate(data);
    }

    /**
     * Get a field type.
     *
     * @param {string} field
     * @returns {string|null}
     */
    getFieldType(field) {
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
    getFieldParam(field, param) {
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

    /**
     * Get a link type.
     *
     * @param {string} link
     * @returns {string|null}
     */
    getLinkType(link) {
        if (!this.defs || !this.defs.links) {
            return null;
        }

        if (link in this.defs.links) {
            return this.defs.links[link].type || null;
        }

        return null;
    }

    /**
     * Get a link param.
     *
     * @param {string} link A link.
     * @param {string} param A param.
     * @returns {*}
     */
    getLinkParam(link, param) {
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
     * Is a field read-only.
     *
     * @param {string} field A field.
     * @returns {bool}
     */
    isFieldReadOnly(field) {
        return this.getFieldParam(field, 'readOnly') || false;
    }

    /**
     * If a field required.
     *
     * @param {string} field A field.
     * @returns {bool}
     */
    isRequired(field) {
        return this.getFieldParam(field, 'required') || false;
    }

    /**
     * Get IDs of a link-multiple field.
     *
     * @param {string} field A link-multiple field name.
     * @returns {string[]}
     */
    getLinkMultipleIdList(field) {
        return this.get(field + 'Ids') || [];
    }

    /**
     * Get team IDs.
     *
     * @returns {string[]}
     */
    getTeamIdList() {
        return this.get('teamsIds') || [];
    }

    /**
     * Whether it has a field.
     *
     * @param {string} field A field.
     * @returns {boolean}
     */
    hasField(field) {
        return ('defs' in this) && ('fields' in this.defs) && (field in this.defs.fields);
    }

    /**
     * Whether has a link.
     *
     * @param {string} link A link.
     * @returns {boolean}
     */
    hasLink(link) {
        return ('defs' in this) && ('links' in this.defs) && (link in this.defs.links);
    }

    /**
     * @returns {boolean}
     */
    isEditable() {
        return true;
    }

    /**
     * @returns {boolean}
     */
    isRemovable() {
        return true;
    }

    /**
     * Get an entity type.
     *
     * @returns {string}
     */
    getEntityType() {
        return this.name;
    }

    /**
     * Abort the last fetch.
     */
    abortLastFetch() {
        if (this.lastSyncPromise && this.lastSyncPromise.getReadyState() < 4) {
            this.lastSyncPromise.abort();
        }
    }

    /**
     * @deprecated Use `getClonedAttributes`.
     * @todo Remove in v9.0.
     * @return {Object.<string, *>}
     */
    toJSON() {
        console.warn(`model.toJSON is deprecated. Use 'getClonedAttributes' instead.`);

        return this.getClonedAttributes();
    }
}

Object.assign(Model.prototype, Events);

Model.extend = BullView.extend;

export default Model;
