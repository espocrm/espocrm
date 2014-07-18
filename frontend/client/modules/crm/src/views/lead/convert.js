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

Espo.define('Crm:Views.Lead.Convert', 'View', function (Dep) {

	return Dep.extend({

		template: 'crm:lead.convert',

		data: function () {
			return {
				scopes: this.scopes,
				scope: this.model.name,
			};
		},

		events: {
			'change input.check-scope': function (e) {
				var scope = $(e.currentTarget).data('scope');
				var $div = this.$el.find('.edit-container-' + Espo.Utils.toDom(scope));
				if (e.currentTarget.checked)	{
					$div.removeClass('hide');
				} else {
					$div.addClass('hide');
				}
			},
			'click button[data-action="convert"]': function (e) {			
				this.convert();
			},
			'click button[data-action="cancel"]': function (e) {
				this.getRouter().navigate('#Lead/view/' + this.id, {trigger: true});
			},
		},

		setup: function () {
			this.wait(true);
			this.id = this.options.id;
			
			this.notify('Loading...');

			this.getModelFactory().create('Lead', function (model) {
				this.model = model;
				model.id = this.id;

				this.listenToOnce(model, 'sync', function () {
					this.build();
				}.bind(this));
				model.fetch();
			}.bind(this));

		},

		build: function () {
			var scopes = this.scopes = [];
			for (var scope in this.model.defs.convertFields) {
				if (this.getAcl().check(scope, 'edit')) {
					scopes.push(scope);
				}
			}
			var i = 0;
			
			var attributeList = this.getFieldManager().getEntityAttributes(this.model.name);			
			var ignoreAttributeList = ['createdAt', 'modifiedAt', 'modifiedById', 'modifiedByName', 'createdById', 'createdByName'];			
			
			scopes.forEach(function (scope) {
				this.getModelFactory().create(scope, function (model) {
					model.populateDefaults();
					
					this.getFieldManager().getEntityAttributes(model.name).forEach(function (attr) {
						if (~attributeList.indexOf(attr) && !~ignoreAttributeList.indexOf(attr)) {
							model.set(attr, this.model.get(attr), {silent: true}); 
						}
					}, this);
									
					for (var field in this.model.defs.convertFields[scope]) {
						var leadField = this.model.defs.convertFields[scope][field];
						var leadAttrs = this.getFieldManager().getAttributes(this.model.getFieldParam(leadField, 'type'), leadField);
						var attrs = this.getFieldManager().getAttributes(model.getFieldParam(field, 'type'), field);

						attrs.forEach(function (attr, i) {
							var leadAttr = leadAttrs[i];
							model.set(attr, this.model.get(leadAttr));
						}.bind(this));
					}

					this.createView(scope, 'Record.Edit', {
						model: model,
						el: '#main .edit-container-' + Espo.Utils.toDom(scope),
						buttonsPosition: false,
						layoutName: 'detailConvert',
						exit: function () {},
					}, function (view) {
						i++;
						if (i == scopes.length) {
							this.wait(false);
							this.notify(false);
						}
					}.bind(this));
				}, this);
			}, this);
		},

		convert: function () {			
			
			var scopes = [];

			this.scopes.forEach(function (scope) {
				if (this.$el.find('input[data-scope="' + scope + '"]').get(0).checked) {
					scopes.push(scope);
				}
			}.bind(this));

			if (scopes.length == 0) {
				this.notify('Select one or more checkboxes', 'error');
				return;
			}

			var notValid = false;
			scopes.forEach(function (scope) {
				var editView = this.getView(scope);
				editView.model.set(editView.fetch());
				notValid = editView.validate() || notValid;
			}.bind(this));
			
			var self = this;
			
			var data = {
				id: self.model.id,
				records: {}
			};
			scopes.forEach(function (scope) {
				data.records[scope] = self.getView(scope).model.attributes;
			});
			

			if (!notValid) {
				this.$el.find('[data-action="convert"]').addClass('disabled');
				this.notify('Please wait...');
				$.ajax({
					url: 'Lead/action/convert',
					data: JSON.stringify(data),
					type: 'POST',
					success: function () {
						self.getRouter().navigate('#Lead/view/' + self.model.id, {trigger: true});
						self.notify('Converted', 'success');
					},
					error: function () {
						self.$el.find('[data-action="convert"]').removeClass('disabled');
					}
				});
			} else {
				this.notify('Not Valid', 'error');
			}
		},

	});
});

