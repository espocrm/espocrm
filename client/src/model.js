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

import Bull from 'lib!bullbone';
import Backbone from 'lib!backbone';
import _ from 'lib!underscore';

/**
 * When attributes have changed.
 *
 * @event Model#change
 * @param {Model} model A model.
 * @param {Object} o Options.
 */

/**
 * On sync with backend.
 *
 * @event Model#sync
 * @param {Model} model A model.
 * @param {Object} response Response from backend.
 * @param {Object} o Options.
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
     * A root URL.
     * @type {string|null}
     */
    urlRoot = null

    /**
     * A name.
     * @type {string|null}
     */
    name = null

    /**
     * An entity type.
     * @type {string|null}
     */
    entityType = null

    /**
     * @param {Object.<string, *>|Model} [attributes]
     * @param {{
     *     collection?: module:collection,
     *     entityType?: string,
     *     defs?: module:model~defs,
     *     user?: module:models/user,
     *     dateTime?: module:date-time
     * }} [options]
     */
    constructor(attributes, options) {
        options = options || {};

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

        /** @private */
        this.dateTime = options.dateTime || null;

        /** @private */
        this.changed = {};
        /** @private */
        this._previousAttributes = null;
    }

    /**
     * @todo Revise naming.
     *
     * @public
     * @return {Object.<string, *>}
     */
    toJSON() {
        return Espo.Utils.cloneDeep(this.attributes);
    }

    /**
     * @protected
     * @param {string} [method] HTTP method.
     * @param {Model} [model]
     * @param {Object} [options]
     * @returns {Promise}
     */
    sync(method, model, options) {
        if (method === 'patch') {
            options.type = 'PUT';
        }

        return Backbone.sync.call(this, method, model, options);
    }

    /**
     * Set an attribute value.
     *
     * @param {(string|Object)} attribute An attribute name or a {key => value} object.
     * @param {*} [value] A value or options if the first argument is an object.
     * @param {{
     *     silent?: boolean,
     * }} [options] Options. `silent` won't trigger a `change` event.
     * @returns {this}
     * @fires Model#change Unless `{silent: true}`.
     */
    set(attribute, value, options) {
        if (typeof attribute === 'object') {
            let o = attribute;

            if ('id' in o) {
                this.id = o['id'];
            }
        }
        else if (attribute === 'id') {
            this.id = value;
        }

        return Backbone.Model.prototype.set.call(this, attribute, value, options);
    }

    /**
     * Set attributes values.
     *
     * @param {Object.<string, *>} attributes
     * @param {{
     *     silent?: boolean,
     * }} [options] Options. `silent` won't trigger a `change` event.
     * @return {this}
     */
    setMultiple(attributes, options) {
        return this.set(attributes, options);
    }

    /**
     * Get an attribute value.
     *
     * @param {string} attribute An attribute name.
     * @returns {*}
     */
    get(attribute) {
        if (attribute === 'id' && this.id) {
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

        return (typeof value !== 'undefined');
    }

    /**
     * Unset an attribute.
     *
     * @param {string} attribute
     * @param {Object} [options] Options.
     * @return {Model}
     */
    unset(attribute, options) {
        return this.set(attribute, void 0, _.extend({}, options, {unset: true}));
    }

    /**
     * Removes all attributes from the model, including the `id` attribute.
     * Fires a `change` event unless `silent` is passed as an option.
     *
     * @param {Object} [options] Options.
     */
    clear(options) {
        let attributes = {};

        for (let key in this.attributes) {
            attributes[key] = void 0;
        }

        return this.set(attributes, _.extend({}, options, {unset: true}));
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
     * @private
     */
    _validate() {
        return true;
    }

    /**
     * Fetch values from the backend.
     *
     * @param {Object} [options] Options.
     * @returns {Promise<Object>}
     * @fires Model#sync
     */
    fetch(options) {
        options = _.extend({parse: true}, options);

        let success = options.success;

        options.success = response => {
            let serverAttributes = options.parse ?
                this.parse(response, options) :
                response;

            if (!this.set(serverAttributes, options)) {
                return false;
            }

            if (success) {
                success.call(options.context, this, response, options);
            }

            this.trigger('sync', this, response, options);
        };

        this.lastXhr = this.sync('read', this, options);

        return this.lastXhr;
    }

    /**
     * Save values to the backend.
     *
     * @param {Object} [attributes] Attribute values.
     * @param {Object} [options] Options.
     * @returns {Promise}
     * @fires Model#sync
     */
    save(attributes, options) {
        return Backbone.Model.prototype.save.call(this, attributes, options);
    }

    /**
     * Delete the record in the backend.
     *
     * @param {{wait: boolean}} [options] Options.
     * @returns {Promise}
     * @fires Model#sync
     */
    destroy(options) {
        return Backbone.Model.prototype.destroy.call(this, options);
    }

    /**
     * @private
     */
    url() {
        let base = _.result(this, 'urlRoot');

        if (!base) {
            throw new Error("No URL.");
        }

        if (this.isNew()) {
            return base;
        }

        let id = this.get('id');

        return base.replace(/[^\/]$/, '$&/') + encodeURIComponent(id);
    }

    /**
     * @protected
     * @param {*} response
     * @param {Object.<string, *>} options
     * @return {*}
     */
    parse(response, options) {
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
                dateTime: this.dateTime,
                defs: this.defs,
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
     *
     * @param {*} defaultValue
     * @returns {*}
     * @deprecated
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
        if (this.lastXhr && this.lastXhr.readyState < 4) {
            this.lastXhr.abort();
        }
    }
}

Model.extend = Bull.View.extend;

_.extend(Model.prototype, Bull.Events);

export default Model;
