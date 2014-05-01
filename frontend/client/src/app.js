/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014  Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
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
 ************************************************************************/ 
var Espo = Espo || {};

Espo.App = function (options, callback) {
	var options = options || {};

	this.useCache = options.useCache || this.useCache;
	this.url = options.url || this.url;

	this.controllers = {};

	if (this.useCache) {
		this.cache = new Espo.Cache();
		if (options.cacheTimestamp) {
			this.cache.handleActuality(options.cacheTimestamp);
		}
	}

	this.storage = new Espo.Storage();

	this.loader = Espo.loader;
	this.loader.cache = this.cache;

	this._setupAjax();

	this.settings = new Espo['Models.Settings'](null, {cache: this.cache});	
	if (!this.settings.loadFromCache()) {
		this.settings.load(true);
	}

	this.metadata = new Espo.Metadata(this.cache);
	
	this.language = new Espo.Language(this.cache);

	this.fieldManager = new Espo.FieldManager();

	this.user = new Espo['Models.User']();
	this.preferences = new Espo['Models.Preferences']();
	this.preferences.settings = this.settings;
	this.acl = new Espo.Acl(this.user);
	
	this.preferences.on('update', function (model) {
		this.storage.set('user', 'preferences', this.preferences.toJSON());
	}, this);

	this._modelFactory = new Espo.ModelFactory(this.loader, this.metadata, this.user);
	this._collectionFactory = new Espo.CollectionFactory(this.loader, this._modelFactory);

	this._initDateTime();
	this._initView();
	this._initBaseController();

	this._preLoader = new Espo.PreLoader(this.cache, this._viewFactory);
	
	var countLoaded = 0;
	var manageCallback = function () {
		countLoaded++;
		if (countLoaded == 2) {
			callback.call(this, this);
		}
	}.bind(this);
	
	this.loader.loadLibsConfig(manageCallback);
	this._preLoad(manageCallback);
}

_.extend(Espo.App.prototype, {

	useCache: false,

	user: null,

	preferences: null,

	settings: null,

	metadata: null,

	language: null,

	fieldManager: null,

	cache: null,

	loader: null,

	url: 'api/v1',

	auth: null,

	baseController: null,

	controllers: null,

	router: null,

	_modelFactory: null,
	_collectionFactory: null,
	_viewFactory: null,
	_viewLoader: null,
	_viewHelper: null,

	start: function () {
		this._initAuth();
		var onAuth = function () {
			this.metadata.load(function () {
				this.fieldManager.defs = this.metadata.get('fields');
				this.fieldManager.metadata = this.metadata;
				
				this.settings.defs = this.metadata.get('entityDefs.Settings');
				this.user.defs = this.metadata.get('entityDefs.User');
				this.preferences.defs = this.metadata.get('entityDefs.Preferences');			
										
				this._initRouter();
			}.bind(this));
		}.bind(this);

		if (!this.auth) {
			this.baseController.login();
		} else {
			this._initUserData();
			onAuth();
		}

		this.on('auth', onAuth);
	},

	_initRouter: function () {
		this.router = new Espo.Router();
		this._viewHelper.router = this.router;
		this.baseController._router = this.router;
		this.router.on('routed', function (params) {
			this.doAction(params);
		}.bind(this));
		try {
			Backbone.history.start();
		} catch (e) {
			Backbone.history.loadUrl();
		}
	},

	doAction: function (params) {
		this.trigger('action', params);

		this._getController(params.controller, function (controller) {
			try {
				controller.doAction(params.action, params.options);
				this.trigger('action:done');
			} catch (e) {
				switch (e.name) {
					case 'AccessDenied':
						this.baseController.error403();
						break;
					case 'NotFound':
						this.baseController.error404();
						break;
					default:
						throw e;
				}
			}
		}.bind(this));
	},

	_initBaseController: function () {
		this.baseController = new Espo['Controllers.Base']({}, this._getControllerInjection());
	},

	_getControllerInjection: function () {
		return {
			viewFactory: this._viewFactory,
			modelFactory: this._modelFactory,
			collectionFactory: this._collectionFactory,
			settings: this.settings,
			user: this.user,
			preferences: this.preferences,
			acl: this.acl,
			cache: this.cache,
			router: this.router,
			storage: this.storage,
			metadata: this.metadata,
			dateTime: this.dateTime,
		};
	},

	_getController: function (name, callback) {
		if (!(name || false)) {
			callback(this.baseController);
			return;
		}
		if (!(name in this.controllers)) {		
			try {
				var className = this.metadata.get('clientDefs.' + name + '.controller');
				if (!className) {
					var module = this.metadata.get('scopes.' + name + '.module');
					className = Espo.Utils.composeClassName(module, name, 'Controllers');
				}
				
				this.loader.load(className, function (controllerClass) {
					this.controllers[name] = new controllerClass(this.baseController.params, this._getControllerInjection());
					this.controllers[name].name = name;
					callback(this.controllers[name]);
				}.bind(this),
				function () {
					this.baseController.error404();
				}.bind(this));
				return;
			} catch (e) {
				this.baseController.error404();
			}
		}
		callback(this.controllers[name]);
	},

	_preLoad: function (callback) {
		this._preLoader.load(callback, this);
	},

	_initDateTime: function () {
		this.dateTime = new Espo.DateTime();
		this._modelFactory.dateTime = this.dateTime;
		this.dateTime.setSettingsAndPreferences(this.settings, this.preferences);
	},

	_initView: function () {

		var helper = this._viewHelper = new Espo.ViewHelper();
		helper.layoutManager = new Espo.LayoutManager({cache: this.cache});
		helper.settings = this.settings;
		helper.user = this.user;
		helper.preferences = this.preferences;
		helper.acl = this.acl;
		helper.modelFactory = this._modelFactory;
		helper.collectionFactory = this._collectionFactory;
		helper.storage = this.storage;
		helper.dateTime = this.dateTime;
		helper.language = this.language;
		helper.metadata = this.metadata;
		helper.fieldManager = this.fieldManager;
		helper.cache = this.cache;
		helper.storage = this.storage;

		this._viewLoader = function (viewName, callback) {
			this.loader.load(Espo.Utils.composeViewClassName(viewName), callback);
		}.bind(this);
		
		var self = this;
		
	
		var getResourceInnerPath = function (type, name) {		
			switch (type) {
				case 'template':
					return 'res/templates/' + name.split('.').join('/') + '.tpl';
				case 'layoutTemplate':
					return 'res/layout-types/' + name + '.tpl';					
				case 'layout':
					return 'res/layouts/' + name + '.json';
					
			}
		};
		
		var getResourcePath = function (type, name) {
			var path;
			if (name.indexOf(':') != -1) {
				var arr = name.split(':');
				name = arr[1];
				var mod = arr[0];
				if (mod == 'custom') {
					path = 'client/custom/' + getResourceInnerPath(type, name);
				} else {
					path = 'client/modules/' + mod + '/' + getResourceInnerPath(type, name);
				}
			} else {
				path = 'client/' + getResourceInnerPath(type, name);
			}
			return path;
		};

		this._viewFactory = new Bull.Factory({
			useCache: false,
			defaultViewName: 'Base',
			helper: helper,
			viewLoader: this._viewLoader,
			resources: {
				loaders: {
					'template': function (name, callback) {						
						var path = getResourcePath('template', name);
						self.loader.load('res!'	+ path, callback);				
					},
					'layoutTemplate': function (name, callback) {						
						var path = getResourcePath('layoutTemplate', name);
						self.loader.load('res!'	+ path, callback);				
					}
				} 
			}
		});
	},

	_initAuth: function () {
		this.auth = this.storage.get('user', 'auth') || null;

		this.baseController.on('login', function (data) {
			this.auth = Base64.encode(data.auth.userName  + ':' + data.auth.password);								
			this.storage.set('user', 'auth', this.auth);
			this.storage.set('user', 'user', data.user);			
			this.storage.set('user', 'preferences', data.preferences);
			this.storage.set('user', 'acl', data.acl || {});			
			
			this._initUserData(data);

			this.trigger('auth');

		}.bind(this));

		this.baseController.on('logout', function () {
			this.logout();
		}.bind(this));
	},
	
	logout: function () {
		this.auth = null;
		this.user.clear();
		this.preferences.clear();
		this.acl.clear();
		this.storage.clear('user', 'auth');
		this.storage.clear('user', 'user');
		this.storage.clear('user', 'preferences');
		this.storage.clear('user', 'acl');
		this.doAction({action: 'login'});			
		this.language.clearCache();

        xhr = new XMLHttpRequest;
        xhr.open("GET", this.url + "/", !1, 'logout', 'logout');        
        xhr.send("");
        xhr.abort();
	},

	_initUserData: function (options) {	
		options = options || {};
		
		if (this.auth !== null) {
		
			this.language.load(null, true);
		
			var userData = options.user || this.storage.get('user', 'user') || null;
			var preferencesData = options.preferences || this.storage.get('user', 'preferences') || null;
			var aclData = options.acl || this.storage.get('user', 'acl') || null;
			
			this.user.set(userData);
			this.preferences.set(preferencesData);			
			this.acl.set(aclData);
			
			this.user.on('change', function () {
				this.storage.set('user', 'user', this.user.toJSON());		
			}, this);
			
			if (!this.auth) {
				return;
			}

			var arr = Base64.decode(this.auth).split(':');
			var xhr = new XMLHttpRequest();
			xhr.open('GET', this.url + '/', false, arr[0], arr[1]);				
			xhr.send('');				
		}
	},

	_setupAjax: function () {
		var self = this;
		$.ajaxSetup({
			beforeSend: function (xhr, options) {
				if (!options.local && self.url) {
					this.url = Espo.Utils.trimSlash(self.url) + '/' + this.url;
				}
				if (self.auth !== null) {
					xhr.setRequestHeader('Authorization', 'Basic ' + self.auth);
					xhr.setRequestHeader('Espo-Authorization', self.auth);
				}
			},
			dataType: 'json',
			timeout: 60000,
			contentType: 'application/json'
		});
		
		$(document).ajaxError(function (event, xhr, options) {
			if (xhr.errorIsHandled) {
				return;
			}
			switch (xhr.status) {
				case 0:
					if (xhr.statusText == 'timeout') {
						Espo.Ui.error(self.language.translate('Timeout'));
					}
					break;
				case 200:
					Espo.Ui.error(self.language.translate('Bad server response'));					
					break;
				case 401:
					if (!options.login) {
						Espo.Ui.error(self.language.translate('Auth error'));
						self.logout();
					}
					break;
				case 403:
					if (options.main) {
						self.baseController.error403();
					} else {
						Espo.Ui.error(self.language.translate('Error') + ' ' + xhr.status);
					}
					break;
				case 404:
					if (options.main) {
						self.baseController.error404();
					} else {
						Espo.Ui.error(self.language.translate('Error') + ' ' + xhr.status);
					}
					break;
				default:
					Espo.Ui.error(self.language.translate('Error') + ' ' + xhr.status);
			}
		});
	},

}, Backbone.Events);


