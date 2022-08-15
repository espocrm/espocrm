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

(function (_, $) {

    let root = this;

    if (!root.Espo) {
        root.Espo = {};
    }

    /**
     * A callback with resolved dependencies passed as parameters.
     *   Should return a value to define a module.
     *
     * @callback Espo.Loader~requireCallback
     * @param {...any} arguments Resolved dependencies.
     * @returns {*}
     */

    /**
     * A loader. Used for loading and defining AMD modules, resource loading.
     * Handles caching.
     *
     * @class
     * @param {?module:cache.Class} [cache=null]
     * @param {?int} [_cacheTimestamp=null]
     */
    let Loader = function (cache, _cacheTimestamp) {
        this._cacheTimestamp = _cacheTimestamp || null;
        this._cache = cache || null;
        this._libsConfig = {};
        this._loadCallbacks = {};
        this._pathsBeingLoaded = {};
        this._dataLoaded = {};
        this._classMap = {};
        this._loadingSubject = null;
        this._responseCache = null;
        this._basePath = '';

        this._internalModuleList = [];
        this._internalModuleMap = {};
        this._isDeveloperMode = false;

        this._baseUrl = window.location.origin + window.location.pathname;

        this._isDeveloperModeIsSet = false;
        this._basePathIsSet = false;
        this._cacheIsSet = false;
        this._responseCacheIsSet = false;
        this._internalModuleListIsSet = false;

        this._addLibsConfigCallCount = 0;
        this._addLibsConfigCallMaxCount = 2;
    };

    _.extend(Loader.prototype, /** @lends Loader.prototype */{

        /**
         * @param {boolean} isDeveloperMode
         */
        setIsDeveloperMode: function (isDeveloperMode) {
            if (this._isDeveloperModeIsSet) {
                throw new Error('Is-Developer-Mode is already set.');
            }

            this._isDeveloperMode = isDeveloperMode;
            this._isDeveloperModeIsSet = true;
        },

        /**
         * @param {string} basePath
         */
        setBasePath: function (basePath) {
            if (this._basePathIsSet) {
                throw new Error('Base path is already set.');
            }

            this._basePath = basePath;
            this._basePathIsSet = true;
        },

        /**
         * @returns {Number}
         */
        getCacheTimestamp: function () {
            return this._cacheTimestamp;
        },

        /**
         * @param {Number} cacheTimestamp
         */
        setCacheTimestamp: function (cacheTimestamp) {
            this._cacheTimestamp = cacheTimestamp;
        },

        /**
         * @param {module:cache.Class} cache
         */
        setCache: function (cache) {
            if (this._cacheIsSet) {
                throw new Error('Cache is already set');
            }

            this._cache = cache;
            this._cacheIsSet = true;
        },

        /**
         * @param {Cache} responseCache
         */
        setResponseCache: function (responseCache) {
            if (this._responseCacheIsSet) {
                throw new Error('Response-Cache is already set');
            }

            this._responseCache = responseCache;
            this._responseCacheIsSet = true;
        },

        /**
         * @param {string[]} internalModuleList
         */
        setInternalModuleList: function (internalModuleList) {
            if (this._internalModuleListIsSet) {
                throw new Error('Internal-module-list is already set');
            }

            this._internalModuleList = internalModuleList;
            this._internalModuleMap = {};
            this._internalModuleListIsSet = true;
        },

        /**
         * @private
         */
        _getClass: function (name) {
            if (name in this._classMap) {
                return this._classMap[name];
            }

            return false;
        },

        /**
         * @private
         */
        _setClass: function (name, o) {
            this._classMap[name] = o;
        },

        /**
         * @private
         */
        _nameToPath: function (name) {
            if (name.indexOf(':') === -1) {
                return 'client/src/' + name + '.js';
            }

            let arr = name.split(':');
            let namePart = arr[1];
            let modulePart = arr[0];

            if (modulePart === 'custom') {
                return 'client/custom/src/' + namePart + '.js' ;
            }

            if (this._isModuleInternal(modulePart)) {
                return 'client/modules/' + modulePart + '/src/' + namePart + '.js';
            }

            return 'client/custom/modules/' + modulePart + '/src/' + namePart + '.js';
        },

        /**
         * @private
         * @param {string} script
         * @param {string} name
         * @param {string} path
         */
        _execute: function (script, name, path) {
            /** @var {?string} */
            let module = null;

            let colonIndex = name.indexOf(':');

            if (colonIndex > 0) {
                module = name.substring(0, colonIndex);
            }

            let noStrictMode = false;

            if (!module && name.indexOf('lib!') === 0) {
                noStrictMode = true;

                if (!this._isDeveloperMode) {
                    let readName = name.substring(4);

                    let hasSourceMap = (this._libsConfig[readName] || {}).sourceMap

                    if (hasSourceMap) {
                        let realPath = path.split('?')[0];

                        script += `\n//# sourceMappingURL=${this._baseUrl + realPath}.map`;
                    }
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
        },

        /**
         * @private
         */
        _executeLoadCallback: function (subject, o) {
            if (subject in this._loadCallbacks) {
                this._loadCallbacks[subject].forEach(callback => callback(o));

                delete this._loadCallbacks[subject];
            }
        },

        /**
         * Define a module.
         *
         * @param {string} subject A module name to be defined.
         * @param {string[]} dependency A dependency list.
         * @param {Espo.Loader~requireCallback} callback A callback with resolved dependencies
         *   passed as parameters. Should return a value to define the module.
         */
        define: function (subject, dependency, callback) {
            if (subject) {
                subject = this._normalizeClassName(subject);
            }

            if (this._loadingSubject) {
                subject = subject || this._loadingSubject;

                this._loadingSubject = null;
            }

            if (!dependency) {
                this._defineProceed(callback, subject, []);

                return;
            }

            this.require(dependency, (...arguments) => {
                this._defineProceed(callback, subject, arguments);
            });
        },

        /**
         * @private
         */
        _defineProceed: function (callback, subject, args) {
            let o = callback.apply(root, args);

            if (!o) {
                if (this._cache) {
                    this._cache.clear('a', subject);
                }

                throw new Error("Could not load '" + subject + "'");
            }

            this._setClass(subject, o);
            this._executeLoadCallback(subject, o);
        },

        /**
         * Require a module or multiple modules.
         *
         * @param {string|string[]} subject A module or modules to require.
         * @param {Espo.Loader~requireCallback} callback A callback with resolved dependencies.
         * @param {Function|null} [errorCallback] An error callback.
         */
        require: function (subject, callback, errorCallback) {
            let list;

            if (Object.prototype.toString.call(subject) === '[object Array]') {
                list = subject;

                list.forEach((item, i) => {
                    list[i] = this._normalizeClassName(item);
                });
            }
            else if (subject) {
                subject = this._normalizeClassName(subject);

                list = [subject];
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
        },

        /**
         * @private
         */
        _convertCamelCaseToHyphen: function (string) {
            if (string === null) {
                return string;
            }

            return string.replace(/([a-z])([A-Z])/g, '$1-$2').toLowerCase();
        },

        /**
         * @private
         */
        _normalizeClassName: function (name) {
            if (~name.indexOf('.') && !~name.indexOf('!')) {
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

            return name;
        },

        /**
         * @private
         */
        _addLoadCallback: function (name, callback) {
            if (!(name in this._loadCallbacks)) {
                this._loadCallbacks[name] = [];
            }

            this._loadCallbacks[name].push(callback);
        },

        /**
         * @private
         */
        _load: function (name, callback, errorCallback) {
            let dataType, type, path, exportsTo, exportsAs;

            let realName = name;

            let noAppCache = false;

            if (name.indexOf('lib!') === 0) {
                dataType = 'script';
                type = 'lib';

                realName = name.substr(4);
                path = realName;

                exportsTo = 'window';
                exportsAs = realName;

                if (realName in this._libsConfig) {
                    let libData = this._libsConfig[realName] || {};

                    path = libData.path || path;

                    if (this._isDeveloperMode) {
                        path = libData.devPath || path;
                    }

                    exportsTo = libData.exportsTo || exportsTo;
                    exportsAs = libData.exportsAs || exportsAs;
                }

                if (path.indexOf(':') !== -1) {
                    console.error(`Not allowed path '${path}'.`);
                    throw new Error();
                }

                noAppCache = true;

                let obj = this._fetchObject(exportsTo, exportsAs);

                if (obj) {
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
                type = 'class';

                if (!name || name === '') {
                    throw new Error("Can not load empty class name");
                }

                let classObj = this._getClass(name);

                if (classObj) {
                    callback(classObj);

                    return;
                }

                path = this._nameToPath(name);
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
        },

        /**
         * @private
         */
        _fetchObject: function (exportsTo, exportsAs) {
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
        },

        /**
         * @private
         */
        _processCached: function (dto, cached) {
            let name = dto.name;
            let callback = dto.callback;
            let type = dto.type;
            let dataType = dto.dataType;
            let exportsAs = dto.exportsAs;
            let exportsTo = dto.exportsTo;

            if (type === 'class') {
                this._loadingSubject = name;
            }

            if (dataType === 'script') {
                this._execute(cached, name, dto.path);
            }

            if (type === 'class') {
                let classObj = this._getClass(name);

                if (classObj) {
                    callback(classObj);

                    return;
                }

                this._addLoadCallback(name, callback);

                return;
            }

            let data = cached;

            if (exportsTo && exportsAs) {
                data = this._fetchObject(exportsTo, exportsAs);
            }

            this._dataLoaded[name] = data;

            callback(data);
        },

        /**
         * @private
         */
        _processRequest: function (dto) {
            let name = dto.name;
            let url = dto.url;
            let errorCallback = dto.errorCallback;
            let path = dto.path;
            let useCache = dto.useCache;
            let noAppCache = dto.noAppCache;

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
                    this._cache.set('a', name, response);
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
        },

        /**
         * @private
         */
        _handleResponse: function (dto, response) {
            let name = dto.name;
            let callback = dto.callback;
            let type = dto.type;
            let dataType = dto.dataType;
            let exportsAs = dto.exportsAs;
            let exportsTo = dto.exportsTo;

            this._addLoadCallback(name, callback);

            if (type === 'class') {
                this._loadingSubject = name;
            }

            if (dataType === 'script') {
                this._execute(response, name, dto.path);
            }

            let data;

            if (type === 'class') {
                data = this._getClass(name);

                if (data && typeof data === 'function') {
                    this._executeLoadCallback(name, data);
                }

                return;
            }

            data = response;

            if (exportsTo && exportsAs) {
                data = this._fetchObject(exportsTo, exportsAs);
            }

            this._dataLoaded[name] = data;

            this._executeLoadCallback(name, data);
        },

        /**
         * @param {Object} data
         * @internal
         */
        addLibsConfig: function (data) {
            if (this._addLibsConfigCallCount === this._addLibsConfigCallMaxCount) {
                throw new Error("Not allowed to call addLibsConfig.");
            }

            this._addLibsConfigCallCount++;

            this._libsConfig = _.extend(this._libsConfig, data);
        },

        /**
         * @private
         */
        _isModuleInternal: function (moduleName) {
            if (!(moduleName in this._internalModuleMap)) {
                this._internalModuleMap[moduleName] = this._internalModuleList.indexOf(moduleName) !== -1;
            }

            return this._internalModuleMap[moduleName];
        },

        /**
         * Require a module or multiple modules.
         *
         * @param {string|string[]} subject A module or modules to require.
         * @returns {Promise<unknown>}
         */
        requirePromise: function (subject) {
            return new Promise((resolve, reject) => {
                this.require(
                    subject,
                    () => resolve(),
                    () => reject()
                );
            });
        },
    });

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
         * @param {module:cache.Class} cache
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
         * @param {string[]} internalModuleList
         */
        setInternalModuleList: function (internalModuleList) {
            loader.setInternalModuleList(internalModuleList);
        },

        /**
         * Define a module.
         *
         * @param {string} subject A module name to be defined.
         * @param {string[]} dependency A dependency list.
         * @param {Espo.Loader~requireCallback} callback A callback with resolved dependencies
         *   passed as parameters. Should return a value to define the module.
         */
        define: function (subject, dependency, callback) {
            loader.define(subject, dependency, callback);
        },

        /**
         * Require a module or multiple modules.
         *
         * @param {string|string[]} subject A module or modules to require.
         * @param {Espo.Loader~requireCallback} callback A callback with resolved dependencies.
         * @param {Function|null} [errorCallback] An error callback.
         */
        require: function (subject, callback, errorCallback) {
            loader.require(subject, callback, errorCallback);
        },

        /**
         * Require a module or multiple modules.
         *
         * @param {string|string[]} subject A module or modules to require.
         * @returns {Promise<unknown>}
         */
        requirePromise: function (subject) {
            return loader.requirePromise(subject);
        },

        /**
         * @param {Object} data
         * @internal
         */
        addLibsConfig: function (data) {
            loader.addLibsConfig(data);
        },
    };

    /**
     * Require a module or multiple modules.
     *
     * @param {string|string[]} subject A module or modules to require.
     * @param {Espo.Loader~requireCallback} callback A callback with resolved dependencies.
     * @param {Object} [context] A context.
     * @param {Function|null} [errorCallback] An error callback.
     */
    root.require = Espo.require = function (subject, callback, context, errorCallback) {
        if (context) {
            callback = callback.bind(context);
        }

        loader.require(subject, callback, errorCallback);
    };

    /**
     * Define an [AMD](https://github.com/amdjs/amdjs-api/blob/master/AMD.md) module.
     *
     * 3 signatures:
     * 1. `(callback)` – Unnamed, no dependencies.
     * 2. `(dependencyList, callback)` – Unnamed, with dependencies.
     * 3. `(moduleName, dependencyList, callback)` – Named.
     *
     * @param {string|string[]|Espo.Loader~requireCallback} arg1 A module name to be defined,
     *   a dependency list or a callback.
     * @param {string[]|Espo.Loader~requireCallback} [arg2] A dependency list or a callback with resolved
     *   dependencies.
     * @param {Espo.Loader~requireCallback} [arg3] A callback with resolved dependencies.
     */
    root.define = Espo.define = function (arg1, arg2, arg3) {
        let subject = null;
        let dependency = null;
        let callback;

        if (typeof arg1 === 'function') {
            callback = arg1;
        }
        else if (typeof arg1 !== 'undefined' && typeof arg2 === 'function') {
            dependency = arg1;
            callback = arg2;
        }
        else {
            subject = arg1;
            dependency = arg2;
            callback = arg3;
        }

        loader.define(subject, dependency, callback);
    };

}).call(window, _, $);
