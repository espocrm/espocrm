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

Espo.define('Views.ExternalAccount.OAuth2', 'View', function (Dep) {
	
	return Dep.extend({
	
		template: 'external-account.oauth2',
		
		events: {

		},
		
		data: function () {
			return {
				integration: this.integration,
				helpText: this.helpText
			};
		},
		
		events: {
			'click button[data-action="cancel"]': function () {
				this.getRouter().navigate('#ExternalAccount', {trigger: true});
			},
			'click button[data-action="save"]': function () {
				this.save();
			},
			'click [data-action="connect"]': function () {
				this.connect();
			}
		},
		
		setup: function () {
			this.integration = this.options.integration;
			this.id = this.options.id;
							
		
			this.helpText = false;
			if (this.getLanguage().has(this.integration, 'help', 'ExternalAccount')) {
				this.helpText = this.translate(this.integration, 'help', 'ExternalAccount');
			}
			
			this.fieldList = [];
			
			this.dataFieldList = [];		
			
			this.model = new Espo.Model();
			this.model.id = this.id;
			this.model.name = 'ExternalAccount';
			this.model.urlRoot = 'ExternalAccount';
			
			this.model.defs = {
				fields: {
					enabled: {
						required: true,
						type: 'bool'
					},
				}
			};			
			
			this.wait(true);			
				
			this.model.populateDefaults();
			
			this.listenToOnce(this.model, 'sync', function () {
				this.createFieldView('bool', 'enabled');
				
				$.ajax({
					url: 'ExternalAccount/action/getOAuthCredentials?id=' + this.id,
					dataType: 'json'
				}).done(function (respose) {
					this.clientId = respose.clientId;
					this.redirectUri = respose.redirectUri;
					this.wait(false);
				}.bind(this));
				
			}, this);
			
			this.model.fetch();			 
		},
		
		hideField: function (name) {
			this.$el.find('label.field-label-' + name).addClass('hide');
			this.$el.find('div.field-' + name).addClass('hide');
			var view = this.getView(name);
			if (view) {
				view.enabled = false;
			}
		},
		
		showField: function (name) {
			this.$el.find('label.field-label-' + name).removeClass('hide');
			this.$el.find('div.field-' + name).removeClass('hide');
			var view = this.getView(name);
			if (view) {
				view.enabled = true;
			}
		},
		
		afterRender: function () {
			if (!this.model.get('enabled')) {
				this.$el.find('.data-panel').addClass('hidden');
			}
			
			this.listenTo(this.model, 'change:enabled', function () {
				if (this.model.get('enabled')) {
					this.$el.find('.data-panel').removeClass('hidden');
				} else {
					this.$el.find('.data-panel').addClass('hidden');
				}
			}, this);
		},
		
		createFieldView: function (type, name, readOnly, params) {
			this.createView(name, this.getFieldManager().getViewName(type), {
				model: this.model,
				el: this.options.el + ' .field-' + name,
				defs: {
					name: name,
					params: params
				},
				mode: readOnly ? 'detail' : 'edit',
				readOnly: readOnly,
			});
			this.fieldList.push(name);
		},
		
		save: function () {
			this.fieldList.forEach(function (field) {
				var view = this.getView(field);
				if (!view.readOnly) {
					view.fetchToModel();
				}
			}, this);						
			
			var notValid = false;
			this.fieldList.forEach(function (field) {
				notValid = this.getView(field).validate() || notValid;
			}, this);
			
			if (notValid) {
				this.notify('Not valid', 'error');
				return;
			}					
			
			this.listenToOnce(this.model, 'sync', function () {	
				this.notify('Saved', 'success');
			}, this);
			
			this.notify('Saving...');
			this.model.save();
		},
		
		popup: function (options, callback) {
			options.windowName = options.windowName ||  'ConnectWithOAuth';
			options.windowOptions = options.windowOptions || 'location=0,status=0,width=800,height=400';
			options.callback = options.callback || function(){ window.location.reload(); };
			
			var self = this;
			
			var path = options.path;
			
			var arr = [];
			var params = (options.params || {});
			for (var name in params) {
				if (params[name]) {
					arr.push(name + '=' + encodeURI(params[name]));
				}
			}
			path += '?' + arr.join('&');
			
			var parseUrl = function (str) {
				var code = null;
				
				str = str.substr(str.indexOf('?') + 1, str.length);
				str.split('&').forEach(function (part) {
					var arr = part.split('=');
					var name = decodeURI(arr[0]);
					var value = decodeURI(arr[1] || '');
					
					if (name == 'code') {
						code = value;
					}
				}, this);
				if (code) {
					return {
						code: code,
					}
				}
			}
			
			popup = window.open(path, options.windowName, options.windowOptions);
			interval = window.setInterval(function () {
				if (popup.closed) {
					window.clearInterval(interval);
				} else {
					var res = parseUrl(popup.location.href.toString());
					if (res) {			
						callback.call(self, res);
						popup.close();
						window.clearInterval(interval);
					}
				}
			}, 500);
		},
		
		connect: function () {
			this.popup({
				path: this.getMetadata().get('integrations.' + this.integration + '.params.endpoint'),
				params: {
					client_id: this.clientId,
					redirect_uri: this.redirectUri,
					scope: this.getMetadata().get('integrations.' + this.integration + '.params.scope'),
					response_type: 'code',
					access_type: 'offline'
				}		
			}, function (res) {
				if (res.code) {
					$.ajax({
						url: 'ExternalAccount/action/authorizationCode',
						type: 'POST',
						data: JSON.stringify({
							'id': this.id,
							'code': res.code
						})
					}).done(function () {
					
						// TODO show Connected and Disconnect button
					
					}.bind(this));
					
				} else {
					this.notify('Error occured', 'error');
				}
			});
		},
		
	});

});
