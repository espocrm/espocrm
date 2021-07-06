/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2021 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
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

var Espo = Espo || {classMap: {}};

(function (Espo, _, $) {

    let root = this;

    Espo.Loader = function (cache, _cacheTimestamp) {
        this._cacheTimestamp = _cacheTimestamp || null;
        this._cache = cache || null;
        this._libsConfig = {};
        this._loadCallbacks = {};
        this._pathsBeingLoaded = {};
        this._dataLoaded = {};
        this._loadingSubject = null;
        this._responseCache = null;
        this._basePath = '';
        this._internalModuleList = [];
        this._internalModuleMap = {};

        this.isDeveloperMode = false;
    };

    _.extend(Espo.Loader.prototype, {

        _classMap: Espo,

        setBasePath: function (basePath) {
            this._basePath = basePath;
        },

        getCacheTimestamp: function () {
            return this._cacheTimestamp;
        },

        setCacheTimestamp: function (cacheTimestamp) {
            this._cacheTimestamp = cacheTimestamp;
        },

        setCache: function (cache) {
            this._cache = cache;
        },

        setResponseCache: function (responseCache) {
            this._responseCache = responseCache;
        },

        setInternalModuleList: function (internalModuleList) {
            this._internalModuleList = internalModuleList;
            this._internalModuleMap = {};
        },

        _getClass: function (name) {
            if (name in this._classMap) {
                return this._classMap[name];
            }

            return false;
        },

        _setClass: function (name, o) {
            this._classMap[name] = o;
        },

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
                return'client/modules/' + modulePart + '/src/' + namePart + '.js';
            }

            return 'client/custom/modules/' + modulePart + '/src/' + namePart + '.js';
        },

        _execute: function (script) {
            eval.call(root, script);
        },

        _executeLoadCallback: function (subject, o) {
            if (subject in this._loadCallbacks) {
                this._loadCallbacks[subject].forEach(callback => callback(o));

                delete this._loadCallbacks[subject];
            }
        },

        define: function (subject, dependency, callback) {
            if (subject) {
                subject = this._normalizeClassName(subject);
            }

            if (this._loadingSubject) {
                subject = subject || this._loadingSubject;

                this._loadingSubject = null;
            }

            if (!dependency) {
                this._defineProceedproceed(callback, subject, []);

                return;
            }

            this.require(dependency, (...arguments) => {
                this._defineProceed(callback, subject, arguments);
            });
        },

        _defineProceed: function (callback, subject, args) {
            let o = callback.apply(this, args);

            if (!o) {
                if (this._cache) {
                    this._cache.clear('a', subject);
                }

                throw new Error("Could not load '" + subject + "'");
            }

            this._setClass(subject, o);
            this._executeLoadCallback(subject, o);
        },

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
                this.load(list[0], callback, errorCallback);

                return;
            }

            if (totalCount) {
                let readyCount = 0;
                let loaded = {};

                list.forEach(name => {
                    this.load(name, c => {
                        loaded[name] = c;

                        readyCount++;

                        if (readyCount === totalCount) {
                            let args = [];

                            for (let i in list) {
                                args.push(loaded[list[i]]);
                            }

                            callback.apply(this, args);
                        }
                    });
                });

                return;
            }

            callback.apply(this);
        },

        _convertCamelCaseToHyphen: function (string) {
            if (string === null) {
                return string;
            }

            return string.replace(/([a-z])([A-Z])/g, '$1-$2').toLowerCase();
        },

        _normalizeClassName: function (name) {
            let normalizedName = name;

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

                return normalizedName = this._convertCamelCaseToHyphen(name).split('.').join('/');
            }

            return normalizedName;
        },

        _addLoadCallback: function (name, callback) {
            if (!(name in this._loadCallbacks)) {
                this._loadCallbacks[name] = [];
            }

            this._loadCallbacks[name].push(callback);
        },

        load: function (name, callback, errorCallback) {
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

                    if (this.isDeveloperMode) {
                        path = libData.devPath || path;
                    }

                    exportsTo = libData.exportsTo || exportsTo;
                    exportsAs = libData.exportsAs || exportsAs;
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
                this._execute(cached);
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
                this._execute(response);
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

        addLibsConfig: function (data) {
            this._libsConfig = _.extend(this._libsConfig, data);
        },

        _isModuleInternal: function (moduleName) {
            if (!(moduleName in this._internalModuleMap)) {
                this._internalModuleMap[moduleName] = this._internalModuleList.indexOf(moduleName) !== -1;
            }

            return this._internalModuleMap[moduleName];
        },
    });

    Espo.loader = new Espo.Loader();

    root.require = Espo.require = function (subject, callback, context, errorCallback) {
        if (context) {
            callback = callback.bind(context);
        }

        Espo.loader.require(subject, callback, errorCallback);
    };

    root.define = Espo.define = function (arg1, arg2, arg3) {
        let subject = null;
        let dependency = null;
        let callback = null;

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

        Espo.loader.define(subject, dependency, callback);
    };

}).call(this, Espo, _, $);
