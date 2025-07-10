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

(function () {

    const root = this;

    if (!root.Espo) {
        root.Espo = {};
    }

    if (root.Espo.loader) {
        throw new Error("Loader was already loaded.");
    }

    /**
     * A callback with resolved dependencies passed as parameters.
     * Should return a value to define a module.
     *
     * @callback Loader~requireCallback
     * @param {...any} arguments Resolved dependencies.
     * @returns {*}
     */

    /**
     * @typedef {Object} Loader~libData
     * @property {string} [exportsTo] Exports to.
     * @property {string} [exportsAs] Exports as.
     * @property {boolean} [sourceMap] Has a source map.
     * @property {string} [exposeAs] To expose to global as.
     * @property {string} [path] A path.
     * @property {string} [devPath] A path in developer mode.
     */

    /**
     * @typedef {Object} Loader~dto
     * @property {string} path
     * @property {function(value): void} callback
     * @property {function|null} [errorCallback]
     * @property {'script'|'text'} dataType
     * @property {string} id
     * @property {'amd'|'lib'|'res'} type
     * @property {string|null} exportsTo
     * @property {string|null} exportsAs
     * @property {string} [url]
     * @property {boolean} [useCache]
     * @property {boolean} [suppressAmd]
     */

    /**
     * A loader. Used for loading and defining AMD modules, resource loading.
     * Handles caching.
     */
    class Loader {

        /**
         * @param {int|null} [_cacheTimestamp=null]
         */
        constructor(_cacheTimestamp) {
            this._cacheTimestamp = _cacheTimestamp || null;
            /** @type {Object.<string, Loader~libData>} */
            this._libsConfig = {};
            this._loadCallbacks = {};
            this._pathsBeingLoaded = {};
            this._dataLoaded = {};
            this._definedMap = {};
            this._aliasMap = {};
            this._contextId = null;
            this._responseCache = null;
            this._basePath = '';

            this._internalModuleList = [];
            this._transpiledModuleList = [];
            this._internalModuleMap = {};
            this._isDeveloperMode = false;

            let baseUrl = window.location.origin + window.location.pathname;

            if (baseUrl.slice(-1) !== '/') {
                baseUrl = window.location.pathname.includes('.') ?
                    baseUrl.slice(0, baseUrl.lastIndexOf('/')) + '/' :
                    baseUrl + '/';
            }

            this._baseUrl = baseUrl;

            this._isDeveloperModeIsSet = false;
            this._basePathIsSet = false;
            this._responseCacheIsSet = false;
            this._internalModuleListIsSet = false;
            this._bundleFileMap = {};
            this._bundleMapping = {};
            /** @type {Object.<string, string[]>} */
            this._bundleDependenciesMap = {};
            /** @type {Object.<string, Promise>} */
            this._bundlePromiseMap = {};

            this._addLibsConfigCallCount = 0;
            this._addLibsConfigCallMaxCount = 2;

            /** @type {Object.<string, string>} */
            this._urlIdMap = {};
        }

        /**
         * @param {boolean} isDeveloperMode
         */
        setIsDeveloperMode(isDeveloperMode) {
            if (this._isDeveloperModeIsSet) {
                throw new Error('Is-Developer-Mode is already set.');
            }

            this._isDeveloperMode = isDeveloperMode;
            this._isDeveloperModeIsSet = true;
        }

        /**
         * @param {string} basePath
         */
        setBasePath(basePath) {
            if (this._basePathIsSet) {
                throw new Error('Base path is already set.');
            }

            this._basePath = basePath;
            this._basePathIsSet = true;
        }

        /**
         * @returns {Number}
         */
        getCacheTimestamp() {
            return this._cacheTimestamp;
        }

        /**
         * @param {Number} cacheTimestamp
         */
        setCacheTimestamp(cacheTimestamp) {
            this._cacheTimestamp = cacheTimestamp;
        }

        /**
         * @param {Cache} responseCache
         */
        setResponseCache(responseCache) {
            if (this._responseCacheIsSet) {
                throw new Error('Response-Cache is already set');
            }

            this._responseCache = responseCache;
            this._responseCacheIsSet = true;
        }

        /**
         * @param {string[]} internalModuleList
         */
        setInternalModuleList(internalModuleList) {
            if (this._internalModuleListIsSet) {
                throw new Error('Internal-module-list is already set');
            }

            this._internalModuleList = internalModuleList;
            this._internalModuleMap = {};
            this._internalModuleListIsSet = true;
        }

        /**
         * @param {string[]} transpiledModuleList
         */
        setTranspiledModuleList(transpiledModuleList) {
            this._transpiledModuleList = transpiledModuleList;
        }

        /**
         * @private
         * @param {string} id
         */
        _get(id) {
            if (id in this._definedMap) {
                return this._definedMap[id];
            }

            return void 0;
        }

        /**
         * @private
         * @param {string} id
         * @param {*} value
         */
        _set(id, value) {
            this._definedMap[id] = value;

            if (id.slice(0, 4) === 'lib!') {
                const libName = id.slice(4);

                const libsData = this._libsConfig[libName];

                if (libsData && libsData.exposeAs) {
                    const key = libsData.exposeAs;

                    window[key] = value;
                }
            }
        }

        /**
         * @private
         * @param {string} id
         * @return {string}
         */
        _idToPath(id) {
            if (id.indexOf(':') === -1) {
                return 'client/lib/transpiled/src/' + id + '.js';
            }

            const [mod, namePart] = id.split(':');

            if (mod === 'custom') {
                return 'client/custom/src/' + namePart + '.js';
            }

            const transpiled = this._transpiledModuleList.includes(mod);
            const internal = this._isModuleInternal(mod);

            if (transpiled) {
                if (internal) {
                    return `client/lib/transpiled/modules/${mod}/src/${namePart}.js`;
                }

                return `client/custom/modules/${mod}/lib/transpiled/src/${namePart}.js`;
            }

            if (internal) {
                return 'client/modules/' + mod + '/src/' + namePart + '.js';
            }

            return 'client/custom/modules/' + mod + '/src/' + namePart + '.js';
        }

        /**
         * @private
         * @param {string} id
         * @param {*} value
         */
        _executeLoadCallback(id, value) {
            if (!(id in this._loadCallbacks)) {
                return;
            }

            this._loadCallbacks[id].forEach(callback => callback(value));

            delete this._loadCallbacks[id];
        }

        /**
         * Define a module.
         *
         * @param {string|null} id A module name to be defined.
         * @param {string[]} dependencyIds A dependency list.
         * @param {Loader~requireCallback} callback A callback with resolved dependencies
         *   passed as parameters. Should return a value to define the module.
         */
        define(id, dependencyIds, callback) {
            if (id) {
                id = this._normalizeId(id);
            }

            if (!id && document.currentScript) {
                const src = document.currentScript.src;

                id = this._urlIdMap[src];

                delete this._urlIdMap[src];
            }

            if (!id && this._contextId) {
                id = this._contextId;
            }

            this._contextId = null;

            const existing = this._get(id);

            if (typeof existing !== 'undefined') {
                return;
            }

            if (!dependencyIds) {
                this._defineProceed(callback, id, [], -1);

                return;
            }

            const indexOfExports = dependencyIds.indexOf('exports');

            if (Array.isArray(dependencyIds)) {
                dependencyIds = dependencyIds.map(depId => this._normalizeIdPath(depId, id));
            }

            this.require(dependencyIds, (...args) => {
                this._defineProceed(callback, id, args, indexOfExports);
            });
        }

        /**
         * @private
         * @param {function} callback
         * @param {string} id
         * @param {Array} args
         * @param {number} indexOfExports
         */
        _defineProceed(callback, id, args, indexOfExports) {
            let value = callback.apply(root, args);

            if (typeof value === 'undefined' && indexOfExports === -1 && id) {
                throw new Error(`Could not load '${id}'.`);
            }

            if (indexOfExports !== -1) {
                const exports = args[indexOfExports];

                // noinspection JSUnresolvedReference
                value = ('default' in exports) ? exports.default : exports;
            }

            if (!id) {
                console.error(value);
                // Libs can define w/o id and set to the root.
                // Not supposed to happen as should be suppressed by require.amd = false;
                return;
            }

            this._set(id, value);
            this._executeLoadCallback(id, value);
        }

        /**
         * Require a module or multiple modules.
         *
         * @param {string|string[]} id A module or modules to require.
         * @param {Loader~requireCallback} callback A callback with resolved dependencies.
         * @param {Function|null} [errorCallback] An error callback.
         */
        require(id, callback, errorCallback) {
            let list;

            if (Object.prototype.toString.call(id) === '[object Array]') {
                list = id;

                list.forEach((item, i) => {
                    list[i] = this._normalizeId(item);
                });
            }
            else if (id) {
                id = this._normalizeId(id);

                list = [id];
            }
            else {
                list = [];
            }

            const totalCount = list.length;

            if (totalCount === 1) {
                this._load(list[0], callback, errorCallback);

                return;
            }

            if (totalCount) {
                let readyCount = 0;
                const loaded = {};

                list.forEach(depId => {
                    this._load(depId, c => {
                        loaded[depId] = c;

                        readyCount++;

                        if (readyCount === totalCount) {
                            const args = [];

                            for (const i in list) {
                                args.push(loaded[list[i]]);
                            }

                            callback.apply(root, args);
                        }
                    });
                });

                return;
            }

            callback.apply(root);
        }

        /**
         * @private
         */
        _convertCamelCaseToHyphen(string) {
            if (string === null) {
                return string;
            }

            return string.replace(/([a-z])([A-Z])/g, '$1-$2').toLowerCase();
        }

        /**
         * @param {string} id
         * @param {string} subjectId
         * @private
         */
        _normalizeIdPath(id, subjectId) {
            if (id.charAt(0) !== '.') {
                return id;
            }

            if (id.slice(0, 2) !== './' && id.slice(0, 3) !== '../') {
                return id;
            }

            let outputPath = id;

            const dirParts = subjectId.split('/').slice(0, -1);

            if (id.slice(0, 2) === './') {
                outputPath = dirParts.join('/') + '/' + id.slice(2);
            }

            const parts = outputPath.split('/');

            let up = 0;

            for (const part of parts) {
                if (part === '..') {
                    up++;

                    continue;
                }

                break;
            }

            if (!up) {
                return outputPath;
            }

            if (up) {
                outputPath = dirParts.slice(0, -up).join('/') + '/' + outputPath.slice(3 * up);
            }

            return outputPath;
        }

        /**
         * @private
         * @param {string} id
         * @return {string}
         */
        _restoreId(id) {
            if (!id.includes(':')) {
                return id;
            }

            const [mod, part] = id.split(':');

            return `modules/${mod}/${part}`;
        }

        /**
         * @private
         * @param {string} id
         * @return {string}
         */
        _normalizeId(id) {
            if (id in this._aliasMap) {
                id = this._aliasMap[id];
            }

            if (~id.indexOf('.') && !~id.indexOf('!') && id.slice(-3) !== '.js') {
                console.warn(`${id}: module ID should use slashes instead of dots and hyphen instead of CamelCase.`);
            }

            if (!!/[A-Z]/.exec(id[0])) {
                if (id.indexOf(':') !== -1) {
                    const arr = id.split(':');
                    const modulePart = arr[0];
                    const namePart = arr[1];

                    return this._convertCamelCaseToHyphen(modulePart) + ':' +
                        this._convertCamelCaseToHyphen(namePart)
                            .split('.')
                            .join('/');
                }

                return this._convertCamelCaseToHyphen(id).split('.').join('/');
            }

            if (id.startsWith('modules/')) {
                id = id.slice(8);

                const index = id.indexOf('/');

                if (index > 0) {
                    const mod = id.slice(0, index);
                    id = id.slice(index + 1);

                    return mod + ':' + id;
                }
            }

            return id;
        }

        /**
         * @private
         * @param {string} id
         * @param {function(*)} callback
         */
        _addLoadCallback(id, callback) {
            if (!(id in this._loadCallbacks)) {
                this._loadCallbacks[id] = [];
            }

            this._loadCallbacks[id].push(callback);
        }

        /**
         * @private
         * @param {string} id
         * @param {function(*)} callback
         * @param {function()} [errorCallback]
         */
        _load(id, callback, errorCallback) {
            if (id === 'exports') {
                callback({});

                return;
            }

            let dataType, type, path, exportsTo, exportsAs;

            let realName = id;
            let suppressAmd = false;

            if (id.indexOf('lib!') === 0) {
                dataType = 'script';
                type = 'lib';

                realName = id.slice(4);
                path = realName;

                exportsTo = 'window';
                exportsAs = null;

                const isDefinedLib = realName in this._libsConfig;

                if (isDefinedLib) {
                    const libData = this._libsConfig[realName] || {};

                    path = libData.path || path;

                    if (this._isDeveloperMode && libData.devPath) {
                        path = libData.devPath;
                    }

                    exportsTo = libData.exportsTo || null;
                    exportsAs = libData.exportsAs || null;
                }

                if (isDefinedLib && !exportsTo) {
                    type = 'amd';
                }

                if (!isDefinedLib && id.slice(-3) === '.js') {
                    suppressAmd = true;
                }

                if (exportsAs) {
                    suppressAmd = true;
                }

                if (path.indexOf(':') !== -1) {
                    console.error(`Not allowed path '${path}'.`);

                    throw new Error();
                }

                let obj = void 0;

                if (exportsTo && exportsAs) {
                    obj = this._fetchObject(exportsTo, exportsAs);
                }

                if (typeof obj === 'undefined' && id in this._definedMap) {
                    obj = this._definedMap[id];
                }

                if (typeof obj !== 'undefined') {
                    callback(obj);

                    return;
                }
            } else if (id.indexOf('res!') === 0) {
                dataType = 'text';
                type = 'res';

                realName = id.slice(4);
                path = realName;

                if (path.indexOf(':') !== -1) {
                    console.error(`Not allowed path '${path}'.`);

                    throw new Error();
                }
            } else {
                dataType = 'script';
                type = 'amd';

                if (!id || id === '') {
                    throw new Error("Can't load with empty module ID.");
                }

                const value = this._get(id);

                if (typeof value !== 'undefined') {
                    callback(value);

                    return;
                }

                const restoredId = this._restoreId(id);

                if (restoredId in this._bundleMapping) {
                    const bundleName = this._bundleMapping[restoredId];

                    this._requireBundle(bundleName).then(() => {
                        const value = this._get(id);

                        if (typeof value === 'undefined') {
                            const msg = `Could not obtain module '${restoredId}' from bundle '${bundleName}'.`;
                            console.error(msg);

                            throw new Error(msg);
                        }

                        callback(value);
                    });

                    return;
                }

                path = this._idToPath(id);
            }

            if (id in this._dataLoaded) {
                callback(this._dataLoaded[id]);

                return;
            }

            /** @type {Loader~dto} */
            const dto = {
                id: id,
                type: type,
                dataType: dataType,
                path: path,
                callback: callback,
                errorCallback: errorCallback,
                exportsAs: exportsAs,
                exportsTo: exportsTo,
                suppressAmd: suppressAmd,
            };

            if (path in this._pathsBeingLoaded) {
                this._addLoadCallback(id, callback);

                return;
            }

            this._pathsBeingLoaded[path] = true;

            let useCache = false;

            if (this._cacheTimestamp) {
                useCache = true;

                const sep = (path.indexOf('?') > -1) ? '&' : '?';

                path += sep + 'r=' + this._cacheTimestamp;
            }

            const url = this._basePath + path;

            dto.path = path;
            dto.url = url;
            dto.useCache = useCache;

            if (dto.dataType === 'script') {
                this._addLoadCallback(id, callback);

                const urlObj = new URL(this._baseUrl + url);

                if (!useCache) {
                    urlObj.searchParams.append('_', Date.now().toString())
                }

                const fullUrl = urlObj.toString();

                if (suppressAmd) {
                    define.amd = false;
                }

                if (type === 'amd') {
                    this._urlIdMap[fullUrl] = id;
                }

                this._addScript(fullUrl, () => {
                    if (suppressAmd) {
                        define.amd = true;
                    }

                    let value;

                    if (type === 'amd') {
                        value = this._get(id);

                        if (typeof value !== 'undefined') {
                            this._executeLoadCallback(id, value);

                            return;
                        }

                        // Supposed to be handled by the added callback.

                        return;
                    }

                    if (exportsTo && exportsAs) {
                        value = this._fetchObject(exportsTo, exportsAs);

                        this._dataLoaded[id] = value;
                        this._executeLoadCallback(id, value);

                        return;
                    }

                    if (type === 'lib') {
                        this._dataLoaded[id] = undefined;
                        this._executeLoadCallback(id, undefined);

                        return;
                    }

                    console.warn(`Could not obtain ${id}.`);
                });

                return;
            }

            if (!this._responseCache) {
                this._processRequest(dto);

                return;
            }

            this._responseCache
                .match(new Request(url))
                .then(response => {
                    if (!response) {
                        this._processRequest(dto);

                        return;
                    }

                    response.text()
                        .then(text => this._handleResponseText(dto, text));
                });
        }

        /**
         * @private
         * @param {string} url
         * @param {function} callback
         * @return {Promise}
         */
        _addScript(url, callback) {
            const script = document.createElement('script');

            script.src = url;
            script.async = true;

            script.addEventListener('error', e => {
                console.error(`Could not load script '${url}'.`, e);
            });

            document.head.appendChild(script);

            script.addEventListener('load', () => callback());
        }

        /**
         * @private
         * @param {string} name
         * @return {Promise}
         */
        _requireBundle(name) {
            if (this._bundlePromiseMap[name]) {
                return this._bundlePromiseMap[name];
            }

            const dependencies = this._bundleDependenciesMap[name] || [];

            if (!dependencies.length) {
                this._bundlePromiseMap[name] = this._addBundle(name);

                return this._bundlePromiseMap[name];
            }

            this._bundlePromiseMap[name] = new Promise(resolve => {
                const list = dependencies.map(item => {
                    if (item.indexOf('bundle!') === 0) {
                        return this._requireBundle(item.substring(7));
                    }

                    return Espo.loader.requirePromise(item);
                });

                Promise.all(list)
                    .then(() => this._addBundle(name))
                    .then(() => resolve());
            });

            return this._bundlePromiseMap[name];
        }

        /**
         * @private
         * @param {string} name
         * @return {Promise}
         */
        _addBundle(name) {
            let src = this._bundleFileMap[name];

            if (!src) {
                throw new Error(`Unknown bundle '${name}'.`);
            }

            if (this._cacheTimestamp) {
                const sep = (src.indexOf('?') > -1) ? '&' : '?';

                src += sep + 'r=' + this._cacheTimestamp;
            }

            src = this._basePath + src;

            const script = document.createElement('script');

            script.src = src;
            script.async = true;

            script.addEventListener('error', event => {
                console.error(`Could not load bundle '${name}'.`, event);
            });

            return new Promise(resolve => {
                document.head.appendChild(script);

                script.addEventListener('load', () => resolve());
            });
        }

        /**
         * @private
         * @return {*}
         */
        _fetchObject(exportsTo, exportsAs) {
            let from = root;

            if (exportsTo === 'window') {
                from = root;
            }
            else {
                for (const item of exportsTo.split('.')) {
                    from = from[item];

                    if (typeof from === 'undefined') {
                        return void 0;
                    }
                }
            }

            if (exportsAs in from) {
                return from[exportsAs];
            }

            return void 0;
        }

        /**
         * @private
         * @param {Loader~dto} dto
         */
        _processRequest(dto) {
            const url = dto.url;
            const errorCallback = dto.errorCallback;
            const path = dto.path;
            const useCache = dto.useCache;

            const urlObj = new URL(this._baseUrl + url);

            if (!useCache) {
                urlObj.searchParams.append('_', Date.now().toString())
            }

            fetch(urlObj)
                .then(response => {
                    if (!response.ok) {
                        if (typeof errorCallback === 'function') {
                            errorCallback();

                            return;
                        }

                        throw new Error(`Could not fetch asset '${path}'.`);
                    }

                    response.text().then(text => {
                        if (this._responseCache) {
                            this._responseCache.put(url, new Response(text));
                        }

                        this._handleResponseText(dto, text);
                    });
                })
                .catch(() => {
                    if (typeof errorCallback === 'function') {
                        errorCallback();

                        return;
                    }

                    throw new Error(`Could not fetch asset '${path}'.`);
                });
        }

        /**
         * @private
         * @param {Loader~dto} dto
         * @param {string} text
         */
        _handleResponseText(dto, text) {
            const id = dto.id;
            const callback = dto.callback;

            this._addLoadCallback(id, callback);

            this._dataLoaded[id] = text;

            this._executeLoadCallback(id, text);
        }

        /**
         * @param {Object.<string, Loader~libData>} data
         * @internal
         */
        addLibsConfig(data) {
            if (this._addLibsConfigCallCount === this._addLibsConfigCallMaxCount) {
                throw new Error("Not allowed to call addLibsConfig.");
            }

            this._addLibsConfigCallCount++;

            this._libsConfig = {...this._libsConfig, ...data};
        }

        /**
         * @param {Object.<string, string>} map
         */
        setAliasMap(map) {
            this._aliasMap = map;
        }

        /**
         * @private
         */
        _isModuleInternal(moduleName) {
            if (!(moduleName in this._internalModuleMap)) {
                this._internalModuleMap[moduleName] = this._internalModuleList.indexOf(moduleName) !== -1;
            }

            return this._internalModuleMap[moduleName];
        }

        /**
         * @param {string} name A bundle name.
         * @param {string} file A bundle file.
         * @internal
         */
        mapBundleFile(name, file) {
            this._bundleFileMap[name] = file;
        }

        /**
         * @param {string} name A bundle name.
         * @param {string[]} list Dependencies.
         * @internal
         */
        mapBundleDependencies(name, list) {
            this._bundleDependenciesMap[name] = list;
        }

        /**
         * @param {Object.<string, string>} mapping
         * @internal
         */
        addBundleMapping(mapping) {
            Object.assign(this._bundleMapping, mapping);
        }

        /**
         * @param {string} id
         * @internal
         */
        setContextId(id) {
            this._contextId = id;
        }

        /**
         * Require a module.
         *
         * @param {string} id A module to require.
         * @returns {Promise<*>}
         */
        requirePromise(id) {
            return new Promise((resolve, reject) => {
                this.require(
                    id,
                    arg => resolve(arg),
                    () => reject()
                );
            });
        }
    }

    const loader = new Loader();

    // noinspection JSUnusedGlobalSymbols

    Espo.loader = {

        /**
         * @param {boolean} isDeveloperMode
         * @internal
         */
        setIsDeveloperMode: function (isDeveloperMode) {
            loader.setIsDeveloperMode(isDeveloperMode);
        },

        /**
         * @param {string} basePath
         * @internal
         */
        setBasePath: function (basePath) {
            loader.setBasePath(basePath);
        },

        /**
         * @returns {Number}
         */
        getCacheTimestamp: function () {
            return loader.getCacheTimestamp();
        },

        /**
         * @param {Number} cacheTimestamp
         * @internal
         */
        setCacheTimestamp: function (cacheTimestamp) {
            loader.setCacheTimestamp(cacheTimestamp);
        },

        /**
         * @param {Cache} responseCache
         * @internal
         */
        setResponseCache: function (responseCache) {
            loader.setResponseCache(responseCache);
        },

        /**
         * Define a module.
         *
         * @param {string} id A module name to be defined.
         * @param {string[]} dependencyIds A dependency list.
         * @param {Loader~requireCallback} callback A callback with resolved dependencies
         *   passed as parameters. Should return a value to define the module.
         */
        define: function (id, dependencyIds, callback) {
            loader.define(id, dependencyIds, callback);
        },

        /**
         * Require a module or multiple modules.
         *
         * @param {string|string[]} id A module or modules to require.
         * @param {Loader~requireCallback} callback A callback with resolved dependencies.
         * @param {Function|null} [errorCallback] An error callback.
         */
        require: function (id, callback, errorCallback) {
            loader.require(id, callback, errorCallback);
        },

        /**
         * Require a module or multiple modules.
         *
         * @param {string|string[]} id A module or modules to require.
         * @returns {Promise<unknown>}
         */
        requirePromise: function (id) {
            return loader.requirePromise(id);
        },

        /**
         * @param {Object.<string, Loader~libData>} data
         * @internal
         */
        addLibsConfig: function (data) {
            loader.addLibsConfig(data);
        },

        /**
         * @param {string} name A bundle name.
         * @param {string} file A bundle file.
         * @internal
         */
        mapBundleFile: function (name, file) {
            loader.mapBundleFile(name, file);
        },

        /**
         * @param {string} name A bundle name.
         * @param {string[]} list Dependencies.
         * @internal
         */
        mapBundleDependencies: function (name, list) {
            loader.mapBundleDependencies(name, list);
        },

        /**
         * @param {Object.<string, string>} mapping
         * @internal
         */
        addBundleMapping: function (mapping) {
            loader.addBundleMapping(mapping);
        },

        /**
         * @param {string} id
         * @internal
         */
        setContextId: function (id) {
            loader.setContextId(id);
        },
    };

    /**
     * Require a module or multiple modules.
     *
     * @param {string|string[]} id A module or modules to require.
     * @param {Loader~requireCallback} callback A callback with resolved dependencies.
     * @param {Object} [context] A context.
     * @param {Function|null} [errorCallback] An error callback.
     *
     * @deprecated Use `Espo.loader.require` instead.
     */
    root.require = Espo.require = function (id, callback, context, errorCallback) {
        if (context) {
            callback = callback.bind(context);
        }

        loader.require(id, callback, errorCallback);
    };

    /**
     * Define an [AMD](https://github.com/amdjs/amdjs-api/blob/master/AMD.md) module.
     *
     * 3 signatures:
     * 1. `(callback)` – Unnamed, no dependencies.
     * 2. `(dependencyList, callback)` – Unnamed, with dependencies.
     * 3. `(moduleName, dependencyList, callback)` – Named.
     *
     * @param {string|string[]|Loader~requireCallback} arg1 A module name to be defined,
     *   a dependency list or a callback.
     * @param {string[]|Loader~requireCallback} [arg2] A dependency list or a callback with resolved
     *   dependencies.
     * @param {Loader~requireCallback} [arg3] A callback with resolved dependencies.
     */
    root.define = Espo.define = function (arg1, arg2, arg3) {
        let id = null;
        let depIds = null;
        let callback;

        if (typeof arg1 === 'function') {
            callback = arg1;
        } else if (typeof arg1 !== 'undefined' && typeof arg2 === 'function') {
            if (Array.isArray(arg1)) {
                depIds = arg1;
            } else {
                id = arg1;
                depIds = [];
            }

            callback = arg2;
        } else {
            id = arg1;
            depIds = arg2;
            callback = arg3;
        }

        loader.define(id, depIds, callback);
    };

    root.define.amd = true;

    (() => {
        const loaderParamsTag = document.querySelector('script[data-name="loader-params"]');

        if (!loaderParamsTag) {
            return;
        }

        /**
         * @type {{
         *     cacheTimestamp?: int,
         *     basePath?: string,
         *     internalModuleList?: [],
         *     transpiledModuleList?: [],
         *     libsConfig?: Object.<string, Loader~libData>,
         *     aliasMap?: Object.<string, *>,
         * }}
         */
        const params = JSON.parse(loaderParamsTag.textContent);

        loader.setCacheTimestamp(params.cacheTimestamp);
        loader.setBasePath(params.basePath);
        loader.setInternalModuleList(params.internalModuleList);
        loader.setTranspiledModuleList(params.transpiledModuleList);
        loader.addLibsConfig(params.libsConfig);
        loader.setAliasMap(params.aliasMap);
    })();

}).call(window);
