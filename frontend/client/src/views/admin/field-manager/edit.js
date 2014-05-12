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

Espo.define('Views.Admin.FieldManager.Edit', 'View', function (Dep) {
	
	return Dep.extend({
	
		template: 'admin.field-manager.edit',
		
		data: function () {
			return {
				scope: this.scope,
				field: this.field,
				defs: this.defs,
				params: this.params,
				type: this.type,
				fieldList: this.fieldList,
			};
		},
		
		events: {
			'click button[data-action="cancel"]': function () {
				this.getRouter().navigate('#Admin/fieldManager/scope=' + this.scope, {trigger: true});
			},
			'click button[data-action="save"]': function () {
				this.save();
			},
		},
		
		setup: function () {
			this.scope = this.options.scope;
			this.field = this.options.field;
			this.type = this.options.type;
			this.defs = {};
			
			this.fieldList = [];
			
			this.isNew = false;
			if (!this.field) {
				this.isNew = true;
			}
			
			this.model = new Espo.Model();
			this.model.name = 'Admin';
			this.model.urlRoot = 'Admin/fieldManager/' + this.scope;
			
			this.model.defs = {
				fields: {
					name: {required: true},
					label: {required: true},
				},
			};
			
			if (!this.isNew) {
				this.model.id = this.field;
				this.model.set('name', this.field);		
				this.model.set('label', this.getLanguage().translate(this.field, 'fields', this.scope));
			} else {
				this.model.set('type', this.type);
			}

						
			this.wait(true);
			this.getModelFactory().create(this.scope, function (model) {
			
				if (!this.isNew) {
					this.type = model.getFieldType(this.field);
					this.defs = model.defs.fields[this.field];
				}
					
				this.params = this.getFieldManager().getParams(this.type);
				
				this.model.set(this.defs);							
				
				this.createFieldView('varchar', 'name', !this.isNew);
				this.createFieldView('varchar', 'label');
				
				this.getView('name').on('change', function (m) {
					var name = this.model.get('name');
					this.model.set('label', name);					
					if (name) {						
						name = name.replace('-', '').replace(/[^\w\s]/gi, '').replace(/ (.)/g, function(match, g) {
        					return g.toUpperCase();
    					}).replace(' ', '');
    					if (name.length) {
    						 name = name.charAt(0).toLowerCase() + name.slice(1);
    					}
					}
					this.model.set('name', name);
				}, this);			
								
				this.params.forEach(function (o) {
					if (o.hidden) {
						return;
					}
					this.createFieldView(o.type, o.name);
				}, this);
				
			
				this.wait(false);
			}.bind(this));	
			 
		},
		
		createFieldView: function (type, name, readOnly) {
			this.createView(name, this.getFieldManager().getViewName(type), {
				model: this.model,
				el: this.options.el + ' .field-' + name,
				defs: {
					name: name,
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
				this.getMetadata().data['entityDefs'][this.scope]['fields'][this.model.get('name')] = this.model.toJSON();
				this.getMetadata().storeToCache();
				this.notify('Saved', 'success');
				
				var data = this.getLanguage().data;
				if (this.scope in data) {
					if (!('fields' in data[this.scope])) {
						data[this.scope]['fields'] = {};
					}
					data[this.scope]['fields'][this.model.get('name')] = this.model.get('label');
				}
				
				this.getRouter().navigate('#Admin/fieldManager/scope=' + this.scope + '&field=' + this.model.get('name'), {trigger: true});
			}.bind(this));
			
			this.notify('Saving...');
			this.model.save();
		},
		
	});

});
