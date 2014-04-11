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
	
Espo.define('Views.Admin.Layouts.List', 'Views.Admin.Layouts.Rows', function (Dep) {		

	return Dep.extend({
	
		dataAttributes: ['name', 'width', 'link'],
		
		dataAttributesDefs: {
			link: 'bool',
			width: 'text',
		},
		
		editable: true,
	
		setup: function () {
			Dep.prototype.setup.call(this);
			
			this.wait(true);
			this.loadLayout(function () {
				this.wait(false);
			}.bind(this));			
		},
		
		loadLayout: function (callback) {
			this.getModelFactory().create(Espo.Utils.hyphenToUpperCamelCase(this.scope), function (model) {
				this.getHelper().layoutManager.get(this.scope, this.type, function (layout) {					
					this.readDataFromLayout(model, layout);						
					if (callback) {
						callback();
					}				
				}.bind(this), false);
			}.bind(this));	
		},
		
		readDataFromLayout: function (model, layout) {
			var allFields = [];
			for (var field in model.defs.fields) {
				if (this.checkFieldType(model.getFieldParam(field, 'type'))) {
					allFields.push(field);
				}
			}			
					
			this.enabledFieldsList = [];
			
			this.enabledFields = [];
			this.disabledFields = [];
					
			for (var i in layout) {
				this.enabledFields.push({
					name: layout[i].name,
					label: this.getLanguage().translate(layout[i].name, 'fields', this.scope)
				});
				this.enabledFieldsList.push(layout[i].name);
			}
			
			
				
			for (var i in allFields) {
				if (!_.contains(this.enabledFieldsList, allFields[i])) {
					this.disabledFields.push({
						name: allFields[i],
						label: this.getLanguage().translate(allFields[i], 'fields', this.scope)
					});
				}
			}
			
			this.rowLayout = layout;
					
			for (var i in this.rowLayout) {
				this.rowLayout[i].label = this.getLanguage().translate(this.rowLayout[i].name, 'fields', this.scope);
			}
		},
		
		parseDataAttributes: function (dialog) {
			var width = parseFloat(dialog.$el.find("[name='width']").val());									
			if (isNaN(width) || width > 100 || width < 0) {
				width = '';
			}						
			return {
				width: width,
				link: dialog.$el.find("[name='link']").val()
			};				
		},
		
		checkFieldType: function (type) {
			if (['linkMultiple'].indexOf(type) != -1) {
				return false;
			}
			return true;
		},
	});
});


