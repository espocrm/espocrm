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

(function (Espo, _, Handlebars) {

	Espo.ViewHelper = function (options) {
		this.urlRegex = /(\b(https?|ftp|file):\/\/[-A-Z0-9+&@#\/%?=~_|!:,.;]*[-A-Z0-9+&@#\/%=~_|])/ig; 
		
		this._registerHandlebarsHelpers();		
		
		this.bbSearch = [
			/\[url\="?(.*?)"?\](.*?)\[\/url\]/g
		];
		this.bbReplace = [
			'<a href="$1">$2</a>'
		];
			
	}

	_.extend(Espo.ViewHelper.prototype, {

		layoutManager: null,

		settings: null,

		user: null,

		preferences: null,

		language: null,

		_registerHandlebarsHelpers: function () {
			var self = this;

			Handlebars.registerHelper('img', function (img) {
				return new Handlebars.SafeString("<img src=\"img/" + img + "\"></img>");
			});

			Handlebars.registerHelper('prop', function (object, name) {
				if (name in object) {
					return object[name];
				}
			});

			Handlebars.registerHelper('var', function (name, context, options) {
				return new Handlebars.SafeString(context[name]);
			});

			Handlebars.registerHelper('concat', function (left, right) {
				return left + right;
			});

			Handlebars.registerHelper('ifEqual', function (left, right, options) {
				if (left == right) {
					return options.fn(this);
				}
				return options.inverse(this);
			});

			Handlebars.registerHelper('ifNotEqual', function (left, right, options) {
				if (left != right) {
					return options.fn(this);
				}
				return options.inverse(this);
			});

			Handlebars.registerHelper('ifPropEquals', function (object, property, value, options) {
				if (object[property] == value) {
					return options.fn(this);
				}
				return options.inverse(this);
			});
			
			Handlebars.registerHelper('ifAttrEquals', function (model, attr, value, options) {
				if (model.get(attr) == value) {
					return options.fn(this);
				}
				return options.inverse(this);
			});
			
			Handlebars.registerHelper('ifAttrNotEmpty', function (model, attr, options) {
				if (model.get(attr)) {
					return options.fn(this);
				}
				return options.inverse(this);
			});

			Handlebars.registerHelper('get', function (model, name) {
				return model.get(name);
			});

			Handlebars.registerHelper('length', function (arr) {
				return arr.length;
			});

			Handlebars.registerHelper('translate', function (name, options) {
				var scope = options.hash.scope || null;
				var category = options.hash.category || null;
				if (name === 'null') {
					return '';
				}
				return self.language.translate(name, category, scope);
			});

			Handlebars.registerHelper('button', function (name, options) {
				var style = options.hash.style || 'default';
				var scope = options.hash.scope || null;
				var label = options.hash.label || name;
				return new Handlebars.SafeString('<button class="btn btn-'+style+'" data-action="'+name+'" type="button">'+self.language.translate(label, 'labels', scope)+'</button>');
			});

			Handlebars.registerHelper('hyphen', function (string) {
				return Espo.Utils.convert(string, 'c-h');
			});

			Handlebars.registerHelper('toDom', function (string) {
				return Espo.Utils.toDom(string);
			});

			Handlebars.registerHelper('breaklines', function (text) {
				text = Handlebars.Utils.escapeExpression(text || '');
				text = text.replace(/(\r\n|\n|\r)/gm, '<br>');
				return new Handlebars.SafeString(text);
			});
			
			Handlebars.registerHelper('complexText', function (text) {
				text = Handlebars.Utils.escapeExpression(text || '');
				text = text.replace(/(\r\n|\n|\r)/gm, '<br>');
				text = text.replace(self.urlRegex, function (url) {  
					return '<a href="' + url + '">' + url + '</a>';  
				});
				self.bbSearch.forEach(function (re, i) {
					text = text.replace(re, self.bbReplace[i]);
				});
				
				text = text.replace('[#see-more-text]', ' <a href="javascript:" data-action="seeMoreText">' + self.language.translate('See more')) + '</a>';
				return new Handlebars.SafeString(text);
			});

			Handlebars.registerHelper('translateOption', function (name, options) {
				var scope = options.hash.scope || null;
				var field = options.hash.field || null;
				if (!field) {
					return '';
				}
				var translationHash = self.language.translate(field, 'options', scope) || {};
				return translationHash[name] || name;
			});

			Handlebars.registerHelper('options', function (list, value, options) {
				value = value || false;
				list = list || {};
				var html = '';
				var isArray = (Object.prototype.toString.call(list) === '[object Array]');

				var multiple = (Object.prototype.toString.call(value) === '[object Array]');
				var checkOption = function (name) {
					if (multiple) {
						return value.indexOf(name) != -1;
					} else {
						return value === name;
					}
				};

				var scope = options.hash.scope || false;
				var category = options.hash.category || false;
				var field = options.hash.field || false;

				var translationHash;
				if (!category && field) {
					var translationHash = self.language.translate(field, 'options', scope) || {};
				}

				var translate = function (name) {
					if (!category && field) {
						if (typeof translationHash === 'object' && name in translationHash) {
							return translationHash[name];
						}
						return name;
					}
					return self.language.translate(name, category, scope);
				};

				for (var key in list) {
					var keyVal = list[key];
					html += "<option value=\"" + keyVal + "\" " + (checkOption(list[key]) ? 'selected' : '') + ">" + translate(list[key]) + "</option>"
				}
				return new Handlebars.SafeString(html);
			});
		}
	});


}).call(this, Espo, _, Handlebars);
