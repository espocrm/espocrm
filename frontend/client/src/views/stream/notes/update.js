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

Espo.define('Views.Stream.Notes.Update', 'Views.Stream.Note', function (Dep) {

	return Dep.extend({

		template: 'stream.notes.update',
		
		data: function () {
			return _.extend({
				fieldsArr: this.fieldsArr,
				parentType: this.model.get('parentType')
			}, Dep.prototype.data.call(this));
		},
		
		events: {
			'click a[data-action="expandDetails"]': function (e) {		
				if (this.$el.find('.details').hasClass('hidden')) {
					this.$el.find('.details').removeClass('hidden');				
					$(e.currentTarget).find('span').removeClass('glyphicon-chevron-down').addClass('glyphicon-chevron-up');
				} else {
					this.$el.find('.details').addClass('hidden');				
					$(e.currentTarget).find('span').addClass('glyphicon-chevron-down').removeClass('glyphicon-chevron-up');
				}
			}
		},
		
		setup: function () {
			var data = JSON.parse(this.model.get('data'));
			
			var fields = data.fields;
			
			this.wait(true);
			this.getModelFactory().create(this.model.get('parentType'), function (model) {
				var modelWas = model;
				var modelBecame = model.clone();
				
				data.attributes = data.attributes || {};
				
				
				modelWas.set(data.attributes.was);
				modelBecame.set(data.attributes.became);				
				
				this.fieldsArr = [];
				
				fields.forEach(function (field) {
					var type = model.getFieldType(field) || 'base';
					this.createView(field + 'Was', this.getFieldManager().getViewName(type), {
						model: modelWas,
						readOnly: true,
						defs: {
							name: field
						},
						mode: 'list'
					});
						this.createView(field + 'Became', this.getFieldManager().getViewName(type), {
						model: modelBecame,
						readOnly: true,
						defs: {
							name: field
						},
						mode: 'list'
					});	
					
					this.fieldsArr.push({
						field: field,
						was: field + 'Was',
						became: field + 'Became'						
					});
									
				}, this);
			
				this.wait(false);				
			}, this);			
		},
		
	});
});

