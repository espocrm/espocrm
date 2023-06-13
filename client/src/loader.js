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

(function () {

    let root = this;

    if (!root.Espo) {
        root.Espo = {};
    }

    /**
     * A callback with resolved dependencies passed as parameters.
     *   Should return a value to define a module.
     *
     * @callback Loader~requireCallback
     * @param {...any} arguments Resolved dependencies.
     * @returns {*}
     */

    /**
     * @typedef Loader~libData
     * @type {Object}
     * @property {string} [exportsTo] Exports to.
     * @property {string} [exportsAs] Exports as.
     * @property {boolean} [sourceMap] Has a source map.
     * @property {boolean} [expose] To expose to global.
     * @property {string} [exposeAs] To expose to global as.
     * @property {string} [path] A path.
     * @property {string} [devPath] A path in developer mode.
     */

    /**
     * A loader. Used for loading and defining AMD modules, resource loading.
     * Handles caching.
     */
    class Loader {

        /**
         * @param {module:cache|null} [cache=null]
         * @param {int|null} [_cacheTimestamp=null]
         */
        constructor(cache, _cacheTimestamp) {
            this._cacheTimestamp = _cacheTimestamp || null;
            this._cache = cache || null;
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

            this._baseUrl = window.location.origin + window.location.pathname;

            this._isDeveloperModeIsSet = false;
            this._basePathIsSet = false;
            this._cacheIsSet = false;
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
         * @param {module:cache} cache
         */
        setCache(cache) {
            if (this._cacheIsSet) {
                throw new Error('Cache is already set');
            }

            this._cache = cache;
            this._cacheIsSet = true;
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
                let libName = id.slice(4);

                const libsData = this._libsConfig[libName];

                if (libsData && libsData.expose) {
                    let key = libsData.exposeAs || libName;

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

            let arr = id.split(':');
            let namePart = arr[1];
            let mod = arr[0];

            if (mod === 'custom') {
                return 'client/custom/src/' + namePart + '.js';
            }

            if (this._transpiledModuleList.includes(mod)) {
                return `client/lib/transpiled/modules/${mod}/src/${namePart}.js`;
            }

            if (this._isModuleInternal(mod)) {
                return 'client/modules/' + mod + '/src/' + namePart + '.js';
            }

            return 'client/custom/modules/' + mod + '/src/' + namePart + '.js';
        }

        /**
         * @private
         * @param {string} script
         * @param {string} id
         * @param {string} path
         */
        _execute(script, id, path) {
            /** @var {?string} */
            let module = null;

            let colonIndex = id.indexOf(':');

            if (colonIndex > 0) {
                module = id.substring(0, colonIndex);
            }

            let noStrictMode = false;

            if (!module && id.indexOf('lib!') === 0) {
                noStrictMode = true;

                let realName = id.substring(4);

                let libsData = this._libsConfig[realName] || {};

                if (!this._isDeveloperMode) {
                    if (libsData.sourceMap) {
                        let realPath = path.split('?')[0];

                        script += `\n//# sourceMappingURL=${this._baseUrl + realPath}.map`;
                    }
                }

                if (libsData.exportsTo === 'window' && libsData.exportsAs) {
                    script += `\nwindow.${libsData.exportsAs} = ` +
                        `window.${libsData.exportsAs} || ${libsData.exportsAs}\n`;
                }
            }

            script += `\n//# sourceURL=${this._baseUrl + path}`;

            // For bc.
            if (module && module !== 'crm') {
                noStrictMode = true;
            }

            if (noStrictMode) {
                (new Function(script)).call(root);

                return;
            }

            (new Function("'use strict'; " + script))();
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
                id = this._normalizeClassName(id);
            }

            if (this._contextId) {
                id = id || this._contextId;

                this._contextId = null;
            }

            let existing = this._get(id);

            if (typeof existing !== 'undefined') {
                return;
            }

            if (!dependencyIds) {
                this._defineProceed(callback, id, [], -1);

                return;
            }

            let indexOfExports = dependencyIds.indexOf('exports');

            if (Array.isArray(dependencyIds)) {
                dependencyIds = dependencyIds.map(item => this._normalizePath(item, id));
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
                if (this._cache) {
                    this._cache.clear('a', id);
                }

                throw new Error("Could not load '" + id + "'");
            }

            if (indexOfExports !== -1) {
                let exports = args[indexOfExports];

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
                    list[i] = this._normalizeClassName(item);
                });
            }
            else if (id) {
                id = this._normalizeClassName(id);

                list = [id];
            }
            else {
                list = [];
            }

            let totalCount = list.length;

            if (totalCount === 1) {
                this._load(list[0], callback, errorCallback);

                return;
            }

            if (totalCount) {
                let readyCount = 0;
                let loaded = {};

                list.forEach(name => {
                    this._load(name, c => {
                        loaded[name] = c;

                        readyCount++;

                        if (readyCount === totalCount) {
                            let args = [];

                            for (let i in list) {
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
         * @param {string} path
         * @param {string} id
         * @private
         */
        _normalizePath(path, id) {
            if (path.at(0) !== '.') {
                return path;
            }

            if (path.slice(0, 2) !== './' && path.slice(0, 3) !== '../') {
                return path;
            }

            let outputPath = path;

            let dirParts = id.split('/').slice(0, -1);

            if (path.slice(0, 2) === './') {
                outputPath = dirParts.join('/') + '/' + path.slice(2);
            }

            let parts = outputPath.split('/');

            let up = 0;

            for (let part of parts) {
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
         */
        _normalizeClassName(name) {
            if (name in this._aliasMap) {
                name = this._aliasMap[name];
            }

            if (~name.indexOf('.') && !~name.indexOf('!') && !name.slice(-3) === '.js') {
                console.warn(
                    name + ': ' +
                    'class name should use slashes for a directory separator and hyphen format.'
                );
            }

            if (!!/[A-Z]/.exec(name[0])) {
                if (name.indexOf(':') !== -1) {
                    let arr = name.split(':');
                    let modulePart = arr[0];
                    let namePart = arr[1];

                    return this._convertCamelCaseToHyphen(modulePart) + ':' +
                        this._convertCamelCaseToHyphen(namePart)
                            .split('.')
                            .join('/');
                }

                return this._convertCamelCaseToHyphen(name).split('.').join('/');
            }

            if (name.startsWith('modules/')) {
                name = name.slice(8);

                let index = name.indexOf('/');

                if (index > 0) {
                    let mod = name.slice(0, index);
                    name = name.slice(index + 1);

                    return mod + ':' + name;
                }
            }

            return name;
        }

        /**
         * @private
         */
        _addLoadCallback(name, callback) {
            if (!(name in this._loadCallbacks)) {
                this._loadCallbacks[name] = [];
            }

            this._loadCallbacks[name].push(callback);
        }

        /**
         * @private
         */
        _load(name, callback, errorCallback) {
            if (name === 'exports') {
                callback({});

                return;
            }

            let dataType, type, path, exportsTo, exportsAs;

            let realName = name;
            let noAppCache = false;

            if (name.indexOf('lib!') === 0) {
                dataType = 'script';
                type = 'lib';

                realName = name.substr(4);
                path = realName;

                exportsTo = 'window';
                exportsAs = null;


                let isDefinedLib = realName in this._libsConfig;

                if (isDefinedLib) {
                    const libData = this._libsConfig[realName] || {};

                    path = libData.path || path;

                    if (this._isDeveloperMode && libData.devPath) {
                        path = libData.devPath;
                    }

                    path = (this._isDeveloperMode ? libData.devPath : libData.path) || path;
                    exportsTo = libData.exportsTo || null;
                    exportsAs = libData.exportsAs || null;
                }

                if (isDefinedLib && !exportsTo) {
                    type = 'amd';
                }

                if (path.indexOf(':') !== -1) {
                    console.error(`Not allowed path '${path}'.`);

                    throw new Error();
                }

                noAppCache = true;

                let obj = void 0;

                if (exportsTo && exportsAs) {
                    obj = this._fetchObject(exportsTo, exportsAs);
                }


                if (typeof obj === 'undefined' && name in this._definedMap) {
                    obj = this._definedMap[name];
                }

                if (typeof obj !== 'undefined') {
                    callback(obj);

                    return;
                }
            }
            else if (name.indexOf('res!') === 0) {
                dataType = 'text';
                type = 'res';

                realName = name.substr(4);
                path = realName;

                if (path.indexOf(':') !== -1) {
                    console.error(`Not allowed path '${path}'.`);

                    throw new Error();
                }
            }
            else {
                dataType = 'script';
                type = 'amd';

                if (!name || name === '') {
                    throw new Error("Can not load empty class name");
                }

                let classObj = this._get(name);

                if (typeof classObj !== 'undefined') {
                    callback(classObj);

                    return;
                }

                if (name in this._bundleMapping) {
                    let bundleName = this._bundleMapping[name];

                    this._requireBundle(bundleName).then(() => {
                        let classObj = this._get(name);

                        if (typeof classObj === 'undefined') {
                            let msg = `Could not obtain class '${name}' from bundle '${bundleName}'.`;
                            console.error(msg);

                            throw new Error(msg);
                        }

                        callback(classObj);
                    });

                    return;
                }

                path = this._idToPath(name);
            }

            if (name in this._dataLoaded) {
                callback(this._dataLoaded[name]);

                return;
            }

            let dto = {
                name: name,
                type: type,
                dataType: dataType,
                noAppCache: noAppCache,
                path: path,
                callback: callback,
                errorCallback: errorCallback,
                exportsAs: exportsAs,
                exportsTo: exportsTo,
            };

            if (this._cache && !this._responseCache) {
                let cached = this._cache.get('a', name);

                if (cached) {
                    this._processCached(dto, cached);

                    return;
                }
            }

            if (path in this._pathsBeingLoaded) {
                this._addLoadCallback(name, callback);

                return;
            }

            this._pathsBeingLoaded[path] = true;

            let useCache = false;

            if (this._cacheTimestamp) {
                useCache = true;

                let sep = (path.indexOf('?') > -1) ? '&' : '?';

                path += sep + 'r=' + this._cacheTimestamp;
            }

            let url = this._basePath + path;

            dto.path = path;
            dto.url = url;
            dto.useCache = useCache;

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

                    response
                        .text()
                        .then(cached => {
                            this._handleResponse(dto, cached);
                        });
                });
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

            let dependencies = this._bundleDependenciesMap[name] || [];

            if (!dependencies.length) {
                this._bundlePromiseMap[name] = this._addBundle(name);

                return this._bundlePromiseMap[name];
            }

            this._bundlePromiseMap[name] = new Promise(resolve => {
                let list = dependencies.map(item => {
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
                let sep = (src.indexOf('?') > -1) ? '&' : '?';

                src += sep + 'r=' + this._cacheTimestamp;
            }

            src = this._basePath + src;

            let scriptEl = document.createElement('script');

            scriptEl.setAttribute('type', 'text/javascript')
            scriptEl.setAttribute('src', src);

            scriptEl.addEventListener('error', event => {
                console.error(`Could not load bundle '${name}'.`, event);
            });

            return new Promise(resolve => {
                document.head.appendChild(scriptEl);

                scriptEl.addEventListener('load', () => resolve());
            });
        }

        /**
         * @private
         */
        _fetchObject(exportsTo, exportsAs) {
            let from = root;

            if (exportsTo === 'window') {
                from = root;
            }
            else {
                for (let item of exportsTo.split('.')) {
                    from = from[item];

                    if (typeof from === 'undefined') {
                        return null;
                    }
                }
            }

            if (exportsAs in from) {
                return from[exportsAs];
            }
        }

        /**
         * @private
         */
        _processCached(dto, cached) {
            let id = dto.name;
            let callback = dto.callback;
            let type = dto.type;
            let dataType = dto.dataType;
            let exportsAs = dto.exportsAs;
            let exportsTo = dto.exportsTo;

            if (type === 'amd') {
                this._contextId = id;
            }

            if (dataType === 'script') {
                this._execute(cached, id, dto.path);
            }

            if (type === 'amd') {
                let value = this._get(id);

                if (typeof value !== 'undefined') {
                    callback(value);

                    return;
                }

                this._addLoadCallback(id, callback);

                return;
            }

            let data = cached;

            if (exportsTo && exportsAs) {
                data = this._fetchObject(exportsTo, exportsAs);
            }

            this._dataLoaded[id] = data;

            callback(data);
        }

        /**
         * @private
         */
        _processRequest(dto) {
            let id = dto.name;
            let url = dto.url;
            let errorCallback = dto.errorCallback;
            let path = dto.path;
            let useCache = dto.useCache;
            let noAppCache = dto.noAppCache;

            // @todo Use `fetch`.
            $.ajax({
                type: 'GET',
                cache: useCache,
                dataType: 'text',
                mimeType: 'text/plain',
                local: true,
                url: url,
            })
                .then(response => {
                    if (this._cache && !noAppCache && !this._responseCache) {
                        this._cache.set('a', id, response);
                    }

                    if (this._responseCache) {
                        this._responseCache.put(url, new Response(response));
                    }

                    this._handleResponse(dto, response);
                })
                .catch(() => {
                    if (typeof errorCallback === 'function') {
                        errorCallback();

                        return;
                    }

                    throw new Error("Could not load file '" + path + "'");
                });
        }

        /**
         * @private
         */
        _handleResponse(dto, response) {
            let id = dto.name;
            let callback = dto.callback;
            let type = dto.type;
            let dataType = dto.dataType;
            let exportsAs = dto.exportsAs;
            let exportsTo = dto.exportsTo;

            this._addLoadCallback(id, callback);

            if (type === 'amd') {
                this._contextId = id;
            }

            let isLib = id.slice(0, 4) === 'lib!';

            if (isLib && exportsAs) {
                define.amd = false;
            }

            if (dataType === 'script') {
                this._execute(response, id, dto.path);
            }

            if (isLib && exportsAs) {
                define.amd = true;
            }

            let result;

            if (type === 'amd') {
                result = this._get(id);

                if (typeof result !== 'undefined') {
                    this._executeLoadCallback(id, result);
                }

                return;
            }

            result = response;

            if (exportsTo && exportsAs) {
                result = this._fetchObject(exportsTo, exportsAs);
            }

            this._dataLoaded[id] = result;

            this._executeLoadCallback(id, result);
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
         * Require a module or multiple modules.
         *
         * @param {...string} id A module or modules to require.
         * @returns {Promise<unknown>}
         */
        requirePromise(id) {
            return new Promise((resolve, reject) => {
                this.require(
                    id,
                    (...args) => resolve(...args),
                    () => reject()
                );
            });
        }
    }

    let loader = new Loader();

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
         * @param {module:cache} cache
         * @internal
         */
        setCache: function (cache) {
            loader.setCache(cache);
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
        }
        else if (typeof arg1 !== 'undefined' && typeof arg2 === 'function') {
            if (Array.isArray(arg1)) {
                depIds = arg1;
            } else {
                id = arg1;
                depIds = [];
            }

            callback = arg2;
        }
        else {
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

        const params = JSON.parse(loaderParamsTag.textContent);

        loader.setCacheTimestamp(params.cacheTimestamp);
        loader.setBasePath(params.basePath);
        loader.setInternalModuleList(params.internalModuleList);
        loader.setTranspiledModuleList(params.transpiledModuleList);
        loader.addLibsConfig(params.libsConfig);
        loader.setAliasMap(params.aliasMap);
    })();

}).call(window);
