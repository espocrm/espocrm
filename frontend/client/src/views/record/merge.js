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

Espo.define('Views.Record.Merge', 'View', function (Dep) {

	return Dep.extend({

		template: 'record.merge',

		scope: null,

		data: function () {
			var rows = [];
			this.fields.forEach(function (field) {
				var o = {
					name: field,
					scope: this.scope,
				};
				o.columns = [];
				this.models.forEach(function (m) {
					o.columns.push({
						id: m.id,
						fieldVariable: m.id + '-' + field, 
					});
				});
				rows.push(o);
			}.bind(this));
			return {
				rows: rows,
				models: this.models,
				scope: this.scope,
			};
		},

		events: {
			'change input[type="radio"][name="check-all"]': function (e) {
				e.stopPropagation();
				var id = e.currentTarget.value;
				$('input[data-id="'+id+'"]').prop('checked', true);
			},
			'click button[data-action="cancel"]': function () {
				this.getRouter().navigate('#' + this.scope, {trigger: true});
			},
			'click button[data-action="merge"]': function () {
				var id = $('input[type="radio"][name="check-all"]:checked').val();
				
				var model;
				
				this.models.forEach(function (m) {
					if (m.id == id) {
						model = m;
					}
				}.bind(this));

				var self = this;

				var attributes = {};
				$('input.field-radio:checked').each(function (i, el) {
					var field = el.name;
					var id = $(el).data('id');
					if (model.id != id) {
						var fieldType = model.getFieldParam(field, 'type');
						var fields = self.getFieldManager().getActualAttributes(fieldType, field);							
						var modelFrom;							
						self.models.forEach(function (m) {
							if (m.id == id) {
								modelFrom = m;
								return;
							}
						});							
						fields.forEach(function (field) {
							attributes[field] = modelFrom.get(field);
						});

					}
				});
				
				self.notify('Merging...');
				model.once('sync', function () {
					var i = 0;
					var count = this.models.length;
					this.models.forEach(function (m) {
						if (m.id != model.id) {
							m.once('destroy', function () {
								i++;
								if (i == count - 1) {
									this.notify('Merged', 'success');
									this.getRouter().navigate('#' + this.scope + '/view/' + model.id, {trigger: true});
								}
							}, this);
							m.destroy();
						}							
					}, this);									
				}.bind(this));
				
				model.save(attributes, {
					patch: true,
					error: function () {
						self.notify('Error occured', 'error')
					},
				});
			}
		},
		
		afterRender: function () {
			$('input[data-id="' + this.models[0].id + '"]').prop('checked', true);
		},

		setup: function () {
			this.scope = this.options.models[0].name;
			this.models = this.options.models;

			var fieldManager = this.getFieldManager();

			var differentFieldList = [];
			var fieldsDefs = this.models[0].defs.fields;

			for (var field in fieldsDefs) {
				var type = fieldsDefs[field].type;
				if (fieldManager.isMergable(type) && !this.models[0].isFieldReadOnly(field)) {
					var actualFields = fieldManager.getActualAttributes(type, field);
					var differs = false;
					actualFields.forEach(function (field) {												
						var values = [];	 
						this.models.forEach(function (model) {
							values.push(model.get(field));
						});							
						var firstValue = values[0];
						values.forEach(function (value) {
							if (!_.isEqual(firstValue, value)) {
								differs = true;
							}
						});							
					}.bind(this));
					if (differs) {
						differentFieldList.push(field);
					}
				}
			}
			this.fields = differentFieldList;				

			this.fields.forEach(function (field) {
				var type = Espo.Utils.upperCaseFirst(this.models[0].getFieldParam(field, 'type'));
				
				this.models.forEach(function (model) {
					this.createView(model.id + '-' + field, this.getFieldManager().getViewName(type), {
						model: model,
						el: '.merge .' + model.id + ' .field-' + field,
						defs: {
							name: field,
						},
						mode: 'detail',
						readOnly: true,
					});
				}.bind(this));

			}.bind(this));
		},
	});
});


