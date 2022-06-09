/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2022 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

define('model', [], function () {

    let Dep = Backbone.Model;

    /**
     * @class Espo.Model
     * @extends Backbone.Model
     */
    let Model = Dep.extend(/** @lends Espo.Model */{

        /**
         * An entity type.
         * @property {string} name
         */
        name: null,

        dateTime: null,

        _user: null,

        defs: null,

        initialize: function () {
            this.urlRoot = this.urlRoot || this.name;

            this.defs = this.defs || {};

            this.defs.fields = this.defs.fields || {};
            this.defs.links = this.defs.links || {};

            Dep.prototype.initialize.call(this);
        },

        /**
         * @param {'patch'|'put'} method
         * @param {Espo.Model} model
         * @param {Object} options
         * @returns {Promise}
         */
        sync: function (method, model, options) {
            if (method === 'patch') {
                options.type = 'PUT';
            }

            return Dep.prototype.sync.call(this, method, model, options);
        },

        /**
         * @param {string|Object} key
         * @param {*} val
         * @param {Object} options
         * @returns {this}
         */
        set: function (key, val, options) {
            if (typeof key === 'object') {
                let o = key;

                if (this.idAttribute in o) {
                    this.id = o[this.idAttribute];
                }
            }
            else if (key === 'id') {
                this.id = val;
            }

            return Dep.prototype.set.call(this, key, val, options);
        },

        /**
         * @param {string} key
         * @returns {*}
         */
        get: function (key) {
            if (key === 'id' && this.id) {
                return this.id;
            }

            return Dep.prototype.get.call(this, key);
        },

        /**
         * @param {string} key
         * @returns {boolean}
         */
        has: function (key) {
            let value = this.get(key);

            return (typeof value !== 'undefined');
        },

        /**
         * @returns {boolean}
         */
        isNew: function () {
            return !this.id;
        },

        /**
         * @param {Object} defs
         */
        setDefs: function (defs) {
            this.defs = defs || {};

            this.defs.fields = this.defs.fields || {};
        },

        /**
         * @returns {Object}
         */
        getClonedAttributes: function () {
            var attributes = {};

            for (let name in this.attributes) {
                attributes[name] = Espo.Utils.cloneDeep(this.attributes[name]);
            }

            return attributes;
        },

        /**
         */
        populateDefaults: function () {
            var defaultHash = {};

            if ('fields' in this.defs) {
                for (let field in this.defs.fields) {
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
            }

            defaultHash = Espo.Utils.cloneDeep(defaultHash);

            for (let attr in defaultHash) {
                if (this.has(attr)) {
                    delete defaultHash[attr];
                }
            }

            this.set(defaultHash, {silent: true});
        },

        /**
         * @param {*} defaultValue
         * @returns {*}
         * @deprecated
         */
        parseDefaultValue: function (defaultValue) {
            if (
                typeof defaultValue === 'string' &&
                defaultValue.indexOf('javascript:') === 0
            ) {
                let code = defaultValue.substring(11);

                defaultValue = (new Function( "with(this) { " + code + "}")).call(this);
            }

            return defaultValue;
        },

        /**
         * @param {string} field
         * @param {string} column
         * @param {string} id
         * @returns {*}
         */
        getLinkMultipleColumn: function (field, column, id) {
            return ((this.get(field + 'Columns') || {})[id] || {})[column];
        },

        /**
         * @param {Object} data
         */
        setRelate: function (data) {
            let setRelate = options => {
                var link = options.link;
                var model = options.model;

                if (!link || !model) {
                    throw new Error('Bad related options');
                }

                var type = this.defs.links[link].type;

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
                        var ids = [];
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
            }
            else {
                setRelate(data);
            }
        },

        /**
         * @param {string} field
         * @returns {string|null}
         */
        getFieldType: function (field) {
            if (this.defs && this.defs.fields && (field in this.defs.fields)) {
                return this.defs.fields[field].type || null;
            }

            return null;
        },

        /**
         * @param {string} field
         * @param {string} param
         * @returns {*}
         */
        getFieldParam: function (field, param) {
            if (this.defs && this.defs.fields && (field in this.defs.fields)) {
                if (param in this.defs.fields[field]) {
                    return this.defs.fields[field][param];
                }
            }

            return null;
        },

        /**
         * @param {string} link
         * @returns {string|null}
         */
        getLinkType: function (link) {
            if (this.defs && this.defs.links && (link in this.defs.links)) {
                return this.defs.links[link].type || null;
            }

            return null;
        },

        /**
         * @param {string} link
         * @param {string} param
         * @returns {*}
         */
        getLinkParam: function (link, param) {
            if (this.defs && this.defs.links && (link in this.defs.links)) {
                if (param in this.defs.links[link]) {
                    return this.defs.links[link][param];
                }
            }

            return null;
        },

        /**
         * @param {string} field
         * @returns {bool}
         */
        isFieldReadOnly: function (field) {
            return this.getFieldParam(field, 'readOnly') || false;
        },

        /**
         * @param {string} field
         * @returns {bool}
         */
        isRequired: function (field) {
            return this.getFieldParam(field, 'required') || false;
        },

        /**
         * @param {type} field
         * @returns {string[]}
         */
        getLinkMultipleIdList: function (field) {
            return this.get(field + 'Ids') || [];
        },

        /**
         * @param {type} field
         * @returns {string[]}
         */
        getTeamIdList: function () {
            return this.get('teamsIds') || [];
        },

        getDateTime: function () {
            return this.dateTime;
        },

        getUser: function () {
            return this._user;
        },

        /**
         * @param {string} field
         * @returns {boolean}
         */
        hasField: function (field) {
            return ('defs' in this) && ('fields' in this.defs) && (field in this.defs.fields);
        },

        /**
         * @param {string} field
         * @returns {boolean}
         */
        hasLink: function (link) {
            return ('defs' in this) && ('links' in this.defs) && (link in this.defs.links);
        },

        /**
         * @returns {boolean}
         */
        isEditable: function () {
            return true;
        },

        /**
         * @returns {boolean}
         */
        isRemovable: function () {
            return true;
        },

        /**
         * @returns {string}
         */
        getEntityType: function () {
            return this.name;
        },

        /**
         * @param {Object} options
         * @returns {Promise}
         */
        fetch: function (options) {
            this.lastXhr = Dep.prototype.fetch.call(this, options);

            return this.lastXhr;
        },

        /**
         */
        abortLastFetch: function () {
            if (this.lastXhr && this.lastXhr.readyState < 4) {
                this.lastXhr.abort();
            }
        },

    });

    return Model;
});
